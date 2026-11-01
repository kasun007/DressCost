<?php

namespace App\Http\Controllers;

use App\Enums\ItemType;
use App\Http\Requests\ItemRequest;
use App\Models\Item;
use App\Models\ItemCost;
use App\Models\Material;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    /**
     * Show the form for adding an item to a project.
     */
    public function create(Request $request, Project $project): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('items/create', [
            ...$this->formProps($request, $project),
            'savedItems' => $this->savedItems($request),
        ]);
    }

    /**
     * Store a new item with its materials and costs.
     */
    public function store(ItemRequest $request, Project $project): RedirectResponse
    {
        $this->save($project->items()->make(), $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Item added.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Show the form for editing an item.
     */
    public function edit(Request $request, Project $project, Item $item): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('items/edit', [
            ...$this->formProps($request, $project),
            'item' => [
                ...$item->only('id', 'name', 'type', 'profit_margin', 'quantity_small', 'quantity_medium', 'quantity_large', 'quantity_xl'),
                'materials' => $item->materials->map(fn (Material $material) => [
                    'material_id' => $material->id,
                    'quantity' => $material->pivot->quantity,
                ]),
                'costs' => $item->costs()->oldest('id')->get(['name', 'amount']),
            ],
        ]);
    }

    /**
     * Update an item with its materials and costs.
     */
    public function update(ItemRequest $request, Project $project, Item $item): RedirectResponse
    {
        $this->save($item, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Item updated.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Delete an item.
     */
    public function destroy(Project $project, Item $item): RedirectResponse
    {
        Gate::authorize('update', $project);

        $item->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Item deleted.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Save the item's own fields, then replace its materials and cost lines.
     */
    private function save(Item $item, ItemRequest $request): void
    {
        DB::transaction(function () use ($item, $request) {
            $item->fill($request->itemAttributes())->save();
            $item->materials()->sync($request->materialQuantities());
            $item->costs()->delete();
            $item->costs()->createMany($request->costLines());
        });
    }

    /**
     * Items the user has saved before, one per name (the most recently costed),
     * with their full costing so a new item can start as a copy.
     *
     * @return array<int, array<string, mixed>>
     */
    private function savedItems(Request $request): array
    {
        return Item::query()
            ->whereHas('project', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with(['project.workspace', 'materials', 'costs' => fn ($query) => $query->oldest('id')])
            ->latest()
            ->latest('id')
            ->get()
            ->unique(fn (Item $item) => mb_strtolower($item->name))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->map(fn (Item $item) => [
                ...$item->only('id', 'name', 'type', 'profit_margin', 'quantity_small', 'quantity_medium', 'quantity_large', 'quantity_xl'),
                'project' => $item->project->workspace->name.' › '.$item->project->name,
                'materials' => $item->materials->map(fn (Material $material) => [
                    'material_id' => $material->id,
                    'quantity' => $material->pivot->quantity,
                ])->all(),
                'costs' => $item->costs->map->only('name', 'amount')->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Props shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formProps(Request $request, Project $project): array
    {
        return [
            'project' => $project->only('id', 'name'),
            'workspace' => $project->workspace->only('id', 'name'),
            'materials' => $request->user()->materials()
                ->orderBy('name')
                ->get(['id', 'name', 'unit', 'unit_price']),
            'types' => array_column(ItemType::cases(), 'value'),
            'costTypes' => ItemCost::typesFor($request->user()),
        ];
    }
}
