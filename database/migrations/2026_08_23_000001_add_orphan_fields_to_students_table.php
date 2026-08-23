<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->enum('orphan_type', ['none', 'orphan', 'half_orphan'])->default('none')->after('has_debts');
            $table->string('guardian_name')->nullable()->after('orphan_type');
            $table->string('guardian_phone')->nullable()->after('guardian_name');
            $table->string('guardian_relation')->nullable()->after('guardian_phone');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['orphan_type', 'guardian_name', 'guardian_phone', 'guardian_relation']);
        });
    }
};
