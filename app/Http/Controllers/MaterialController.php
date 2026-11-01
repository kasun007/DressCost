<?php

namespace App\Http\Controllers;

use App\Enums\MaterialUnit;
use App\Http\Requests\MaterialRequest;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MaterialController extends Controller
{
    /**
     * Show the user's materials.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('materials/index', [
            'materials' => $request->user()->materials()
                ->withCount('items')
                ->orderBy('name')
                ->get(),
            'units' => array_column(MaterialUnit::cases(), 'value'),
        ]);
    }

    /**
     * Store a new material.
     */
    public function store(MaterialRequest $request): RedirectResponse
    {
        $request->user()->materials()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Material added.')]);

        return to_route('materials.index');
    }

    /**
     * Update a material.
     */
    public function update(MaterialRequest $request, Material $material): RedirectResponse
    {
        $material->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Material updated.')]);

        return to_route('materials.index');
    }

    /**
     * Delete a material that no item uses.
     */
    public function destroy(Material $material): RedirectResponse
    {
        Gate::authorize('delete', $material);

        if ($material->items()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This material is used by an item. Remove it from those items first.'),
            ]);

            return to_route('materials.index');
        }

        $material->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Material deleted.')]);

        return to_route('materials.index');
    }
}
