<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompareRequest extends FormRequest
{
    public const KINDS = ['workspace', 'project', 'item'];

    /**
     * Get the validation rules that apply to the request. Whether `a` and `b`
     * belong to the user is checked against their own data in the controller.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kind' => ['nullable', Rule::in(self::KINDS)],
            'a' => ['nullable', 'integer'],
            'b' => ['nullable', 'integer'],
        ];
    }

    /**
     * What is being compared: workspaces, projects or items.
     */
    public function kind(): string
    {
        return $this->string('kind', 'project')->toString();
    }
}
