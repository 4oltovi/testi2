<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_scores', function (Blueprint $table) {
            $table->dropUnique('cat_scores_unique');
            $table->unique(
                ['student_id', 'subject_assignment_id', 'lesson_date', 'lesson_number', 'category', 'period'],
                'cat_scores_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('category_scores', function (Blueprint $table) {
            $table->dropUnique('cat_scores_unique');
            $table->unique(
                ['student_id', 'subject_assignment_id', 'lesson_date', 'lesson_number', 'category'],
                'cat_scores_unique'
            );
        });
    }
};
