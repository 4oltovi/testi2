<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('session_token', 64)->nullable()->after('remember_token');
        });

        $items = [
            [
                'key' => 'single_session_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'security',
                'display_name' => 'Танҳо як сессия барои ҳар корбар',
                'description' => 'Агар аз дигар дастгоҳ ворид шавад, аввала хориҷ мешавад',
                'is_public' => 0,
            ],
            [
                'key' => 'test_mode',
                'value' => 'online',
                'type' => 'string',
                'group' => 'security',
                'display_name' => 'Режими тест',
                'description' => 'online — онлайн, offline — локалӣ (бе интернет)',
                'is_public' => 0,
            ],
        ];

        foreach ($items as $item) {
            DB::table('settings')->updateOrInsert(
                ['key' => $item['key']],
                array_merge($item, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('session_token');
        });
        DB::table('settings')->whereIn('key', ['single_session_enabled', 'test_mode'])->delete();
    }
};
