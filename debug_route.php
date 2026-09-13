<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo route('debts.export-pdf', ['group_id' => 4, 'semester_id' => 5]);
echo PHP_EOL;
