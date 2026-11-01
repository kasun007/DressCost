<?php

namespace App\Http\Requests;

use App\Enums\MaterialUnit;
use App\Models\Material;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used for both creating and updating a material.
 */
class MaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $material = $this->route('material');

        return ! $material instanceof Material || $this->user()->can('update', $material);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $material = $this->route('material');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('materials')
                    ->where('user_id', $this->user()->id)
                    ->ignore($material instanceof Material ? $material->id : null),
            ],
            'unit' => ['required', Rule::enum(MaterialUnit::class)],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }
}
