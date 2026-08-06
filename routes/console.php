<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ====== Ҷадвали вазифаҳо (Scheduler) ======

// Тоза кардани логҳои кӯҳна (ҳар ҳафта)
Schedule::command('model:prune', ['--model' => \App\Models\AuditLog::class])->weekly();

// Тоза кардани сессияҳои гузашта (ҳар рӯз)
Schedule::command('session:gc')->daily();
