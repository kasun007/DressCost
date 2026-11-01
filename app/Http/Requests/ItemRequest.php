<?php

namespace App\Http\Requests;

use App\Enums\ItemType;
use App\Models\Item;
use App\Models\Project;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Used for both creating and updating an item within a project.
 */
class ItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && $this->user()->can('update', $project);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(ItemType::class)],
            'profit_margin' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'quantity_small' => ['required', 'integer', 'min:0', 'max:100000'],
            'quantity_medium' => ['required', 'integer', 'min:0', 'max:100000'],
            'quantity_large' => ['required', 'integer', 'min:0', 'max:100000'],
            'quantity_xl' => ['required', 'integer', 'min:0', 'max:100000'],
            'materials' => ['array'],
            'materials.*.material_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('materials', 'id')->where('user_id', $this->user()->id),
            ],
            'materials.*.quantity' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
            'costs' => ['array'],
            'costs.*.name' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
            'costs.*.amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    /**
     * At least one piece must be made, in any size.
     *
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $total = collect(array_keys(Item::SIZES))
                    ->sum(fn (string $size) => (int) $this->input("quantity_{$size}", 0));

                if ($total < 1) {
                    $validator->errors()->add('quantity', 'Enter how many pieces to make in at least one size.');
                }
            },
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'materials.*.material_id' => 'material',
            'materials.*.quantity' => 'quantity',
            'costs.*.name' => 'costing type',
            'quantity_small' => 'Small quantity',
            'quantity_medium' => 'Medium quantity',
            'quantity_large' => 'Large quantity',
            'quantity_xl' => 'XL quantity',
            'costs.*.amount' => 'amount',
        ];
    }

    /**
     * The item's own columns, without its materials and costs.
     *
     * @return array<string, mixed>
     */
    public function itemAttributes(): array
    {
        return $this->safe()->except(['materials', 'costs']);
    }

    /**
     * The item's non-material cost lines, ready for `createMany()`.
     *
     * @return array<int, array{name: string, amount: float}>
     */
    public function costLines(): array
    {
        /** @var array<int, array{name: string, amount: int|float|string}> $costs */
        $costs = $this->validated('costs', []);

        return array_map(fn (array $cost) => [
            'name' => $cost['name'],
            'amount' => (float) $cost['amount'],
        ], array_values($costs));
    }

    /**
     * Material quantities keyed by material id, ready for `sync()`.
     *
     * @return array<int, array{quantity: float}>
     */
    public function materialQuantities(): array
    {
        /** @var array<int, array{material_id: int|string, quantity: int|float|string}> $materials */
        $materials = $this->validated('materials', []);

        return collect($materials)
            ->mapWithKeys(fn (array $row) => [
                (int) $row['material_id'] => ['quantity' => (float) $row['quantity']],
            ])
            ->all();
    }
}
