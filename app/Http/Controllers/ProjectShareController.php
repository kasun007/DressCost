<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Turns a project's read-only share link on (or renews it) and off.
 */
class ProjectShareController extends Controller
{
    /**
     * Create a share link. Any previous link stops working.
     */
    public function store(Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $project->share();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Share link created.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Stop sharing: the link stops working straight away.
     */
    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $project->stopSharing();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sharing stopped. The link no longer works.')]);

        return to_route('projects.show', $project);
    }
}
