<?php

namespace App\Http\Controllers;

use App\Costing\ItemCostCalculator;
use App\Costing\ItemPresenter;
use App\Models\Item;
use App\Models\Project;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public, read-only view of a shared project. Anyone with the link can
 * see its full costing without logging in; nothing can be changed.
 */
class SharedProjectController extends Controller
{
    public function __construct(
        private readonly ItemCostCalculator $calculator,
        private readonly ItemPresenter $items,
    ) {}

    public function show(string $token): Response
    {
        $project = Project::where('share_token', $token)
            ->with([
                'workspace',
                'items' => fn ($query) => $query->latest()->orderBy('name'),
                'items.materials' => fn ($query) => $query->orderBy('name'),
                'items.costs' => fn ($query) => $query->oldest('id'),
            ])
            ->firstOrFail();

        $response = Inertia::render('shared/project', [
            'project' => $project->only('name', 'description'),
            'workspace' => $project->workspace->name,
            'items' => $project->items->map(fn (Item $item) => $this->items->present($item)),
            'totalCost' => $this->calculator->total($project->items)->totalCost,
            'totalPieces' => $project->items->sum(fn (Item $item) => $item->quantity()),
        ])->toResponse(request());

        // Shared links are private to whoever has them; keep them out of search engines.
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
