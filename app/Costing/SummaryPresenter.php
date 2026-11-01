<?php

namespace App\Costing;

use App\Models\Project;
use App\Models\Workspace;

/**
 * Card data for workspaces and projects. Workspaces show counts only;
 * projects show their total cost. Selling price and profit belong to items.
 * Expects `projects.items.materials` and `projects.items.costs` (or
 * `items.materials` and `items.costs`) to be loaded.
 */
class SummaryPresenter
{
    public function __construct(private readonly ItemCostCalculator $calculator) {}

    /**
     * @return array<string, mixed>
     */
    public function workspace(Workspace $workspace): array
    {
        return [
            ...$workspace->only('id', 'name', 'description'),
            'projects_count' => $workspace->projects->count(),
            'items_count' => $workspace->projects->sum(fn (Project $project) => $project->items->count()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function project(Project $project): array
    {
        return [
            ...$project->only('id', 'name', 'description'),
            'items_count' => $project->items->count(),
            'total_cost' => $this->calculator->total($project->items)->totalCost,
        ];
    }
}
