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
        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedTinyInteger('credits')->nullable()->change();
            $table->unsignedSmallInteger('total_hours')->nullable()->change();
            $table->unsignedSmallInteger('lecture_hours')->default(0)->nullable()->change();
            $table->unsignedSmallInteger('practice_hours')->default(0)->nullable()->change();
            $table->unsignedSmallInteger('lab_hours')->default(0)->nullable()->change();
            $table->unsignedSmallInteger('independent_hours')->default(0)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedTinyInteger('credits')->nullable(false)->change();
            $table->unsignedSmallInteger('total_hours')->nullable(false)->change();
        });
    }
};
