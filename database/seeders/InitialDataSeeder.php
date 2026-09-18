<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // ====== КОРБАРИ АВВАЛИН (Super Admin) ======
        $admin = User::updateOrCreate(
            ['login' => 'admin'],
            [
                'first_name' => 'Админ',
                'last_name' => 'Системавӣ',
                'email' => 'admin@donishor.tj',
                'password' => Hash::make('admin123456'),
                'status' => 'active',
            ]
        );
        $adminRole = Role::where('name', 'super_admin')->first();
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        // ====== КУРСҲО ======
        for ($i = 1; $i <= 5; $i++) {
            Course::updateOrCreate(
                ['number' => $i],
                ['name' => "Курси {$i}"]
            );
        }
    }
}
