<?php

namespace Tests\Unit;

use App\Costing\ItemCostCalculator;
use App\Models\Item;
use App\Models\ItemCost;
use App\Models\ItemMaterial;
use App\Models\Material;
use Tests\TestCase;

class ItemCostCalculatorTest extends TestCase
{
    public function test_it_calculates_material_and_other_costs_and_selling_price()
    {
        $item = $this->makeItem(
            margin: 20,
            materials: [[2.5, 800], [6, 15.5]],
            costs: ['Labour' => 1500, 'Overhead' => 250],
        );

        $cost = (new ItemCostCalculator)->calculate($item);

        $this->assertSame(2093.0, $cost->materialCost);
        $this->assertSame(1750.0, $cost->otherCost);
        $this->assertSame(3843.0, $cost->totalCost);
        $this->assertSame(4611.6, $cost->sellingPrice);
        $this->assertSame(768.6, $cost->profit);
    }

    public function test_an_item_without_materials_costs_only_its_other_costs()
    {
        $item = $this->makeItem(margin: 0, materials: [], costs: ['Labour' => 100, 'Embroidery' => 50]);

        $cost = (new ItemCostCalculator)->calculate($item);

        $this->assertSame(0.0, $cost->materialCost);
        $this->assertSame(150.0, $cost->totalCost);
        $this->assertSame(150.0, $cost->sellingPrice);
        $this->assertSame(0.0, $cost->profit);
    }

    public function test_an_item_without_any_costs_is_free()
    {
        $cost = (new ItemCostCalculator)->calculate($this->makeItem(margin: 50, materials: [], costs: []));

        $this->assertSame(0.0, $cost->totalCost);
        $this->assertSame(0.0, $cost->sellingPrice);
    }

    public function test_amounts_are_rounded_to_two_decimal_places()
    {
        $item = $this->makeItem(margin: 33.33, materials: [[1.333, 10]], costs: []);

        $cost = (new ItemCostCalculator)->calculate($item);

        $this->assertSame(13.33, $cost->materialCost);
        $this->assertSame(17.77, $cost->sellingPrice);
    }

    public function test_all_pieces_multiply_the_cost_of_one_piece()
    {
        $item = $this->makeItem(margin: 10, materials: [[2, 50]], costs: ['Labour' => 100]);
        $item->fill(['quantity_small' => 3, 'quantity_medium' => 5, 'quantity_large' => 0, 'quantity_xl' => 2]);

        $calculator = new ItemCostCalculator;

        $this->assertSame(200.0, $calculator->calculate($item)->totalCost);
        $this->assertSame(10, $item->quantity());
        $this->assertSame(2000.0, $calculator->forAllPieces($item)->totalCost);
        $this->assertSame(2200.0, $calculator->forAllPieces($item)->sellingPrice);
        $this->assertSame(2000.0, $calculator->total([$item])->totalCost);
    }

    public function test_it_totals_several_items()
    {
        $items = [
            $this->makeItem(margin: 10, materials: [[1, 50]], costs: ['Labour' => 100]),
            $this->makeItem(margin: 0, materials: [], costs: ['Labour' => 200, 'Overhead' => 20]),
        ];

        $total = (new ItemCostCalculator)->total($items);

        $this->assertSame(50.0, $total->materialCost);
        $this->assertSame(320.0, $total->otherCost);
        $this->assertSame(370.0, $total->totalCost);
        $this->assertSame(385.0, $total->sellingPrice);
        $this->assertSame(15.0, $total->profit);
    }

    /**
     * @param  array<int, array{0: float|int, 1: float|int}>  $materials  [quantity, unit price] pairs
     * @param  array<string, float|int>  $costs  costing type => amount
     */
    private function makeItem(float $margin, array $materials, array $costs): Item
    {
        $item = new Item(['profit_margin' => $margin, 'quantity_medium' => 1]);

        $item->setRelation('materials', collect($materials)->map(function (array $row) {
            $material = new Material(['unit_price' => $row[1]]);
            $material->setRelation('pivot', new ItemMaterial(['quantity' => $row[0]]));

            return $material;
        }));

        $item->setRelation('costs', collect($costs)->map(
            fn ($amount, $name) => new ItemCost(['name' => $name, 'amount' => $amount]),
        )->values());

        return $item;
    }
}
