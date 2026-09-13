<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('academic_debts')
            ->where('original_grade', 'Fx')
            ->update([
                'debt_type' => 'fx',
                'payment_status' => 'not_required',
                'retake_allowed' => true,
                'max_retake_attempts' => 2,
            ]);

        DB::table('academic_debts')
            ->where('original_grade', 'F')
            ->update([
                'debt_type' => 'f',
                'payment_status' => 'pending',
                'retake_allowed' => false,
                'max_retake_attempts' => 2,
            ]);
    }

    public function down(): void
    {
        DB::table('academic_debts')->update([
            'debt_type' => null,
            'payment_status' => 'not_required',
        ]);
    }
};
