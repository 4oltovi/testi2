<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $uniqueExists = DB::select("SHOW INDEX FROM `groups` WHERE Key_name = 'groups_code_unique'");
        if (!empty($uniqueExists)) {
            Schema::table('groups', function (Blueprint $table) {
                $table->dropUnique('groups_code_unique');
            });
        }

        $groups = DB::table('groups')->get();

        foreach ($groups as $group) {
            $name = trim($group->name ?? '');
            $code = trim($group->code ?? '');

            $hasSuffix = preg_match('/^(.*?)[-\s]+(\d+)$/', $name);
            $codeIsDigits = ctype_digit($code);

            if (!$hasSuffix && ($codeIsDigits || $code === '')) {
                continue;
            }

            $newName = $name;
            $newCode = '';

            if ($hasSuffix) {
                preg_match('/^(.*?)[-\s]+(\d+)$/', $name, $matches);
                $newName = trim($matches[1]);
                $newCode = $matches[2];
            }

            DB::table('groups')
                ->where('id', $group->id)
                ->update([
                    'name' => $newName,
                    'code' => $newCode,
                ]);

            if ($newCode === '') {
                echo "[WARNING] Group id={$group->id}: no numeric suffix found in name \"{$name}\". Code set to empty string. Please fill in manually.\n";
            } else {
                $oldCode = trim($group->code ?? '');
                if ($oldCode !== $newCode && $oldCode !== '') {
                    echo "[INFO] Group id={$group->id}: code changed from \"{$oldCode}\" to \"{$newCode}\". Verify course_id matches if needed.\n";
                }
            }
        }
    }

    public function down(): void
    {
        try {
            Schema::table('groups', function (Blueprint $table) {
                $table->unique('code', 'groups_code_unique');
            });
        } catch (\Exception $e) {
            echo "[DOWN] Could not restore unique index (possible duplicate codes): " . $e->getMessage() . "\n";
        }

        $groups = DB::table('groups')->get();

        foreach ($groups as $group) {
            $name = trim($group->name ?? '');
            $code = trim($group->code ?? '');

            if ($code !== '' && preg_match('/^\d+$/', $code)) {
                DB::table('groups')
                    ->where('id', $group->id)
                    ->update([
                        'name' => $name . '-' . $code,
                        'code' => '',
                    ]);
            }
        }
    }
};
