<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Enums\UserRole;

class TestDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'super_admin', 'display_name' => 'Суперадмин', 'level' => 100, 'is_system' => true],
            ['name' => 'admin', 'display_name' => 'Администратор', 'level' => 90, 'is_system' => true],
            ['name' => 'teacher', 'display_name' => 'Омӯзгор', 'level' => 50, 'is_system' => true],
            ['name' => 'student', 'display_name' => 'Донишҷӯ', 'level' => 10, 'is_system' => true],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
