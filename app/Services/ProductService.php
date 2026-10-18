<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Support\BusinessContext;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Products, prices, manual stock adjustments and catalogue master data.
 */
class ProductService
{
    public function __construct(private readonly StockLedger $stock) {}

    /**
     * Create or edit a product from the Add / Edit Product screen.
     */
    public function save(BusinessContext $ctx, array $input, ?string $productId = null): Product
    {
        $ctx->authorize('products', 'You do not have permission to manage products');

        $name = trim((string) ($input['name'] ?? ''));
        $sku = trim((string) ($input['sku'] ?? ''));
        if ($name === '' || $sku === '') {
            throw new BusinessRuleException('Product name and SKU are required');
        }
        if (trim((string) ($input['category'] ?? '')) === '') {
            throw new BusinessRuleException('Please select a category');
        }
        if (trim((string) ($input['unit'] ?? '')) === '') {
            throw new BusinessRuleException('Please select a unit');
        }
        [$cost, $sell, $stock, $low] = $this->numbers($input, 'Please enter valid product numbers');

        return $this->guardUnique(fn () => DB::transaction(function () use ($ctx, $input, $productId, $name, $sku, $cost, $sell, $stock, $low) {
            $businessId = $ctx->businessId();
            $category = $this->findOrCreate(Category::class, $businessId, $input['category']);
            $unit = $this->findOrCreate(Unit::class, $businessId, $input['unit']);
            $brandName = trim((string) ($input['brand'] ?? ''));
            $brand = $brandName === '' ? null : Brand::query()->where('business_id', $businessId)->where('name', $brandName)->first();

            $attributes = [
                'category_id' => $category->id,
                'brand_id' => $brand?->id,
                'unit_id' => $unit->id,
                'name' => $name,
                'sku' => $sku,
                'cost_price' => Money::decimal($cost),
                'selling_price' => Money::decimal($sell),
                'low_stock_alert' => $low,
            ];

            if ($productId) {
                $product = Product::query()->where('business_id', $businessId)->whereKey($productId)->lockForUpdate()->first()
                    ?? throw new BusinessRuleException('Product not found');
                $product->fill($attributes);
                $delta = Money::qty($stock - (float) $product->stock_quantity);
                if ($delta != 0.0) {
                    $this->stock->move($product, $delta, $delta > 0 ? 'adjustment_add' : 'adjustment_remove', null, 'Product stock updated', $ctx->userId());
                } else {
                    $product->save();
                }

                return $product;
            }

            $product = Product::query()->create($attributes + [
                'business_id' => $businessId,
                'barcode' => $sku,
                'stock_quantity' => $stock,
                'is_active' => true,
            ]);
            if ($stock > 0) {
                $this->stock->record($businessId, $product->id, 'opening_stock', $stock, null, 'Opening stock', $ctx->userId());
            }

            return $product;
        }));
    }

    /**
     * "Quick Add Product" from the purchase screen.
     */
    public function quickAdd(BusinessContext $ctx, array $input): Product
    {
        $ctx->authorize('products', 'You do not have permission to manage products');

        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            throw new BusinessRuleException('Product name required');
        }
        $sku = trim((string) ($input['sku'] ?? '')) ?: 'SKU-'.substr((string) now()->getTimestampMs(), -6);
        [$cost, $sell, $stock, $low] = $this->numbers($input, 'Enter valid product numbers');

        return $this->guardUnique(fn () => DB::transaction(function () use ($ctx, $name, $sku, $cost, $sell, $stock, $low) {
            $product = Product::query()->create([
                'business_id' => $ctx->businessId(),
                'name' => $name,
                'sku' => $sku,
                'barcode' => $sku,
                'cost_price' => Money::decimal($cost),
                'selling_price' => Money::decimal($sell),
                'stock_quantity' => $stock,
                'low_stock_alert' => $low,
                'is_active' => true,
            ]);
            if ($stock > 0) {
                $this->stock->record($ctx->businessId(), $product->id, 'opening_stock', $stock, null, 'Opening stock - quick add', $ctx->userId());
            }

            return $product;
        }));
    }

    /**
     * @param  list<array{id: string, selling_price: mixed}>  $prices
     */
    public function updatePrices(BusinessContext $ctx, array $prices): int
    {
        $ctx->authorize('products', 'You do not have permission to manage products');

        foreach ($prices as $row) {
            if (! is_array($row) || empty($row['id']) || ! is_numeric($row['selling_price'] ?? null) || (float) $row['selling_price'] < 0) {
                throw new BusinessRuleException('Enter valid selling prices');
            }
        }

        return DB::transaction(function () use ($ctx, $prices) {
            $count = 0;
            foreach ($prices as $row) {
                $count += Product::query()
                    ->where('business_id', $ctx->businessId())
                    ->whereKey($row['id'])
                    ->update(['selling_price' => Money::decimal(Money::cents($row['selling_price'])), 'updated_at' => now()]);
            }

            return $count;
        });
    }

    /**
     * Manual stock adjustment (Stock Adjustment screen). Port of adjust_stock().
     */
    public function adjustStock(BusinessContext $ctx, string $productId, string $type, mixed $quantity, ?string $reason): array
    {
        $ctx->authorizeAny(['stock', 'products'], 'You do not have permission to adjust stock');

        if (! in_array($type, ['add', 'remove'], true)) {
            throw new BusinessRuleException('Adjustment type must be add or remove');
        }
        $quantity = Money::qty($quantity);
        if ($quantity <= 0) {
            throw new BusinessRuleException('Adjustment quantity must be greater than zero');
        }

        return DB::transaction(function () use ($ctx, $productId, $type, $quantity, $reason) {
            $product = Product::query()
                ->where('business_id', $ctx->businessId())
                ->whereKey($productId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first() ?? throw new BusinessRuleException('Product not found or inactive');

            $old = (float) $product->stock_quantity;
            if ($type === 'remove' && $quantity > $old + 0.0000001) {
                throw new BusinessRuleException('Cannot remove '.Money::plain($quantity).' units. Current stock is only '.Money::plain($old).'.');
            }

            $movement = $type === 'add' ? 'adjustment_add' : 'adjustment_remove';
            $delta = $type === 'add' ? $quantity : -$quantity;
            $this->stock->move($product, $delta, $movement, null, SaleService::nullableText($reason), $ctx->userId());

            return [
                'success' => true,
                'product_id' => $product->id,
                'adjustment_type' => $type,
                'quantity' => $quantity,
                'old_stock' => $old,
                'new_stock' => (float) $product->stock_quantity,
                'movement_type' => $movement,
            ];
        });
    }

    /**
     * Add a category or unit by name (from the product form). Existing names are reused.
     */
    public function addMasterValue(BusinessContext $ctx, string $kind, string $name): Model
    {
        $ctx->authorize('products', 'You do not have permission to manage products');

        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 120) {
            throw new BusinessRuleException('Enter a valid name');
        }

        $class = match ($kind) {
            'category' => Category::class,
            'unit' => Unit::class,
            default => throw new BusinessRuleException('Unknown list'),
        };

        return $this->findOrCreate($class, $ctx->businessId(), $name);
    }

    /**
     * Case-insensitive lookup (the MySQL collation ignores case), created if missing.
     *
     * @param  class-string<Model>  $class
     */
    private function findOrCreate(string $class, string $businessId, string $name): Model
    {
        $name = trim($name);

        return $class::query()->where('business_id', $businessId)->where('name', $name)->first()
            ?? $class::query()->create(['business_id' => $businessId, 'name' => $name]);
    }

    /**
     * @return array{0: int, 1: int, 2: float, 3: float} cost cents, sell cents, stock, low-stock alert
     */
    private function numbers(array $input, string $message): array
    {
        $raw = [$input['cost_price'] ?? 0, $input['selling_price'] ?? 0, $input['stock_quantity'] ?? 0, $input['low_stock_alert'] ?? 5];
        foreach ($raw as $value) {
            if (! is_numeric($value) || ! is_finite((float) $value) || (float) $value < 0) {
                throw new BusinessRuleException($message);
            }
        }

        return [Money::cents($raw[0]), Money::cents($raw[1]), Money::qty($raw[2]), Money::qty($raw[3])];
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function guardUnique(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (UniqueConstraintViolationException) {
            throw new BusinessRuleException('A product with this SKU or barcode already exists');
        }
    }
}
