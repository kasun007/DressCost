<?php

use App\Http\Controllers\CompareController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectShareController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SharedProjectController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

// The app opens on the login page, or the dashboard when already logged in.
Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'))->name('home');

// Read-only share links: anyone with the link can see the project's costing.
Route::get('share/{token}', [SharedProjectController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->middleware('throttle:60,1')
    ->name('shared.projects.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('materials', MaterialController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('workspaces', WorkspaceController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);

    Route::resource('projects', ProjectController::class)
        ->only(['store', 'show', 'update', 'destroy']);

    Route::post('projects/{project}/share', [ProjectShareController::class, 'store'])->name('projects.share.store');
    Route::delete('projects/{project}/share', [ProjectShareController::class, 'destroy'])->name('projects.share.destroy');

    Route::resource('projects.items', ItemController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy'])
        ->scoped(['item' => 'id']);

    Route::get('reports', ReportController::class)->name('reports.index');
    Route::get('reports/compare', CompareController::class)->name('reports.compare');
});

require __DIR__.'/settings.php';
