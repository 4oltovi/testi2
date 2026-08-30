<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retake_exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retake_exam_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('retake_exam_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->json('selected_options')->nullable();
            $table->text('text_answer')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->decimal('points_earned', 6, 2)->default(0);
            $table->text('teacher_comment')->nullable();
            $table->boolean('is_graded')->default(false);
            $table->timestamp('answered_at')->nullable();
            $table->boolean('is_flagged')->default(false);
            $table->timestamps();

            $table->unique(['retake_exam_attempt_id', 'retake_exam_question_id'], 'rea_attempt_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retake_exam_answers');
    }
};
