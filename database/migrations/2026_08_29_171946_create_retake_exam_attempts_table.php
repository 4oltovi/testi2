<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retake_exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retake_exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('retake_exam_student_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('attempt_number')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('auto_submitted_at')->nullable();
            $table->decimal('total_score', 8, 2)->nullable();
            $table->decimal('max_possible_score', 8, 2)->nullable();
            $table->decimal('percentage', 6, 2)->nullable();
            $table->string('letter_grade')->nullable();
            $table->decimal('grade_point', 3, 2)->nullable();
            $table->enum('status', ['in_progress', 'submitted', 'auto_submitted', 'graded'])->default('in_progress');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedInteger('disconnections')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['retake_exam_id', 'student_id', 'attempt_number'], 'rea_exam_student_attempt_idx');
            $table->unique(['retake_exam_id', 'student_id', 'attempt_number'], 'rea_exam_student_attempt_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retake_exam_attempts');
    }
};
