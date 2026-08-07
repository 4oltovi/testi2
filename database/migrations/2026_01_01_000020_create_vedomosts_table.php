<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vedomosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->nullable();          // Рақами ҳуҷҷат
            $table->date('exam_date')->nullable();         // Санаи имтиҳон
            $table->enum('status', ['draft', 'confirmed', 'printed'])->default('draft');
            $table->timestamps();

            $table->unique(['subject_assignment_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vedomosts');
    }
};
