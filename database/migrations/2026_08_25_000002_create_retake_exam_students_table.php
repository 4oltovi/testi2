<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retake_exam_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retake_exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_debt_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('attempt_number')->default(1);
            $table->decimal('score', 5, 2)->nullable(); // Натиҷаи имтиҳон (%)
            $table->string('letter_grade')->nullable(); // Баҳои ҳарфӣ
            $table->enum('status', ['pending', 'passed', 'failed', 'absent'])->default('pending');
            $table->foreignId('examiner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('examined_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['retake_exam_id', 'student_id', 'academic_debt_id'], 'res_unique');
            $table->index(['student_id', 'status']);
            $table->index(['academic_debt_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retake_exam_students');
    }
};
