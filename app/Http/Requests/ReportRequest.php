<?php

namespace App\Http\Requests;

use App\Costing\ReportFilters;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workspace_id' => [
                'nullable',
                'integer',
                Rule::exists('workspaces', 'id')->where('user_id', $this->user()->id),
            ],
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where('user_id', $this->user()->id),
            ],
            'cost_type' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    public function filters(): ReportFilters
    {
        return new ReportFilters(
            workspaceId: $this->filled('workspace_id') ? $this->integer('workspace_id') : null,
            projectId: $this->filled('project_id') ? $this->integer('project_id') : null,
            costType: $this->filled('cost_type') ? $this->string('cost_type')->toString() : null,
            from: $this->filled('from') ? CarbonImmutable::parse($this->string('from')->toString())->startOfDay() : null,
            // Items are dated by when they were saved, so "to" includes that whole day.
            to: $this->filled('to') ? CarbonImmutable::parse($this->string('to')->toString())->endOfDay() : null,
        );
    }
}
