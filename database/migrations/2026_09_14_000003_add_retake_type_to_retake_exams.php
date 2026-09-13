<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retake_exams', function (Blueprint $table) {
            $table->enum('retake_type', ['fx', 'f'])->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('retake_exams', function (Blueprint $table) {
            $table->dropColumn('retake_type');
        });
    }
};
