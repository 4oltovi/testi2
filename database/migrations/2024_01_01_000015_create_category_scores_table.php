<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Танзимоти категорияҳо барои ҳар устод/фан
        Schema::create('grade_category_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->string('category', 30); // savod, sarulibos, jihoz, ishtirok, intizom
            $table->decimal('max_score', 5, 2)->default(10);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['subject_assignment_id', 'category'], 'grade_cat_setting_unique');
        });

        // Баҳоҳои категориявӣ барои ҳар дарс
        Schema::create('category_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->date('lesson_date');
            $table->unsignedTinyInteger('lesson_number')->default(1);
            $table->string('category', 30); // savod, sarulibos, jihoz, ishtirok, intizom
            $table->decimal('score', 5, 2); // Баҳои гузоштаи устод
            $table->decimal('max_score', 5, 2); // Ҳадди аксар дар он лаҳза
            $table->foreignId('graded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'subject_assignment_id', 'semester_id'], 'cat_scores_main_idx');
            $table->index(['subject_assignment_id', 'lesson_date', 'lesson_number'], 'cat_scores_lesson_idx');
            $table->unique(
                ['student_id', 'subject_assignment_id', 'lesson_date', 'lesson_number', 'category'],
                'cat_scores_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_scores');
        Schema::dropIfExists('grade_category_settings');
    }
};
