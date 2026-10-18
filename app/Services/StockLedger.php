<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Product stock changes and their audit trail (stock_movements).
 * Movement quantities are signed: positive adds stock, negative removes it.
 */
class StockLedger
{
    /**
     * Lock product rows in a stable order (so concurrent sales never
     * deadlock) and return them keyed by id.
     *
     * @param  list<string>  $productIds
     * @return Collection<string, Product>
     */
    public function lockProducts(string $businessId, array $productIds, bool $activeOnly = false): Collection
    {
        $ids = array_values(array_unique(array_filter($productIds)));
        sort($ids);

        if ($ids === []) {
            return collect();
        }

        return Product::query()
            ->where('business_id', $businessId)
            ->whereIn('id', $ids)
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    public function record(
        string $businessId,
        string $productId,
        string $type,
        float $quantity,
        ?string $referenceId,
        ?string $note,
        ?string $userId,
    ): void {
        StockMovement::query()->create([
            'business_id' => $businessId,
            'product_id' => $productId,
            'movement_type' => $type,
            'quantity' => Money::qty($quantity),
            'reference_id' => $referenceId,
            'note' => $note,
            'created_by' => $userId,
        ]);
    }

    /**
     * Change a product's stock without writing a movement.
     */
    public function change(Product $product, float $delta): void
    {
        $product->stock_quantity = Money::qty((float) $product->stock_quantity + $delta);
        $product->save();
    }

    /**
     * Change stock and write the matching movement.
     */
    public function move(Product $product, float $delta, string $type, ?string $referenceId, ?string $note, ?string $userId): void
    {
        $this->change($product, $delta);
        $this->record($product->business_id, $product->id, $type, $delta, $referenceId, $note, $userId);
    }

    /**
     * @param  Collection<string, Product>  $products
     */
    public static function find(Collection $products, string $productId, string $message): Product
    {
        return $products->get($productId) ?? throw new BusinessRuleException($message);
    }
}
