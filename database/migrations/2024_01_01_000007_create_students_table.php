<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Донишҷӯён
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('student_id_number', 30)->unique(); // Рақами донишҷӯӣ
            $table->string('record_book_number', 30)->nullable(); // Рақами зачёткa

            // Маълумоти шахсӣ
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('nationality', 50)->nullable();
            $table->string('citizenship', 50)->nullable();
            $table->string('passport_series', 10)->nullable();
            $table->string('passport_number', 20)->nullable();
            $table->string('inn', 20)->nullable(); // ИНН/ РМТ
            $table->text('address_permanent')->nullable(); // Суроғаи доимӣ
            $table->text('address_current')->nullable();   // Суроғаи ҳозира
            $table->string('parent_phone', 20)->nullable();
            $table->string('parent_name', 200)->nullable();

            // Маълумоти таҳсилӣ
            $table->enum('education_form', ['budget', 'contract'])->default('budget'); // Буҷет/Шартнома
            $table->enum('study_form', ['full_time', 'part_time', 'evening'])->default('full_time');
            $table->date('enrollment_date'); // Санаи қабул
            $table->string('enrollment_order', 50)->nullable(); // Фармони қабул
            $table->date('expected_graduation')->nullable(); // Санаи интизории хатм

            // Ҳолати донишҷӯ
            $table->enum('status', [
                'active',       // Фаъол
                'academic_leave', // Рухсатии академӣ
                'expelled',     // Хориҷшуда
                'graduated',    // Хатмкарда
                'transferred',  // Гузашта (ба дигар)
                'restored',     // Барқарорешуда
                'suspended'     // Муваққатан боздошташуда
            ])->default('active');
            $table->date('status_date')->nullable(); // Санаи тағйири ҳолат
            $table->string('status_order', 50)->nullable(); // Фармони тағйири ҳолат
            $table->text('status_reason')->nullable(); // Сабаби тағйири ҳолат

            $table->decimal('cumulative_gpa', 4, 2)->default(0); // GPA кулл
            $table->unsignedSmallInteger('total_credits_earned')->default(0); // Кредитҳои гирифташуда
            $table->boolean('has_debts')->default(false); // Дорои қарздории академӣ

            $table->timestamps();
            $table->softDeletes();

            $table->index(['group_id', 'status']);
            $table->index(['specialty_id', 'course_id']);
            $table->index(['status']);
            $table->index(['cumulative_gpa']);
            $table->index(['has_debts']);
        });

        // Таърихи ҳолати донишҷӯ (status history)
        Schema::create('student_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('order_number', 50)->nullable(); // Рақами фармон
            $table->date('order_date')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
        });

        // Гузаронидани донишҷӯ ба курси нав (course progression)
        Schema::create('student_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('to_group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('from_course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('to_course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('order_number', 50)->nullable();
            $table->date('order_date')->nullable();
            $table->decimal('gpa_at_promotion', 4, 2)->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_promotions');
        Schema::dropIfExists('student_status_history');
        Schema::dropIfExists('students');
    }
};
