<?php

namespace App\Costing;

use Illuminate\Contracts\Support\Arrayable;

/**
 * An immutable cost breakdown for one item or a group of items.
 *
 * @implements Arrayable<string, float>
 */
final readonly class CostBreakdown implements Arrayable
{
    public float $materialCost;

    /** Labour, overhead and any other non-material costs. */
    public float $otherCost;

    public float $totalCost;

    public float $sellingPrice;

    public float $profit;

    public function __construct(float $materialCost, float $otherCost, float $sellingPrice)
    {
        $this->materialCost = round($materialCost, 2);
        $this->otherCost = round($otherCost, 2);
        $this->totalCost = round($this->materialCost + $this->otherCost, 2);
        $this->sellingPrice = round($sellingPrice, 2);
        $this->profit = round($this->sellingPrice - $this->totalCost, 2);
    }

    public static function zero(): self
    {
        return new self(0, 0, 0);
    }

    public function plus(self $other): self
    {
        return new self(
            $this->materialCost + $other->materialCost,
            $this->otherCost + $other->otherCost,
            $this->sellingPrice + $other->sellingPrice,
        );
    }

    /**
     * The same breakdown for a number of pieces.
     */
    public function times(int $pieces): self
    {
        return new self(
            $this->materialCost * $pieces,
            $this->otherCost * $pieces,
            $this->sellingPrice * $pieces,
        );
    }

    /**
     * @return array<string, float>
     */
    public function toArray(): array
    {
        return [
            'material_cost' => $this->materialCost,
            'other_cost' => $this->otherCost,
            'total_cost' => $this->totalCost,
            'selling_price' => $this->sellingPrice,
            'profit' => $this->profit,
        ];
    }
}
