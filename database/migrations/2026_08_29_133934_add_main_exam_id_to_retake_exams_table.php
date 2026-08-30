<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retake_exams', function (Blueprint $table) {
            $table->foreignId('main_exam_id')->nullable()->constrained('exams')->nullOnDelete()->after('semester_id');
        });
    }

    public function down(): void
    {
        Schema::table('retake_exams', function (Blueprint $table) {
            $table->dropForeign(['main_exam_id']);
            $table->dropColumn('main_exam_id');
        });
    }
};
