<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\CashRegister;
use App\Models\RegisterTransaction;
use App\Support\BusinessContext;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Cash register sessions and the cash ledger behind them.
 */
class RegisterService
{
    public const BUCKETS = ['cash_sales', 'cash_refunds', 'cash_expenses', 'cash_inflows', 'cash_outflows', 'cash_adjustments'];

    /**
     * Open a register. If one is already open it is returned instead, so a
     * double click or a stale screen can never create two open drawers.
     */
    public function open(BusinessContext $ctx, float $openingCash): CashRegister
    {
        if (! is_finite($openingCash) || $openingCash < 0) {
            throw new BusinessRuleException('Enter a valid opening cash amount');
        }

        return DB::transaction(function () use ($ctx, $openingCash) {
            $existing = $this->lockOpen($ctx->businessId());
            if ($existing) {
                return $existing;
            }

            $register = new CashRegister([
                'business_id' => $ctx->businessId(),
                'opened_by' => $ctx->userId(),
                'opened_at' => now(),
                'opening_cash' => Money::decimal(Money::cents($openingCash)),
                'status' => 'open',
            ]);
            $this->recalculate($register);

            return $register->refresh();
        });
    }

    public function close(BusinessContext $ctx, string $registerId, float $actualCash): CashRegister
    {
        if (! is_finite($actualCash) || $actualCash < 0) {
            throw new BusinessRuleException('Enter a valid closing cash amount');
        }

        return DB::transaction(function () use ($ctx, $registerId, $actualCash) {
            $register = CashRegister::query()
                ->where('business_id', $ctx->businessId())
                ->whereKey($registerId)
                ->lockForUpdate()
                ->first() ?? throw new BusinessRuleException('Cash register not found');

            if ($register->status !== 'open') {
                throw new BusinessRuleException('Cash register is not open');
            }

            $actual = Money::cents($actualCash);
            $register->fill([
                'closed_at' => now(),
                'closed_by' => $ctx->userId(),
                'actual_closing_cash' => Money::decimal($actual),
                'variance' => Money::decimal($actual - Money::cents($register->expected_closing_cash)),
                'status' => 'closed',
            ])->save();

            return $register;
        });
    }

    /**
     * Latest open register of the business, locked for update.
     */
    public function lockOpen(string $businessId): ?CashRegister
    {
        return CashRegister::query()
            ->where('business_id', $businessId)
            ->where('status', 'open')
            ->orderByDesc('opened_at')
            ->lockForUpdate()
            ->first();
    }

    /**
     * A specific open register, locked. Throws $missingMessage when no id was
     * given and $notFoundMessage when it is not an open register of this business.
     */
    public function lockOpenById(string $businessId, ?string $registerId, string $missingMessage, string $notFoundMessage = 'Open cash register not found'): CashRegister
    {
        if (! $registerId) {
            throw new BusinessRuleException($missingMessage);
        }

        return CashRegister::query()
            ->where('business_id', $businessId)
            ->whereKey($registerId)
            ->where('status', 'open')
            ->lockForUpdate()
            ->first() ?? throw new BusinessRuleException($notFoundMessage);
    }

    /**
     * Any register of the business (open or closed), locked.
     */
    public function lock(string $businessId, string $registerId): ?CashRegister
    {
        return CashRegister::query()
            ->where('business_id', $businessId)
            ->whereKey($registerId)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Add (or, with a negative amount, remove) cents in one register bucket
     * and keep expected_closing_cash consistent with every bucket.
     */
    public function adjust(CashRegister $register, string $bucket, int $cents): void
    {
        if (! in_array($bucket, self::BUCKETS, true)) {
            throw new \InvalidArgumentException("Unknown register bucket {$bucket}");
        }

        $register->{$bucket} = Money::decimal(max(0, Money::cents($register->{$bucket}) + $cents));
        $this->recalculate($register);
    }

    /**
     * expected = opening + sales + inflows - refunds - expenses - outflows + adjustments
     */
    public function recalculate(CashRegister $register): void
    {
        $expected = Money::cents($register->opening_cash)
            + Money::cents($register->cash_sales)
            + Money::cents($register->cash_inflows)
            - Money::cents($register->cash_refunds)
            - Money::cents($register->cash_expenses)
            - Money::cents($register->cash_outflows)
            + Money::cents($register->cash_adjustments);

        $register->expected_closing_cash = Money::decimal($expected);
        $register->save();
    }

    public function transaction(
        CashRegister $register,
        string $type,
        int $cents,
        ?string $referenceId,
        string $description,
        ?string $userId,
    ): RegisterTransaction {
        return RegisterTransaction::query()->create([
            'business_id' => $register->business_id,
            'register_id' => $register->id,
            'transaction_type' => $type,
            'amount' => Money::decimal(max(0, $cents)),
            'reference_id' => $referenceId,
            'description' => $description,
            'created_by' => $userId,
        ]);
    }
}
