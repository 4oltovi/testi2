<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$attempts = App\Models\RatingAttempt::with('session')->get();
foreach ($attempts as $a) {
    echo "ID=" . $a->id . " Student=" . $a->student_id . " Subject=" . $a->subject_id . " Status=" . $a->status . " Pct=" . $a->percentage . " SessionID=" . $a->rating_session_id . " Period=" . ($a->session ? $a->session->period : 'N/A') . " Sem=" . ($a->session ? $a->session->semester_id : 'N/A') . PHP_EOL;
}