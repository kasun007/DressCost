<?php

namespace App\Http\Controllers;

use App\Costing\ItemCostCalculator;
use App\Costing\ItemPresenter;
use App\Http\Requests\ProjectRequest;
use App\Models\Item;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ItemCostCalculator $calculator,
        private readonly ItemPresenter $items,
    ) {}

    /**
     * Store a new project in a workspace.
     */
    public function store(ProjectRequest $request): RedirectResponse
    {
        $project = $request->user()->projects()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project created.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Show a project with each item's full cost breakdown.
     */
    public function show(Request $request, Project $project): Response
    {
        Gate::authorize('view', $project);

        $project->load([
            'workspace',
            'items' => fn ($query) => $query->latest()->orderBy('name'),
            'items.materials' => fn ($query) => $query->orderBy('name'),
            'items.costs' => fn ($query) => $query->oldest('id'),
        ]);

        return Inertia::render('projects/show', [
            'project' => $project->only('id', 'workspace_id', 'name', 'description'),
            'workspace' => $project->workspace->only('id', 'name'),
            'items' => $project->items->map(fn (Item $item) => $this->items->present($item)),
            'totalCost' => $this->calculator->total($project->items)->totalCost,
            'workspaceOptions' => $request->user()->workspaces()->orderBy('name')->get(['id', 'name']),
            'shareUrl' => $project->share_token !== null ? route('shared.projects.show', $project->share_token) : null,
        ]);
    }

    /**
     * Update a project, or move it to another workspace.
     */
    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project updated.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Delete a project and its items.
     */
    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $workspaceId = $project->workspace_id;
        $project->deleteWithItems();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project deleted.')]);

        return to_route('workspaces.show', $workspaceId);
    }
}
