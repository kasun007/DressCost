<?php

namespace App\Http\Requests;

use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Used for both creating and updating a workspace.
 */
class WorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');

        return ! $workspace instanceof Workspace || $this->user()->can('update', $workspace);
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
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
