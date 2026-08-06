<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Омӯзгорон
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete(); // Кафедраи асосӣ
            $table->string('employee_id', 30)->unique(); // Рақами кормандӣ
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();

            // Маълумоти касбӣ
            $table->string('academic_degree', 100)->nullable(); // Дараҷаи илмӣ (к.т.н., д.т.н.)
            $table->string('academic_title', 100)->nullable(); // Унвони илмӣ (доцент, профессор)
            $table->string('position', 100); // Вазифа (ассистент, муаллими калон, доцент, профессор)
            $table->enum('employment_type', ['full_time', 'part_time', 'hourly'])->default('full_time');
            $table->decimal('rate', 3, 2)->default(1.00); // Ставка (1.0, 0.5, 0.25)
            $table->date('hire_date'); // Санаи қабул ба кор
            $table->date('contract_end_date')->nullable();

            // Борбандии дарсӣ
            $table->unsignedSmallInteger('max_hours_per_week')->default(36); // Соатҳои ҳадди аксар
            $table->unsignedSmallInteger('current_load_hours')->default(0); // Борбандии ҷорӣ

            $table->enum('status', ['active', 'on_leave', 'dismissed'])->default('active');
            $table->text('biography')->nullable();
            $table->string('phone_work', 20)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'status']);
            $table->index(['status']);
        });

        // Таърихи фаъолияти омӯзгор
        Schema::create('teacher_activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->string('activity_type', 50); // promotion, department_change, degree_obtained...
            $table->string('description');
            $table->date('activity_date');
            $table->string('order_number', 50)->nullable();
            $table->json('metadata')->nullable(); // Маълумоти иловагӣ
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['teacher_id', 'activity_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_activity_log');
        Schema::dropIfExists('teachers');
    }
};
