<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Бақайдгир -> Кадр.
 *
 * Ҳамаи корбароне, ки нақши `registrar` доштанд, ба нақши нави `hr` манод карда
 * мешаванд. Ҳеҷ корбаре бе нақш намешавад: агар нақши `hr` дар база набошад
 * (install-и нав), он аввал сохта мешавад.
 *
 * Пас аз кӯчидани корманд, нақши `registrar` ҳам ва ҳам пайвастгирҳояи он нест
 * карда мешаванд, то ки «Бақайдгир» дигар дар ягон ҷо намоянд нашавад.
 *
 * Ҷойҳои пайвастгир: `user_role` ва `role_permission` (бино ба
 * `Role::users()` ва `Role::permissions()`).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('user_role')) {
            return;
        }

        $registrarId = DB::table('roles')->where('name', 'registrar')->value('id');

        if (! $registrarId) {
            return; // Бақайдгир ҷой надошт — чораи дигар лозим нест.
        }

        // 1. Нақши `hr` ҳаро барвақт месоҳад (install-и нав).
        $hrId = DB::table('roles')->where('name', 'hr')->value('id');

        if (! $hrId) {
            $hrId = DB::table('roles')->insertGetId([
                'name' => 'hr',
                'display_name' => 'Кадр',
                'description' => 'Кадр ходим — идоракунии корманд ва тасдиқи шахсияти донишҷӯён',
                'level' => 60,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('roles')->where('id', $hrId)->update([
                'display_name' => 'Кадр',
                'level' => 60,
                'is_system' => true,
                'updated_at' => now(),
            ]);
        }

        // 2. Ҳар як корбари `registrar`-ро ба `hr` манод мекунем.
        //    Insert-и алоҳида, на як `update`, то ки як корбар ду бор дар
        //    нақш нашавад, агар дар худи он аллакай `hr` ҳам дошт.
        $userIds = DB::table('user_role')->where('role_id', $registrarId)->pluck('user_id');

        foreach ($userIds as $userId) {
            $alreadyHr = DB::table('user_role')
                ->where('role_id', $hrId)
                ->where('user_id', $userId)
                ->exists();

            if (! $alreadyHr) {
                DB::table('user_role')->insert([
                    'role_id' => $hrId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Нақши кӯҳнаи `registrar` ва пайвастгирҳояшро нест мекунем.
        //    users-ҳое, ки танҳо ин нақшро доштанд, ба `hr`-и нав пайваст
        //    шудаанд, пас ягон корбаре бе нақш намешавад.
        DB::table('user_role')->where('role_id', $registrarId)->delete();

        if (Schema::hasTable('role_permission')) {
            DB::table('role_permission')->where('role_id', $registrarId)->delete();
        }

        DB::table('roles')->where('id', $registrarId)->delete();
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('user_role')) {
            return;
        }

        // Ҳар баробаршавии барқарор: бар мегардонанд ба `registrar`.
        $registrarId = DB::table('roles')->where('name', 'registrar')->value('id');

        if (! $registrarId) {
            $registrarId = DB::table('roles')->insertGetId([
                'name' => 'registrar',
                'display_name' => 'Бақайдгир',
                'level' => 60,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $hrId = DB::table('roles')->where('name', 'hr')->value('id');

        if ($hrId) {
            $userIds = DB::table('user_role')->where('role_id', $hrId)->pluck('user_id');

            foreach ($userIds as $userId) {
                $alreadyRegistrar = DB::table('user_role')
                    ->where('role_id', $registrarId)
                    ->where('user_id', $userId)
                    ->exists();

                if (! $alreadyRegistrar) {
                    DB::table('user_role')->insert([
                        'role_id' => $registrarId,
                        'user_id' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
};