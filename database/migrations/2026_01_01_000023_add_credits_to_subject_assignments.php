<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('subject_assignments', 'credits')) {
            Schema::table('subject_assignments', function (Blueprint $table) {
                $table->unsignedTinyInteger('credits')->nullable()->after('subject_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('subject_assignments', function (Blueprint $table) {
            $table->dropColumn('credits');
        });
    }
};
