<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used for both creating and updating a project. Moving a project to another
 * workspace is done by changing its workspace.
 */
class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return ! $project instanceof Project || $this->user()->can('update', $project);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workspace_id' => [
                'required',
                'integer',
                Rule::exists('workspaces', 'id')->where('user_id', $this->user()->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['workspace_id' => 'workspace'];
    }
}
