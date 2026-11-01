<?php

namespace App\Costing;

use App\Models\Item;
use App\Models\Material;

/**
 * The single place where the pricing rules in docs/requirements.md live.
 */
class ItemCostCalculator
{
    /**
     * Calculate the cost of one piece of an item. Every size costs the same.
     * Expects `materials` and `costs` to be loaded.
     */
    public function calculate(Item $item): CostBreakdown
    {
        $materialCost = round($item->materials->sum(
            fn (Material $material) => $material->pivot->quantity * $material->unit_price,
        ), 2);

        $otherCost = round((float) $item->costs->sum('amount'), 2);

        return new CostBreakdown(
            $materialCost,
            $otherCost,
            ($materialCost + $otherCost) * (1 + $item->profit_margin / 100),
        );
    }

    /**
     * The cost of all the pieces of an item, across every size.
     */
    public function forAllPieces(Item $item): CostBreakdown
    {
        return $this->calculate($item)->times($item->quantity());
    }

    /**
     * Calculate the combined cost of several items, counting every piece.
     *
     * @param  iterable<Item>  $items
     */
    public function total(iterable $items): CostBreakdown
    {
        $total = CostBreakdown::zero();

        foreach ($items as $item) {
            $total = $total->plus($this->forAllPieces($item));
        }

        return $total;
    }
}
