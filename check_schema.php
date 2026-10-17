<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = Illuminate\Support\Facades\DB::select(
    "SELECT COLUMN_NAME FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'semester_grades'
       AND INDEX_NAME = 'semester_grade_unique' ORDER BY SEQ_IN_INDEX"
);

echo "semester_grade_unique: " . implode(', ', array_map(fn ($r) => $r->COLUMN_NAME, $rows)) . PHP_EOL;

$cs = Illuminate\Support\Facades\DB::select(
    "SELECT COLUMN_NAME FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'category_scores'
       AND INDEX_NAME = 'cat_scores_unique' ORDER BY SEQ_IN_INDEX"
);
echo "cat_scores_unique: " . implode(', ', array_map(fn ($r) => $r->COLUMN_NAME, $cs)) . PHP_EOL;

$has = Illuminate\Support\Facades\Schema::hasColumn('category_scores', 'lesson_type');
echo "category_scores.lesson_type exists: " . var_export($has, true) . PHP_EOL;
$has2 = Illuminate\Support\Facades\Schema::hasColumn('subject_assignments', 'merged_into_id');
echo "subject_assignments.merged_into_id exists: " . var_export($has2, true) . PHP_EOL;
$has3 = Illuminate\Support\Facades\Schema::hasColumn('grade_category_settings', 'applies_lecture');
echo "grade_category_settings.applies_lecture exists: " . var_export($has3, true) . PHP_EOL;
