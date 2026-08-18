<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) question_banks: навъи банк (имтиҳон ё рейтинг)
        if (!Schema::hasColumn('question_banks', 'bank_type')) {
            Schema::table('question_banks', function (Blueprint $table) {
                $table->enum('bank_type', ['exam', 'rating'])->default('exam')->after('subject_id');
                $table->index('bank_type');
            });
        }

        // 2) Сессияҳои рейтинг
        Schema::create('rating_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('period', ['rating1', 'rating2']);
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->unsignedSmallInteger('duration_minutes')->default(45);
            $table->unsignedTinyInteger('questions_count')->default(30);
            $table->unsignedTinyInteger('max_attempts')->default(2); // админ худаш мегузорад
            $table->enum('schedule_mode', ['all', 'by_group'])->default('all');
            $table->enum('status', ['draft', 'active', 'completed'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['semester_id', 'period']);
            $table->index(['status']);
            $table->index(['start_at', 'end_at']);
        });

        // 3) Фанҳои сессия (якбора attach мешаванд)
        Schema::create('rating_session_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rating_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('questions_count')->nullable();
            $table->unique(['rating_session_id', 'subject_id'], 'rss_unique');
        });

        // 4) Равзанаи вақт барои гурӯҳҳо (режими by_group — ками нагрузка)
        Schema::create('rating_session_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rating_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->unique(['rating_session_id', 'group_id'], 'rsg_unique');
        });

        // 5) Кӯшишҳои донишҷӯён
        Schema::create('rating_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rating_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('attempt_number')->default(1);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('total_questions')->default(0);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->enum('status', ['in_progress', 'finished', 'auto_closed'])->default('in_progress');
            $table->json('answers_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['rating_session_id', 'student_id', 'subject_id'], 'ra_main_idx');
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating_attempts');
        Schema::dropIfExists('rating_session_groups');
        Schema::dropIfExists('rating_session_subjects');
        Schema::dropIfExists('rating_sessions');

        if (Schema::hasColumn('question_banks', 'bank_type')) {
            Schema::table('question_banks', function (Blueprint $table) {
                $table->dropIndex(['bank_type']);
                $table->dropColumn('bank_type');
            });
        }
    }
};
