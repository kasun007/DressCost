<?php

namespace App\Costing;

use App\Models\Item;
use App\Models\ItemCost;
use App\Models\Material;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Builds the cost report for a user's workspaces, projects and items,
 * split into costing categories.
 *
 * A category is either "Materials" (all material costs of an item) or the
 * costing type of a cost line, such as "Labour". Each item contributes one
 * entry per category, dated by when the item was saved.
 *
 * The report looks at one level at a time: with no focus it is split by
 * workspace; focused on a workspace it is split by its projects; focused on
 * a project it shows its items. Costs are also totalled by the year items
 * were saved.
 *
 * @phpstan-type Entry array{item_id: int, project_id: int, month: string, category: string, amount: float}
 */
class CostReport
{
    public const MATERIALS = 'Materials';

    public function __construct(private readonly ItemCostCalculator $calculator) {}

    /**
     * @return array{
     *     filters: array<string, int|string|null>,
     *     options: array{workspaces: array<int, array<string, mixed>>, projects: array<int, array<string, mixed>>, categories: array<int, string>},
     *     scope: array<string, mixed>|null,
     *     summary: array<string, int|float|null>,
     *     categories: array<int, array{name: string, amount: float}>,
     *     tree: array<int, array<string, mixed>>,
     *     projects: array<int, array<string, mixed>>,
     *     years: array<int, array{year: string, items_count: int, total_cost: float}>,
     *     materials: array<int, array<string, mixed>>,
     * }
     */
    public function for(User $user, ReportFilters $filters = new ReportFilters): array
    {
        $workspaces = $this->workspacesOf($user);
        $projects = $workspaces->flatMap(fn (Workspace $workspace) => $workspace->projects);
        $categories = $this->categoriesFor($user);
        $typeFiltered = $filters->costType !== null;

        // A chosen project wins over a chosen workspace.
        $project = $filters->projectId !== null ? $projects->firstWhere('id', $filters->projectId) : null;
        $workspace = $workspaces->firstWhere('id', $project->workspace_id ?? $filters->workspaceId);

        $projectIds = match (true) {
            $project !== null => [$project->id],
            $workspace !== null => $workspace->projects->modelKeys(),
            default => null,
        };

        $items = $projects
            ->flatMap(fn (Project $p) => $p->items)
            ->filter(fn (Item $item) => $this->matches($item, $filters, $projectIds))
            ->values();

        $entries = $this->entriesFor($items, $categories)
            ->when($typeFiltered, fn (Collection $entries) => $entries->where(
                'category',
                $this->canonical((string) $filters->costType, $categories),
            ))
            ->values();

        $nodes = match (true) {
            $project !== null => collect([$this->projectNode($project, $items, $entries, $typeFiltered)]),
            $workspace !== null => $workspace->projects->map(fn (Project $p) => $this->projectNode($p, $items, $entries, $typeFiltered)),
            default => $workspaces->map(fn (Workspace $w) => $this->workspaceNode($w, $items, $entries, $typeFiltered)),
        };
        $nodes = $nodes->toBase()->filter(fn (array $node) => $node['items_count'] > 0)->values();

        $projectName = fn (Project $p) => $workspaces->firstWhere('id', $p->workspace_id)?->name.' › '.$p->name;

        return [
            'filters' => $filters->toArray(),
            'options' => [
                'workspaces' => $workspaces->map->only('id', 'name')->values()->all(),
                'projects' => $projects->map(fn (Project $p) => [
                    'id' => $p->id,
                    'name' => $projectName($p),
                    'short_name' => $p->name,
                    'workspace_id' => $p->workspace_id,
                ])->values()->all(),
                'categories' => $categories,
            ],
            'scope' => $this->scope($workspace, $project),
            'summary' => [
                'projects_count' => $items->pluck('project_id')->unique()->count(),
                ...$this->totals($items, $entries, $typeFiltered),
            ],
            'categories' => $this->totalsByCategory($entries),
            'tree' => $nodes->all(),
            'projects' => $projects
                ->map(fn (Project $p) => $this->projectBreakdown(
                    ['id' => $p->id, 'name' => $projectName($p)],
                    $items->where('project_id', $p->id),
                    $entries->where('project_id', $p->id),
                    $typeFiltered,
                ))
                ->filter(fn (array $row) => $row['items_count'] > 0)
                ->values()
                ->all(),
            'years' => $this->totalsByYear($entries),
            'materials' => ! $typeFiltered || $this->canonical((string) $filters->costType, $categories) === self::MATERIALS
                ? $this->materialUsage($items)
                : [],
        ];
    }

    /**
     * Item count, total cost and costs by category for any set of items.
     * Used to compare workspaces, projects or items side by side.
     *
     * @param  iterable<Item>  $items  with `materials` and `costs` loaded
     * @return array{items_count: int, total_cost: float, categories: array<int, array{name: string, amount: float}>}
     */
    public function totalsFor(User $user, iterable $items): array
    {
        $items = collect($items);
        $entries = $this->entriesFor($items, $this->categoriesFor($user));

        return [
            'items_count' => $items->count(),
            'total_cost' => round((float) $entries->sum('amount'), 2),
            'categories' => $this->totalsByCategory($entries),
        ];
    }

    /**
     * Every costing category in a fixed order: Materials first, then costing types.
     *
     * @return array<int, string>
     */
    public function categoriesFor(User $user): array
    {
        return [self::MATERIALS, ...ItemCost::typesFor($user)];
    }

    /**
     * The user's workspaces with projects and items, ready for costing.
     *
     * @return EloquentCollection<int, Workspace>
     */
    public function workspacesOf(User $user): EloquentCollection
    {
        return $user->workspaces()
            ->with([
                'projects' => fn ($query) => $query->orderBy('name'),
                'projects.items.materials',
                'projects.items.costs',
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function scope(?Workspace $workspace, ?Project $project): ?array
    {
        if ($workspace === null) {
            return null;
        }

        $path = [['kind' => 'workspace', 'id' => $workspace->id, 'name' => $workspace->name]];

        if ($project !== null) {
            $path[] = ['kind' => 'project', 'id' => $project->id, 'name' => $project->name];
        }

        return [
            'kind' => $project !== null ? 'project' : 'workspace',
            'name' => $project !== null ? $project->name : $workspace->name,
            'path' => $path,
        ];
    }

    /**
     * @param  array<int, int>|null  $projectIds  null means every project
     */
    private function matches(Item $item, ReportFilters $filters, ?array $projectIds): bool
    {
        return ($projectIds === null || in_array($item->project_id, $projectIds, true))
            && ($filters->from === null || $item->created_at->gte($filters->from))
            && ($filters->to === null || $item->created_at->lte($filters->to));
    }

    /**
     * Match a costing type to its known spelling, ignoring case.
     *
     * @param  array<int, string>  $categories
     */
    private function canonical(string $name, array $categories): string
    {
        foreach ($categories as $category) {
            if (mb_strtolower($category) === mb_strtolower($name)) {
                return $category;
            }
        }

        return $name;
    }

    /**
     * @param  Collection<int, Item>  $items
     * @param  array<int, string>  $categories
     * @return Collection<int, Entry>
     */
    private function entriesFor(Collection $items, array $categories): Collection
    {
        return $items->toBase()->flatMap(fn (Item $item) => $this->entries($item, $categories))->values();
    }

    /**
     * One entry per costing category of the item, for all of its pieces.
     *
     * @param  array<int, string>  $categories
     * @return array<int, Entry>
     */
    private function entries(Item $item, array $categories): array
    {
        $entry = fn (string $category, float $amount) => [
            'item_id' => $item->id,
            'project_id' => $item->project_id,
            'month' => $item->created_at->format('Y-m'),
            'category' => $category,
            'amount' => $amount,
        ];

        // Every piece of the item counts.
        $pieces = $item->quantity();
        $entries = [];
        $materialCost = $this->calculator->calculate($item)->materialCost * $pieces;

        if ($materialCost > 0) {
            $entries[] = $entry(self::MATERIALS, round($materialCost, 2));
        }

        foreach ($item->costs as $cost) {
            $entries[] = $entry($this->canonical($cost->name, $categories), round($cost->amount * $pieces, 2));
        }

        return $entries;
    }

    /**
     * Totals for a set of items. Selling price and profit only make sense
     * across all categories, so they are null when filtering by costing type.
     *
     * @param  Collection<int, Item>  $items
     * @param  Collection<int, Entry>  $entries
     * @return array{items_count: int, total_cost: float, selling_price: float|null, profit: float|null}
     */
    private function totals(Collection $items, Collection $entries, bool $typeFiltered): array
    {
        if ($typeFiltered) {
            return [
                'items_count' => $entries->pluck('item_id')->unique()->count(),
                'total_cost' => round((float) $entries->sum('amount'), 2),
                'selling_price' => null,
                'profit' => null,
            ];
        }

        $breakdown = $this->calculator->total($items);

        return [
            'items_count' => $items->count(),
            'total_cost' => $breakdown->totalCost,
            'selling_price' => $breakdown->sellingPrice,
            'profit' => $breakdown->profit,
        ];
    }

    /**
     * @param  Collection<int, Entry>  $entries
     * @return array<int, array{name: string, amount: float}>
     */
    private function totalsByCategory(Collection $entries): array
    {
        return $entries
            ->groupBy('category')
            ->map(fn (Collection $group, string $name) => [
                'name' => $name,
                'amount' => round((float) $group->sum('amount'), 2),
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();
    }

    /**
     * A workspace with its projects, costs rolled up.
     *
     * @param  Collection<int, Item>  $items
     * @param  Collection<int, Entry>  $entries
     * @return array<string, mixed>
     */
    private function workspaceNode(Workspace $workspace, Collection $items, Collection $entries, bool $typeFiltered): array
    {
        $projectIds = $workspace->projects->modelKeys();

        return [
            ...$this->nodeTotals($entries->whereIn('project_id', $projectIds), $items->whereIn('project_id', $projectIds), $typeFiltered),
            'kind' => 'workspace',
            'id' => $workspace->id,
            'name' => $workspace->name,
            'children' => $workspace->projects
                ->map(fn (Project $project) => $this->projectNode($project, $items, $entries, $typeFiltered))
                ->filter(fn (array $node) => $node['items_count'] > 0)
                ->values()
                ->all(),
            'items' => [],
        ];
    }

    /**
     * A project with its items.
     *
     * @param  Collection<int, Item>  $items
     * @param  Collection<int, Entry>  $entries
     * @return array<string, mixed>
     */
    private function projectNode(Project $project, Collection $items, Collection $entries, bool $typeFiltered): array
    {
        $projectEntries = $entries->where('project_id', $project->id);
        $projectItems = $items->where('project_id', $project->id);

        return [
            ...$this->nodeTotals($projectEntries, $projectItems, $typeFiltered),
            'kind' => 'project',
            'id' => $project->id,
            'name' => $project->name,
            'children' => [],
            'items' => $this->itemRows($projectItems, $projectEntries),
        ];
    }

    /**
     * @param  Collection<int, Entry>  $entries
     * @param  Collection<int, Item>  $items
     * @return array{items_count: int, total_cost: float, categories: array<int, array{name: string, amount: float}>}
     */
    private function nodeTotals(Collection $entries, Collection $items, bool $typeFiltered): array
    {
        return [
            'items_count' => $typeFiltered ? $entries->pluck('item_id')->unique()->count() : $items->count(),
            'total_cost' => round((float) $entries->sum('amount'), 2),
            'categories' => $this->totalsByCategory($entries),
        ];
    }

    /**
     * @param  array{id: int, name: string}  $project  the project's id and full name
     * @param  Collection<int, Item>  $items
     * @param  Collection<int, Entry>  $entries
     * @return array<string, mixed>
     */
    private function projectBreakdown(array $project, Collection $items, Collection $entries, bool $typeFiltered): array
    {
        return [
            'id' => $project['id'],
            'name' => $project['name'],
            'last_saved_on' => $items->max('created_at')?->toDateString(),
            ...$this->totals($items, $entries, $typeFiltered),
            'categories' => $this->totalsByCategory($entries),
            'items' => $this->itemRows($items, $entries),
        ];
    }

    /**
     * Items with their cost in this view (only the filtered costing type, if any).
     *
     * @param  Collection<int, Item>  $items
     * @param  Collection<int, Entry>  $entries
     * @return array<int, array{id: int, name: string, type: string, saved_on: string, total_cost: float}>
     */
    private function itemRows(Collection $items, Collection $entries): array
    {
        $itemTotals = $entries->groupBy('item_id')->map(fn (Collection $group) => round((float) $group->sum('amount'), 2));

        return $items
            ->filter(fn (Item $item) => $itemTotals->has($item->id))
            ->sortByDesc('created_at')
            ->map(fn (Item $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'type' => $item->type->value,
                'saved_on' => $item->created_at->toDateString(),
                'total_cost' => (float) $itemTotals->get($item->id, 0.0),
            ])
            ->values()
            ->all();
    }

    /**
     * Item count and total cost per year the items were saved, oldest first.
     *
     * @param  Collection<int, Entry>  $entries
     * @return array<int, array{year: string, items_count: int, total_cost: float}>
     */
    private function totalsByYear(Collection $entries): array
    {
        return $entries
            ->groupBy(fn (array $entry) => substr($entry['month'], 0, 4))
            ->map(fn (Collection $group, string $year) => [
                'year' => $year,
                'items_count' => $group->pluck('item_id')->unique()->count(),
                'total_cost' => round((float) $group->sum('amount'), 2),
            ])
            ->sortKeys()
            ->values()
            ->all();
    }

    /**
     * How much of each material the items use (for all their pieces), most expensive first.
     *
     * @param  Collection<int, Item>  $items
     * @return array<int, array{id: int, name: string, unit: string, unit_price: float, quantity: float, cost: float}>
     */
    private function materialUsage(Collection $items): array
    {
        return $items
            ->flatMap(fn (Item $item) => $item->materials->map(fn (Material $material) => [
                'material' => $material,
                'quantity' => $material->pivot->quantity * $item->quantity(),
            ]))
            ->groupBy(fn (array $use) => $use['material']->id)
            ->map(function (Collection $uses) {
                /** @var Material $material */
                $material = $uses->first()['material'];
                $quantity = (float) $uses->sum('quantity');

                return [
                    'id' => $material->id,
                    'name' => $material->name,
                    'unit' => $material->unit->value,
                    'unit_price' => $material->unit_price,
                    'quantity' => round($quantity, 3),
                    'cost' => round($quantity * $material->unit_price, 2),
                ];
            })
            ->sortByDesc('cost')
            ->values()
            ->all();
    }
}
