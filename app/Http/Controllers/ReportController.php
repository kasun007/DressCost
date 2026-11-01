<?php

namespace App\Http\Controllers;

use App\Costing\CostReport;
use App\Http\Requests\ReportRequest;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * Show the cost report, filtered by project, costing type and date range.
     */
    public function __invoke(ReportRequest $request, CostReport $report): Response
    {
        return Inertia::render('reports/index', $report->for($request->user(), $request->filters()));
    }
}
