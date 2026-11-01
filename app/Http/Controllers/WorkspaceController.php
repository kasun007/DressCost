<?php

namespace App\Http\Controllers;

use App\Costing\SummaryPresenter;
use App\Http\Requests\WorkspaceRequest;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function __construct(private readonly SummaryPresenter $summaries) {}

    /**
     * Show the user's workspaces with their project and item counts.
     */
    public function index(Request $request): Response
    {
        $workspaces = $request->user()->workspaces()
            ->with(['projects.items.materials', 'projects.items.costs'])
            ->orderBy('name')
            ->get();

        return Inertia::render('workspaces/index', [
            'workspaces' => $workspaces->map(fn (Workspace $workspace) => $this->summaries->workspace($workspace)),
        ]);
    }

    /**
     * Store a new workspace.
     */
    public function store(WorkspaceRequest $request): RedirectResponse
    {
        $workspace = $request->user()->workspaces()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace created.')]);

        return to_route('workspaces.show', $workspace);
    }

    /**
     * Show a workspace with its projects.
     */
    public function show(Workspace $workspace): Response
    {
        Gate::authorize('view', $workspace);

        $workspace->load([
            'projects' => fn ($query) => $query->orderBy('name'),
            'projects.items.materials',
            'projects.items.costs',
        ]);

        return Inertia::render('workspaces/show', [
            'workspace' => $workspace->only('id', 'name', 'description'),
            'projects' => $workspace->projects->map(fn (Project $project) => $this->summaries->project($project)),
        ]);
    }

    /**
     * Update a workspace.
     */
    public function update(WorkspaceRequest $request, Workspace $workspace): RedirectResponse
    {
        $workspace->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace updated.')]);

        return to_route('workspaces.show', $workspace);
    }

    /**
     * Delete a workspace with its projects and items.
     */
    public function destroy(Workspace $workspace): RedirectResponse
    {
        Gate::authorize('delete', $workspace);

        $workspace->deleteWithContents();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace deleted.')]);

        return to_route('workspaces.index');
    }
}
