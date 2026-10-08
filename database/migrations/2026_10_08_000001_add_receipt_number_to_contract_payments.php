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
        Schema::table('contract_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('contract_payments', 'receipt_number')) {
                $table->string('receipt_number', 50)->nullable()->after('note');
            }
        });

        // Before adding the unique index, neutralise existing duplicate
        // receipt numbers so the migration does not fail on live data.
        // For each duplicated value we keep the first row and blank the rest.
        $duplicates = DB::table('contract_payments')
            ->select('receipt_number', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('receipt_number')
            ->groupBy('receipt_number')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $ids = DB::table('contract_payments')
                ->where('receipt_number', $dup->receipt_number)
                ->orderBy('id')
                ->pluck('id');

            // Keep the first, blank the rest
            $keep = $ids->first();
            $ids->shift();
            DB::table('contract_payments')
                ->whereIn('id', $ids->all())
                ->update(['receipt_number' => null]);
        }

        Schema::table('contract_payments', function (Blueprint $table) {
            if (!Schema::hasIndex('contract_payments', 'contract_payments_receipt_number_unique')) {
                $table->unique('receipt_number', 'contract_payments_receipt_number_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_payments', function (Blueprint $table) {
            if (Schema::hasIndex('contract_payments', 'contract_payments_receipt_number_unique')) {
                $table->dropIndex('contract_payments_receipt_number_unique');
            }
            if (Schema::hasColumn('contract_payments', 'receipt_number')) {
                $table->dropColumn('receipt_number');
            }
        });
    }
};