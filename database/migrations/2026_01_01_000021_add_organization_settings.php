<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $items = [
            [
                'key' => 'institution_name',
                'value' => 'Муассисаи ғайридавлатии коллеҷи тиббии "Даво" Маркази тестӣ',
                'type' => 'string',
                'group' => 'organization',
                'display_name' => 'Номи муассиса',
                'description' => 'Дар сарлавҳаи ведомост ва транскрипт истифода мешавад',
                'is_public' => 0,
            ],
            [
                'key' => 'deputy_director_name',
                'value' => 'Гулов М.',
                'type' => 'string',
                'group' => 'organization',
                'display_name' => 'Муовини директор оид ба корҳои таълимӣ',
                'description' => 'Имзо дар ведомост ва транскрипт',
                'is_public' => 0,
            ],
            [
                'key' => 'testing_center_head_name',
                'value' => 'Хоҷаев М.М.',
                'type' => 'string',
                'group' => 'organization',
                'display_name' => 'Сардори маркази тестӣ',
                'description' => 'Имзо дар ведомост ва транскрипт',
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
        DB::table('settings')
            ->whereIn('key', ['institution_name', 'deputy_director_name', 'testing_center_head_name'])
            ->delete();
    }
};
