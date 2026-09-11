<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'session_token')) {
                $table->string('session_token', 255)->nullable()->after('remember_token');
            }
        });

        if (!Schema::hasIndex('users', 'users_session_token_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('session_token', 'users_session_token_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasIndex('users', 'users_session_token_unique')) {
                $table->dropUnique(['session_token']);
            }
            if (Schema::hasColumn('users', 'session_token')) {
                $table->dropColumn('session_token');
            }
        });
    }
};
