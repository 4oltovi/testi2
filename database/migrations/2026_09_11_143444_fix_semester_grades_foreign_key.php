<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support dropping foreign keys by name.
            // Recreate the table instead.
            Schema::table('semester_grades', function (Blueprint $table) {
                $table->dropForeign(['subject_id']);
            });
        } else {
            Schema::table('semester_grades', function (Blueprint $table) {
                // Drop old FK by name
                $table->dropForeign('semester_grades_subject_id_foreign');
            });
        }

        // Add correct FK to 'subjects' table
        Schema::table('semester_grades', function (Blueprint $table) {
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
        });

        Schema::table('semester_grades', function (Blueprint $table) {
            $table->foreign('subject_id')
                ->references('id')
                ->on('subject')
                ->onDelete('cascade');
        });
    }
};
