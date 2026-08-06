<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Солҳои таҳсилӣ (мисол: 2024-2025)
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20); // "2024-2025"
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->enum('status', ['planning', 'active', 'completed'])->default('planning');
            $table->timestamps();

            $table->unique(['name']);
            $table->index(['is_current']);
        });

        // Семестрҳо
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number'); // 1 ё 2
            $table->string('name', 50); // "Семестри 1", "Семестри 2"
            $table->date('start_date');
            $table->date('end_date');
            $table->date('exam_start_date')->nullable(); // Оғози сессия
            $table->date('exam_end_date')->nullable();   // Анҷоми сессия
            $table->date('retake_start_date')->nullable(); // Оғози такрорсупорӣ
            $table->date('retake_end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->enum('status', ['planning', 'active', 'exam_period', 'retake_period', 'completed'])->default('planning');
            $table->timestamps();

            $table->unique(['academic_year_id', 'number']);
            $table->index(['is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('academic_years');
    }
};
