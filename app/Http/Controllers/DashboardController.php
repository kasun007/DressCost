<?php

namespace App\Http\Controllers;

use App\Costing\CostReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the total cost, cost by year and the most recently updated projects.
     */
    public function __invoke(Request $request, CostReport $report): Response
    {
        $data = $report->for($request->user());

        return Inertia::render('dashboard', [
            'summary' => [
                'projects_count' => $data['summary']['projects_count'],
                'items_count' => $data['summary']['items_count'],
                'total_cost' => $data['summary']['total_cost'],
            ],
            'years' => $data['years'],
            'recentProjects' => collect($data['projects'])
                ->sortByDesc('last_saved_on')
                ->take(5)
                ->values()
                ->all(),
        ]);
    }
}
