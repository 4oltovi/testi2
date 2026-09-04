<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retake_vedomosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retake_exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->nullable();
            $table->date('exam_date')->nullable();
            $table->enum('status', ['draft', 'confirmed', 'printed'])->default('draft');
            $table->timestamps();

            $table->unique(['retake_exam_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retake_vedomosts');
    }
};
