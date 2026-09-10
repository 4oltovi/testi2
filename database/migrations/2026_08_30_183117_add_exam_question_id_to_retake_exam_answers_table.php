<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retake_exam_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('retake_exam_answers', 'exam_question_id')) {
                $table->unsignedBigInteger('exam_question_id')->nullable()->after('retake_exam_question_id');
            }

            if (Schema::hasColumn('retake_exam_answers', 'exam_question_id')) {
                $table->foreign('exam_question_id')
                    ->references('id')
                    ->on('exam_questions')
                    ->nullOnDelete();

                $table->unique(
                    ['retake_exam_attempt_id', 'exam_question_id'],
                    'rea_attempt_question_unique'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('retake_exam_answers', function (Blueprint $table) {
            if (Schema::hasColumn('retake_exam_answers', 'exam_question_id')) {
                $table->dropForeign(['exam_question_id']);
                $table->dropUnique('rea_attempt_question_unique');
                $table->dropColumn('exam_question_id');
            }
        });
    }
};
