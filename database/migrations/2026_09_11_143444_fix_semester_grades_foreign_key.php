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
        Schema::table('semester_grades', function (Blueprint $table) {
            // Аввал пайванди хаторо нест мекунем
            $table->dropForeign('semester_grades_subject_id_foreign');
            
            // Пайванди дурустро ба ҷадвали 'subjects' илова мекунем
            $table->foreign('subject_id')
                ->references('id')
                ->on('subjects')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('semester_grades', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->foreign('subject_id')
                ->references('id')
                ->on('subject')
                ->onDelete('cascade');
        });
    }
};
