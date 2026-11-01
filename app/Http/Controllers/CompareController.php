<?php

namespace App\Http\Controllers;

use App\Costing\CostReport;
use App\Http\Requests\CompareRequest;
use App\Models\Item;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CompareController extends Controller
{
    public function __construct(private readonly CostReport $report) {}

    /**
     * Compare two workspaces, two projects or two items side by side.
     * With nothing chosen yet, the first two of that kind are compared.
     */
    public function __invoke(CompareRequest $request): Response
    {
        $user = $request->user();
        $kind = $request->kind();
        $choices = $this->choices($this->report->workspacesOf($user), $kind);

        $side = function (string $key, int $fallback) use ($request, $choices, $user) {
            $id = $request->filled($key) ? $request->integer($key) : $choices->keys()->get($fallback);

            if ($id === null) {
                return null;
            }

            $choice = $choices->get($id) ?? throw ValidationException::withMessages([
                $key => 'Choose one of your own workspaces, projects or items.',
            ]);

            return [
                'id' => $id,
                'name' => $choice['label'],
                'detail' => $choice['detail'],
                ...$this->report->totalsFor($user, $choice['items']),
            ];
        };

        return Inertia::render('reports/compare', [
            'kind' => $kind,
            'options' => $choices->map(fn (array $choice, int $id) => ['id' => $id, 'name' => $choice['name']])->values(),
            'categories' => $this->report->categoriesFor($user),
            'a' => $side('a', 0),
            'b' => $side('b', 1),
        ]);
    }

    /**
     * What can be compared, keyed by id: a full name for choosing, a short
     * label and detail line for display, and the items it covers.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Workspace>  $workspaces
     * @return Collection<int, array{name: string, label: string, detail: string|null, items: Collection<int, Item>}>
     */
    private function choices(\Illuminate\Database\Eloquent\Collection $workspaces, string $kind): Collection
    {
        $choices = [];

        foreach ($workspaces as $workspace) {
            if ($kind === 'workspace') {
                $choices[$workspace->id] = [
                    'name' => $workspace->name,
                    'label' => $workspace->name,
                    'detail' => null,
                    'items' => $workspace->projects->toBase()->flatMap(fn (Project $project) => $project->items),
                ];

                continue;
            }

            foreach ($workspace->projects as $project) {
                $path = "{$workspace->name} › {$project->name}";

                if ($kind === 'project') {
                    $choices[$project->id] = [
                        'name' => $path,
                        'label' => $project->name,
                        'detail' => $workspace->name,
                        'items' => $project->items->toBase(),
                    ];

                    continue;
                }

                foreach ($project->items as $item) {
                    $savedOn = $item->created_at->format('j M Y');
                    $choices[$item->id] = [
                        'name' => "{$item->name} ({$path}, {$savedOn})",
                        'label' => $item->name,
                        'detail' => "{$path}, saved {$savedOn}",
                        'items' => collect([$item]),
                    ];
                }
            }
        }

        return collect($choices);
    }
}
