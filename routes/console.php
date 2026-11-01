<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Laravel\Telescope\Telescope;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep Telescope's recordings (local development only) to the last two days.
if (class_exists(Telescope::class)) {
    Schedule::command('telescope:prune --hours=48')->daily();
}
