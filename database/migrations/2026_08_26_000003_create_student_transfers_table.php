<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->foreignId('to_group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->foreignId('from_specialty_id')->nullable()->constrained('specialties')->nullOnDelete();
            $table->foreignId('to_specialty_id')->nullable()->constrained('specialties')->nullOnDelete();
            $table->date('transfer_date');
            $table->string('reason')->nullable();
            $table->string('order_number')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'transfer_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_transfers');
    }
};
