<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_debts', function (Blueprint $table) {
            $table->enum('debt_type', ['fx', 'f'])->nullable()->after('original_grade');
            $table->enum('payment_status', ['not_required', 'pending', 'paid', 'verified'])->default('not_required')->after('debt_type');
            $table->decimal('payment_amount', 10, 2)->nullable()->after('payment_status');
            $table->string('payment_receipt', 255)->nullable()->after('payment_amount');
            $table->timestamp('payment_verified_at')->nullable()->after('payment_receipt');
            $table->foreignId('payment_verified_by')->nullable()->constrained('users')->nullOnDelete()->after('payment_verified_at');

            $table->index(['debt_type', 'payment_status', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('academic_debts', function (Blueprint $table) {
            $table->dropIndex(['debt_type', 'payment_status', 'status']);
            $table->dropColumn([
                'debt_type',
                'payment_status',
                'payment_amount',
                'payment_receipt',
                'payment_verified_at',
                'payment_verified_by',
            ]);
        });
    }
};
