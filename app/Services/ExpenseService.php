<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\RegisterTransaction;
use App\Support\BusinessContext;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Expenses and expense categories. Cash expenses are always posted to an
 * open register so the drawer total stays correct.
 */
class ExpenseService
{
    public const PAYMENT_METHODS = ['cash', 'card', 'bank_transfer', 'cheque', 'credit', 'other'];

    public function __construct(private readonly RegisterService $registers) {}

    public function create(BusinessContext $ctx, array $input): array
    {
        $ctx->authorize('expenses', 'You do not have permission to create expenses');
        $data = $this->validated($input);

        return DB::transaction(function () use ($ctx, $input, $data) {
            $businessId = $ctx->businessId();
            $this->assertCategory($businessId, $data['category_id']);

            $register = null;
            if ($data['payment_method'] === 'cash') {
                $register = $this->registers->lockOpenById($businessId, $input['register_id'] ?? null, 'An open register is required for cash expenses');
            }

            $expense = Expense::query()->create($data + [
                'business_id' => $businessId,
                'created_by' => $ctx->userId(),
            ]);

            if ($register) {
                $this->registers->transaction($register, 'expense', Money::cents($data['amount']), $expense->id, 'Expense: '.$data['description'], $ctx->userId());
                $this->registers->adjust($register, 'cash_expenses', Money::cents($data['amount']));
            }

            return [
                'success' => true,
                'expense_id' => $expense->id,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'expense_date' => $expense->expense_date?->toISOString(),
            ];
        });
    }

    public function update(BusinessContext $ctx, string $expenseId, array $input): array
    {
        $ctx->authorize('expenses', 'You do not have permission to edit expenses');
        $data = $this->validated($input);

        return DB::transaction(function () use ($ctx, $expenseId, $input, $data) {
            $businessId = $ctx->businessId();

            $expense = Expense::query()->where('business_id', $businessId)->whereKey($expenseId)->lockForUpdate()->first()
                ?? throw new BusinessRuleException('Expense not found');
            $this->assertCategory($businessId, $data['category_id']);

            if ($expense->payment_method === 'cash') {
                $this->reverseCash($businessId, $expense);
            }

            $register = null;
            if ($data['payment_method'] === 'cash') {
                $register = $this->registers->lockOpenById($businessId, $input['register_id'] ?? null, 'An open register is required for cash expenses');
            }

            $expense->fill($data)->save();

            if ($register) {
                $this->registers->transaction($register, 'expense', Money::cents($data['amount']), $expense->id, 'Expense: '.$data['description'], $ctx->userId());
                $this->registers->adjust($register, 'cash_expenses', Money::cents($data['amount']));
            }

            return ['success' => true, 'expense_id' => $expense->id, 'amount' => $data['amount'], 'payment_method' => $data['payment_method']];
        });
    }

    public function delete(BusinessContext $ctx, string $expenseId): array
    {
        $ctx->authorize('expenses', 'You do not have permission to delete expenses');

        return DB::transaction(function () use ($ctx, $expenseId) {
            $businessId = $ctx->businessId();

            $expense = Expense::query()->where('business_id', $businessId)->whereKey($expenseId)->lockForUpdate()->first()
                ?? throw new BusinessRuleException('Expense not found');

            if ($expense->payment_method === 'cash') {
                $this->reverseCash($businessId, $expense);
            }
            $expense->delete();

            return ['success' => true, 'expense_id' => $expenseId, 'deleted' => true];
        });
    }

    public function createCategory(BusinessContext $ctx, string $name): array
    {
        $ctx->authorize('expenses', 'You do not have permission to manage expense categories');
        $name = $this->categoryName($name);

        try {
            $category = ExpenseCategory::query()->create(['business_id' => $ctx->businessId(), 'name' => $name, 'is_active' => true]);
        } catch (UniqueConstraintViolationException) {
            // A previously deactivated category with this name is brought back.
            $category = ExpenseCategory::query()->where('business_id', $ctx->businessId())->where('name', $name)->first();
            if (! $category || $category->is_active) {
                throw new BusinessRuleException('An expense category with this name already exists');
            }
            $category->update(['is_active' => true, 'name' => $name]);
        }

        return ['success' => true, 'category_id' => $category->id, 'name' => $name];
    }

    public function updateCategory(BusinessContext $ctx, string $categoryId, string $name): array
    {
        $ctx->authorize('expenses', 'You do not have permission to manage expense categories');
        $name = $this->categoryName($name);

        $category = ExpenseCategory::query()->where('business_id', $ctx->businessId())->whereKey($categoryId)->first()
            ?? throw new BusinessRuleException('Expense category not found');

        try {
            $category->update(['name' => $name]);
        } catch (UniqueConstraintViolationException) {
            throw new BusinessRuleException('An expense category with this name already exists');
        }

        return ['success' => true, 'category_id' => $category->id, 'name' => $name];
    }

    /**
     * Delete an unused category, or deactivate one that historic expenses use.
     */
    public function deleteCategory(BusinessContext $ctx, string $categoryId): array
    {
        $ctx->authorize('expenses', 'You do not have permission to manage expense categories');

        $category = ExpenseCategory::query()->where('business_id', $ctx->businessId())->whereKey($categoryId)->first()
            ?? throw new BusinessRuleException('Expense category not found');

        $used = Expense::query()->where('business_id', $ctx->businessId())->where('category_id', $category->id)->exists();
        if ($used) {
            $category->update(['is_active' => false]);

            return ['success' => true, 'category_id' => $categoryId, 'deleted' => false, 'deactivated' => true];
        }

        $category->delete();

        return ['success' => true, 'category_id' => $categoryId, 'deleted' => true, 'deactivated' => false];
    }

    private function reverseCash(string $businessId, Expense $expense): void
    {
        $transaction = RegisterTransaction::query()
            ->where('business_id', $businessId)
            ->where('reference_id', $expense->id)
            ->where('transaction_type', 'expense')
            ->orderByDesc('created_at')
            ->lockForUpdate()
            ->first() ?? throw new BusinessRuleException('Cash expense register transaction is missing');

        $register = $this->registers->lock($businessId, $transaction->register_id)
            ?? throw new BusinessRuleException('Cash expense register transaction is missing');

        $this->registers->adjust($register, 'cash_expenses', -Money::cents($transaction->amount));
        $transaction->delete();
    }

    private function assertCategory(string $businessId, ?string $categoryId): void
    {
        if ($categoryId && ! ExpenseCategory::query()->where('business_id', $businessId)->whereKey($categoryId)->where('is_active', true)->exists()) {
            throw new BusinessRuleException('Expense category not found or inactive');
        }
    }

    private function categoryName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            throw new BusinessRuleException('Category name is required');
        }
        if (mb_strlen($name) > 80) {
            throw new BusinessRuleException('Category name is too long');
        }

        return $name;
    }

    /**
     * @return array{category_id: ?string, description: string, amount: string, payment_method: string, expense_date: CarbonImmutable, notes: ?string}
     */
    private function validated(array $input): array
    {
        $description = trim((string) ($input['description'] ?? ''));
        if ($description === '') {
            throw new BusinessRuleException('Expense description is required');
        }

        $amount = Money::cents($input['amount'] ?? 0);
        if ($amount <= 0) {
            throw new BusinessRuleException('Expense amount must be greater than zero');
        }

        $method = (string) ($input['payment_method'] ?? 'cash');
        if (! in_array($method, self::PAYMENT_METHODS, true)) {
            throw new BusinessRuleException("Invalid payment method: {$method}");
        }

        try {
            $date = empty($input['expense_date']) ? CarbonImmutable::now() : CarbonImmutable::parse((string) $input['expense_date'])->utc();
        } catch (\Throwable) {
            throw new BusinessRuleException('Enter a valid expense date');
        }

        return [
            'category_id' => ($input['category_id'] ?? null) ?: null,
            'description' => $description,
            'amount' => Money::decimal($amount),
            'payment_method' => $method,
            'expense_date' => $date,
            'notes' => SaleService::nullableText($input['notes'] ?? null),
        ];
    }
}
