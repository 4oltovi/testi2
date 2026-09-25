<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->foreignId('faculty_id')->nullable()->constrained()->nullOnDelete()->after('department_id');
        });

        DB::statement('UPDATE specialties SET faculty_id = (SELECT faculty_id FROM departments WHERE departments.id = specialties.department_id)');
    }

    public function down(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->dropForeign(['faculty_id']);
            $table->dropColumn('faculty_id');
        });
    }
};
