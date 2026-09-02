<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retake_exam_answers', function (Blueprint $table) {
            $table->unsignedBigInteger('retake_exam_question_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('retake_exam_answers', function (Blueprint $table) {
            $table->unsignedBigInteger('retake_exam_question_id')->nullable(false)->change();
        });
    }
};
