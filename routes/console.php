<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('compressed-images:cleanup')->dailyAt('03:00');
Schedule::command('bg-removed-images:cleanup')->dailyAt('03:10');