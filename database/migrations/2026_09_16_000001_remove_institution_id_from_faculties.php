<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropIndex(['institution_id']);
            $table->dropColumn('institution_id');
        });
    }

    public function down(): void
    {
        // NOTE: Reverts the column but existing data will not be recovered.
        Schema::table('faculties', function (Blueprint $table) {
            $table->foreignId('institution_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }
};
