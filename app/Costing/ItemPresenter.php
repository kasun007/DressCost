<?php

namespace App\Costing;

use App\Models\Item;
use App\Models\Material;

/**
 * An item's full costing for display: materials, other costs, quantities by
 * size, and its cost per piece and for all pieces. Shared by the project page
 * and the public share page so both always show the same figures.
 * Expects `materials` and `costs` to be loaded.
 */
class ItemPresenter
{
    public function __construct(private readonly ItemCostCalculator $calculator) {}

    /**
     * @return array<string, mixed>
     */
    public function present(Item $item): array
    {
        return [
            ...$item->only('id', 'name', 'type', 'profit_margin'),
            'saved_on' => $item->created_at->toDateString(),
            'quantities' => $item->quantities(),
            'quantity' => $item->quantity(),
            'materials' => $item->materials->map(fn (Material $material) => [
                'id' => $material->id,
                'name' => $material->name,
                'unit' => $material->unit->value,
                'unit_price' => $material->unit_price,
                'quantity' => $material->pivot->quantity,
                'cost' => round($material->pivot->quantity * $material->unit_price, 2),
            ])->all(),
            'costs' => $item->costs->map->only('id', 'name', 'amount')->all(),
            'cost' => $this->calculator->calculate($item)->toArray(),
            'all_pieces' => $this->calculator->forAllPieces($item)->toArray(),
        ];
    }
}
