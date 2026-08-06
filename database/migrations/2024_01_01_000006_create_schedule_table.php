<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ҷадвали дарсӣ (расписание)
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1=Душанбе ... 6=Шанбе
            $table->unsignedTinyInteger('lesson_number'); // Рақами дарс (1-8)
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('week_type', ['every', 'odd', 'even'])->default('every');
            // every=ҳар ҳафта, odd=ҳафтаи тоқ, even=ҳафтаи ҷуфт
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['semester_id', 'day_of_week']);
            $table->index(['classroom_id', 'day_of_week', 'lesson_number'], 'schedule_classroom_idx');
            // Назорати дубора нагузоштани як аудитория дар як вақт
            $table->unique(
                ['classroom_id', 'semester_id', 'day_of_week', 'lesson_number', 'week_type'],
                'schedule_no_conflict'
            );
        });

        // Вақтномаи дарсҳо (lesson time slots)
        Schema::create('lesson_times', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number'); // 1, 2, 3...
            $table->time('start_time');
            $table->time('end_time');
            $table->string('label', 20); // "Дарси 1", "Дарси 2"
            $table->timestamps();

            $table->unique(['number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_times');
        Schema::dropIfExists('schedules');
    }
};
