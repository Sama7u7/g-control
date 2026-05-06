<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Corre cada día a las 8 AM — procesa gastos fijos automáticos
Schedule::command('gastos:procesar')->dailyAt('08:00');
