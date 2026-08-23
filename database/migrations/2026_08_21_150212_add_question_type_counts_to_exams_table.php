<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedSmallInteger('simple_questions_count')->default(20)->after('total_questions_count');
            $table->unsignedSmallInteger('matching_questions_count')->default(5)->after('simple_questions_count');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['simple_questions_count', 'matching_questions_count']);
        });
    }
};
