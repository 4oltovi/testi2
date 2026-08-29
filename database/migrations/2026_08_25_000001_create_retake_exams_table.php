<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retake_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title'); // Номи имтиҳони такрорӣ
            $table->text('description')->nullable();
            $table->enum('format', ['online_test', 'written', 'oral', 'mixed'])->default('written');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->decimal('passing_score', 5, 2)->default(50.00);
            $table->unsignedTinyInteger('max_attempts')->default(1);
            $table->date('exam_date')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['subject_id', 'semester_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retake_exams');
    }
};
