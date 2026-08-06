<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Муассиса (Донишгоҳ/Коллеҷ)
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name');          // Номи расмии муассиса
            $table->string('short_name', 50)->nullable(); // Ихтисор
            $table->string('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('website')->nullable();
            $table->string('rector_name')->nullable();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Факултетҳо
        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('name');          // Номи факултет
            $table->string('short_name', 20)->nullable();
            $table->string('code', 10)->unique(); // Рамз
            $table->foreignId('dean_id')->nullable()->constrained('users')->nullOnDelete(); // Декан
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active']);
            $table->index(['institution_id']);
        });

        // Кафедраҳо
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('short_name', 20)->nullable();
            $table->string('code', 10)->unique();
            $table->foreignId('head_id')->nullable()->constrained('users')->nullOnDelete(); // Мудири кафедра
            $table->string('phone', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['faculty_id', 'is_active']);
        });

        // Ихтисосҳо (Специальности)
        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20)->unique(); // Рамзи ихтисос (мисол: 1-40 01 01)
            $table->enum('education_level', ['bachelor', 'master', 'specialist'])->default('bachelor');
            $table->unsignedTinyInteger('study_years'); // Муддати таҳсил (4, 5, 2...)
            $table->unsignedSmallInteger('total_credits'); // Маҷмӯи кредитҳо
            $table->enum('study_form', ['full_time', 'part_time', 'evening'])->default('full_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'is_active']);
            $table->index(['education_level']);
        });

        // Курсҳо (1, 2, 3, 4, 5)
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number'); // 1-6
            $table->string('name', 50); // "Курси 1", "Курси 2"
            $table->timestamps();

            $table->unique(['number']);
        });

        // Гурӯҳҳо
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('name', 30); // "ИТ-1-24", "ИТ-2-24"
            $table->string('code', 20)->unique();
            $table->foreignId('curator_id')->nullable()->constrained('users')->nullOnDelete(); // Куратор
            $table->unsignedSmallInteger('max_students')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['specialty_id', 'course_id', 'is_active']);
            $table->index(['academic_year_id']);
        });

        // Аудиторияҳо
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20);      // Рақами аудитория: "301", "А-205"
            $table->string('building', 50)->nullable(); // Бинои
            $table->unsignedSmallInteger('floor')->nullable(); // Ошёна
            $table->unsignedSmallInteger('capacity')->default(30); // Ҷойгоҳ
            $table->enum('type', ['lecture', 'practice', 'lab', 'computer', 'gym', 'other'])->default('practice');
            $table->boolean('has_projector')->default(false);
            $table->boolean('has_computers')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['building', 'is_active']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('specialties');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('faculties');
        Schema::dropIfExists('institutions');
    }
};
