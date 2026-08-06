<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Нақшҳо (Super Admin, Admin, Декан, ...)
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique(); // slug: super_admin, admin, dean...
            $table->string('display_name', 100);  // Номи дар интерфейс
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('level')->default(0); // Сатҳи дастрасӣ (0-100)
            $table->boolean('is_system')->default(false); // Нақши системавӣ (наметавон нест кард)
            $table->timestamps();
        });

        // Иҷозатҳо
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique(); // students.view, journal.edit...
            $table->string('display_name', 150);
            $table->string('module', 50); // Модули марбута
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['module']);
        });

        // Робитаи role-permission (many-to-many)
        Schema::create('role_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        // Робитаи user-role (many-to-many)
        Schema::create('user_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });

        // Иҷозатҳои махсус барои корбар (override)
        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->boolean('granted')->default(true); // true=додан, false=маҳдуд кардан
            $table->timestamps();

            $table->unique(['user_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permission');
        Schema::dropIfExists('user_role');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
