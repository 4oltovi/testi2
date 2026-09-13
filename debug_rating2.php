<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== RATING ATTEMPTS ===\n";
$attempts = App\Models\RatingAttempt::with('session')->get();
foreach ($attempts as $a) {
    $period = $a->session ? $a->session->period : 'N/A';
    $sem = $a->session ? $a->session->semester_id : 'N/A';
    echo "ID=".$a->id." Student=".$a->student_id." Subject=".$a->subject_id." Status=".$a->status." Pct=".$a->percentage." SessionID=".$a->rating_session_id." Period=".$period." Sem=".$sem."\n";
}

echo "\n=== RATING SESSIONS ===\n";
$sessions = App\Models\RatingSession::with('semester')->get();
foreach ($sessions as $s) {
    $semName = $s->semester ? $s->semester->name : 'N/A';
    echo "ID=".$s->id." Name=".$s->name." Period=".$s->period." SemesterID=".$s->semester_id." Semester=".$semName."\n";
}

echo "\n=== SUBJECT ASSIGNMENTS ===\n";
$assignments = App\Models\SubjectAssignment::with(['subject', 'semester', 'group'])->get();
foreach ($assignments as $a) {
    $subjName = $a->subject ? $a->subject->name : 'N/A';
    $semName = $a->semester ? $a->semester->name : 'N/A';
    $grpName = $a->group ? $a->group->name : 'N/A';
    echo "ID=".$a->id." SubjectID=".$a->subject_id." Subject=".$subjName." SemesterID=".$a->semester_id." Semester=".$semName." Group=".$grpName."\n";
}