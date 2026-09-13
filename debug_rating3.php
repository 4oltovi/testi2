<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ALL RATING SESSIONS ===\n";
$sessions = App\Models\RatingSession::with('semester')->get();
foreach ($sessions as $s) {
    $semName = $s->semester ? $s->semester->name : 'N/A';
    echo "ID=".$s->id." Name=".$s->name." Period=".$s->period." SemesterID=".$s->semester_id." Semester=".$semName."\n";
}

echo "\n=== SUBJECT ASSIGNMENT FOR ID=35 ===\n";
$assignment = App\Models\SubjectAssignment::find(35);
if ($assignment) {
    echo "ID=".$assignment->id." SubjectID=".$assignment->subject_id." Subject=".
        ($assignment->subject ? $assignment->subject->name : 'N/A').
        " SemesterID=".$assignment->semester_id." Semester=".
        ($assignment->semester ? $assignment->semester->name : 'N/A')."\n";
        
    // Check what sessions exist for this semester_id
    $sessionsForSem = App\Models\RatingSession::where('semester_id', $assignment->semester_id)
        ->with('semester')
        ->get();
    echo "Sessions for SemesterID=".$assignment->semester_id.":\n";
    foreach ($sessionsForSem as $s) {
        $semName = $s->semester ? $s->semester->name : 'N/A';
        echo "  ID=".$s->id." Name=".$s->name." Period=".$s->period." SemesterID=".$s->semester_id." Semester=".$semName."\n";
    }
    
    // Check what sessions exist for subject_id=13
    $sessionsForSubject = App\Models\RatingSession::whereHas('subjects', function($q) {
        $q->where('subject_id', 13);
    })->with(['semester', 'subjects'])->get();
    echo "Sessions for SubjectID=13:\n";
    foreach ($sessionsForSubject as $s) {
        $semName = $s->semester ? $s->semester->name : 'N/A';
        $subjectNames = $s->subjects->pluck('name')->implode(', ');
        echo "  ID=".$s->id." Name=".$s->name." Period=".$s->period." SemesterID=".$s->semester_id." Semester=".$semName." Subjects=".$subjectNames."\n";
    }
}