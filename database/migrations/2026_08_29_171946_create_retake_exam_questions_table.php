<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retake_exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retake_exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('points', 6, 2)->default(2.50);
            $table->timestamps();

            $table->unique(['retake_exam_id', 'question_id']);
            $table->index(['retake_exam_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retake_exam_questions');
    }
};
