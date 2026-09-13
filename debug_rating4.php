<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ALL SubjectAssignments for SubjectID=13 ===\n";
$assignments = App\Models\SubjectAssignment::where('subject_id', 13)->with(['subject', 'semester', 'group'])->get();
foreach ($assignments as $a) {
    $subjName = $a->subject ? $a->subject->name : 'N/A';
    $semName = $a->semester ? $a->semester->name : 'N/A';
    $grpName = $a->group ? $a->group->name : 'N/A';
    echo "ID=".$a->id." SubjectID=".$a->subject_id." Subject=".$subjName." SemesterID=".$a->semester_id." Semester=".$semName." GroupID=".$a->group_id." Group=".$grpName."\n";
}

echo "\n=== All Students with attempts ===\n";
$studentIds = App\Models\RatingAttempt::where('status', 'finished')->pluck('student_id')->unique()->toArray();
foreach ($studentIds as $sid) {
    $student = App\Models\Student::with(['user', 'group'])->find($sid);
    if ($student) {
        $grpName = $student->group ? $student->group->name : 'N/A';
        echo "StudentID=".$student->id." GroupID=".$student->group_id." Group=".$grpName."\n";
    }
}

echo "\n=== Checking: does student 71 have SubjectAssignment for Semester 2? ===\n";
$student71 = App\Models\Student::with('group')->find(71);
echo "Student 71 group: ".$student71->group_id."\n";
$assignments71sem2 = App\Models\SubjectAssignment::where('group_id', $student71->group_id)
    ->where('subject_id', 13)
    ->where('semester_id', 8)
    ->with('semester')
    ->get();
foreach ($assignments71sem2 as $a) {
    echo "Found: AssignmentID=".$a->id." SemesterID=".$a->semester_id." Semester=".$a->semester->name."\n";
}

echo "\n=== Checking RatingAttempt query directly ===\n";
// Simulate what calculateComputerRatingScore does for student 71, subject_assignment 35, semester 7
$subjectId = App\Models\SubjectAssignment::whereKey(35)->value('subject_id');
echo "SubjectAssignment 35 -> subject_id = ".$subjectId."\n";

$attempt = App\Models\RatingAttempt::where('student_id', 71)
    ->where('subject_id', $subjectId)
    ->where('status', 'finished')
    ->whereHas('session', function($q) use ($subjectId) {
        $q->where('period', 'rating1')
          ->where('semester_id', 7); // Semester 1
    })
    ->orderByDesc('percentage')
    ->first();
echo "Query with semester_id=7: ".($attempt ? "FOUND ({$attempt->id}, {$attempt->percentage})" : "NOT FOUND")."\n";

$attempt2 = App\Models\RatingAttempt::where('student_id', 71)
    ->where('subject_id', $subjectId)
    ->where('status', 'finished')
    ->whereHas('session', function($q) use ($subjectId) {
        $q->where('period', 'rating1');
        // NO semester constraint
    })
    ->orderByDesc('percentage')
    ->first();
echo "Query without semester constraint: ".($attempt2 ? "FOUND ({$attempt2->id}, {$attempt2->percentage})" : "NOT FOUND")."\n";

$attempt3 = App\Models\RatingAttempt::where('student_id', 71)
    ->where('subject_id', $subjectId)
    ->where('status', 'finished')
    ->whereHas('session', function($q) use ($subjectId) {
        $q->where('period', 'rating1')
          ->where('semester_id', 8); // Semester 2
    })
    ->orderByDesc('percentage')
    ->first();
echo "Query with semester_id=8: ".($attempt3 ? "FOUND ({$attempt3->id}, {$attempt3->percentage})" : "NOT FOUND")."\n";