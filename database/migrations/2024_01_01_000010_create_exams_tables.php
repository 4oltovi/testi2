<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Банки саволҳо (Question Bank)
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('name'); // Номи банк
            $table->text('description')->nullable();
            $table->unsignedInteger('total_questions')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['subject_id', 'is_active']);
        });

        // Саволҳо
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'single_choice',    // Якҷавобӣ
                'multiple_choice',  // Чандҷавобӣ
                'open_text',        // Ҷавоби кушод
                'true_false',       // Дуруст/Нодуруст
                'matching'          // Мувофиқгузорӣ
            ]);
            $table->text('question_text'); // Матни савол
            $table->string('question_image')->nullable(); // Тасвир (агар бошад)
            $table->unsignedTinyInteger('difficulty_level')->default(1); // 1-5
            $table->decimal('points', 5, 2)->default(1.00); // Балл барои ин савол
            $table->text('explanation')->nullable(); // Шарҳи ҷавоби дуруст
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('times_used')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['question_bank_id', 'type', 'is_active']);
            $table->index(['subject_id', 'difficulty_level']);
        });

        // Вариантҳои ҷавоб (Answer Options)
        Schema::create('answer_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('option_text');
            $table->string('option_image')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['question_id', 'is_correct']);
        });

        // Имтиҳонҳо (Exams)
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('title'); // Номи имтиҳон
            $table->text('description')->nullable();

            $table->enum('exam_type', [
                'main',             // Имтиҳони асосӣ
                'retake',           // Такрорсупорӣ
                'retake_commission', // Комиссионӣ
                'rating1',          // Рейтинги 1
                'rating2',          // Рейтинги 2
                'midterm',          // Миёнасеместрӣ
                'quiz'              // Тести кӯтоҳ
            ]);

            $table->enum('format', [
                'online_test',  // Тести онлайн
                'written',      // Хаттӣ
                'oral',         // Даҳонӣ
                'mixed'         // Омехта
            ])->default('online_test');

            // Танзимоти тест (барои online_test)
            $table->unsignedSmallInteger('duration_minutes')->default(60); // Вақт (дақиқа)
            $table->unsignedSmallInteger('total_questions_count')->default(30); // Шумораи саволҳо
            $table->decimal('passing_score', 5, 2)->default(50.00); // Ҳадди ақали гузариш (%)
            $table->boolean('shuffle_questions')->default(true); // Тасодуфӣ кардани саволҳо
            $table->boolean('shuffle_answers')->default(true);  // Тасодуфӣ кардани ҷавобҳо
            $table->boolean('show_results_immediately')->default(false); // Натиҷаро фавран нишон деҳ
            $table->boolean('allow_back_navigation')->default(true); // Бозгашт ба саволи пеш
            $table->unsignedTinyInteger('max_attempts')->default(1); // Шумораи кӯшишҳо
            $table->boolean('auto_save')->default(true); // Автосабт

            // Вақти оғоз ва анҷом
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            // Ҳолат
            $table->enum('status', [
                'draft',        // Лоиҳа
                'scheduled',    // Барномарезӣ шуда
                'active',       // Фаъол (дар ҷараён)
                'completed',    // Анҷомёфта
                'cancelled'     // Бекоршуда
            ])->default('draft');

            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_assignment_id', 'exam_type']);
            $table->index(['group_id', 'semester_id']);
            $table->index(['status', 'starts_at']);
            $table->index(['teacher_id']);
        });

        // Саволҳои интихобшуда барои имтиҳон (Exam Questions)
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->decimal('points', 5, 2)->default(1.00); // Балл дар ин имтиҳон
            $table->timestamps();

            $table->unique(['exam_id', 'question_id']);
            $table->index(['exam_id', 'sort_order']);
        });

        // Кӯшиши донишҷӯ (Exam Attempts)
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('attempt_number')->default(1);

            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('auto_submitted_at')->nullable(); // Вақти автосупориш (таймер тамом шуд)

            $table->decimal('total_score', 5, 2)->nullable(); // Баллҳои гирифташуда
            $table->decimal('max_possible_score', 5, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable(); // Фоиз
            $table->string('letter_grade', 2)->nullable();
            $table->decimal('grade_point', 4, 2)->nullable();

            $table->enum('status', [
                'in_progress',  // Дар ҷараён
                'submitted',    // Супорида шуд
                'auto_submitted', // Автоматӣ супорида шуд (вақт тамом)
                'graded',       // Баҳогузорӣ шуд
                'invalidated'   // Бекор карда шуд
            ])->default('in_progress');

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->unsignedSmallInteger('disconnections')->default(0); // Шумораи қатъи интернет
            $table->timestamp('last_activity_at')->nullable(); // Охирин фаъолият

            $table->timestamps();

            $table->unique(['exam_id', 'student_id', 'attempt_number'], 'exam_attempt_unique');
            $table->index(['student_id', 'status']);
            $table->index(['exam_id', 'status']);
        });

        // Ҷавобҳои донишҷӯ (Student Answers)
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();

            // Ҷавоби донишҷӯ
            $table->json('selected_options')->nullable(); // [1, 3] — ID-ҳои интихобшуда
            $table->text('text_answer')->nullable(); // Барои ҷавоби кушод
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_earned', 5, 2)->default(0);
            $table->text('teacher_comment')->nullable(); // Шарҳи муаллим (барои кушод)
            $table->boolean('is_graded')->default(false); // Барои ҷавоби кушод

            $table->timestamp('answered_at')->nullable();
            $table->boolean('is_flagged')->default(false); // Донишҷӯ "алоқаманд" кард
            $table->timestamps();

            $table->unique(['exam_attempt_id', 'exam_question_id'], 'exam_answer_unique');
            $table->index(['exam_attempt_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
        Schema::dropIfExists('exam_attempts');
        Schema::dropIfExists('exam_questions');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('answer_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('question_banks');
    }
};
