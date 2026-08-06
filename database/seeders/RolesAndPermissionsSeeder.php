<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // ====== НАҚШҲО ======
        $roles = [
            ['name' => 'super_admin', 'display_name' => 'Суперадмин', 'level' => 100, 'is_system' => true],
            ['name' => 'admin', 'display_name' => 'Администратор', 'level' => 90, 'is_system' => true],
            ['name' => 'dean', 'display_name' => 'Декан', 'level' => 80, 'is_system' => true],
            ['name' => 'vice_dean', 'display_name' => 'Муовини декан', 'level' => 75, 'is_system' => true],
            ['name' => 'department_head', 'display_name' => 'Мудири кафедра', 'level' => 70, 'is_system' => true],
            ['name' => 'registrar', 'display_name' => 'Бақайдгир', 'level' => 60, 'is_system' => true],
            ['name' => 'teacher', 'display_name' => 'Омӯзгор', 'level' => 50, 'is_system' => true],
            ['name' => 'accountant', 'display_name' => 'Муҳосиб', 'level' => 40, 'is_system' => true],
            ['name' => 'operator', 'display_name' => 'Оператор', 'level' => 30, 'is_system' => true],
            ['name' => 'student', 'display_name' => 'Донишҷӯ', 'level' => 10, 'is_system' => true],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }

        // ====== ИҶОЗАТҲО ======
        $permissions = [
            // Модули корбарон
            ['name' => 'users.view', 'display_name' => 'Дидани корбарон', 'module' => 'users'],
            ['name' => 'users.create', 'display_name' => 'Сохтани корбар', 'module' => 'users'],
            ['name' => 'users.edit', 'display_name' => 'Таҳрири корбар', 'module' => 'users'],
            ['name' => 'users.delete', 'display_name' => 'Нест кардани корбар', 'module' => 'users'],
            ['name' => 'users.roles', 'display_name' => 'Идоракунии нақшҳо', 'module' => 'users'],

            // Модули сохтор
            ['name' => 'structure.view', 'display_name' => 'Дидани сохтор', 'module' => 'structure'],
            ['name' => 'structure.create', 'display_name' => 'Сохтани сохтор', 'module' => 'structure'],
            ['name' => 'structure.edit', 'display_name' => 'Таҳрири сохтор', 'module' => 'structure'],
            ['name' => 'structure.delete', 'display_name' => 'Нест кардани сохтор', 'module' => 'structure'],

            // Модули донишҷӯён
            ['name' => 'students.view', 'display_name' => 'Дидани донишҷӯён', 'module' => 'students'],
            ['name' => 'students.create', 'display_name' => 'Сабти донишҷӯ', 'module' => 'students'],
            ['name' => 'students.edit', 'display_name' => 'Таҳрири донишҷӯ', 'module' => 'students'],
            ['name' => 'students.delete', 'display_name' => 'Нест кардани донишҷӯ', 'module' => 'students'],
            ['name' => 'students.status', 'display_name' => 'Тағйири ҳолати донишҷӯ', 'module' => 'students'],
            ['name' => 'students.promote', 'display_name' => 'Гузаронидан ба курси нав', 'module' => 'students'],

            // Модули омӯзгорон
            ['name' => 'teachers.view', 'display_name' => 'Дидани омӯзгорон', 'module' => 'teachers'],
            ['name' => 'teachers.create', 'display_name' => 'Сабти омӯзгор', 'module' => 'teachers'],
            ['name' => 'teachers.edit', 'display_name' => 'Таҳрири омӯзгор', 'module' => 'teachers'],
            ['name' => 'teachers.delete', 'display_name' => 'Нест кардани омӯзгор', 'module' => 'teachers'],
            ['name' => 'teachers.assign', 'display_name' => 'Таъинот ба фан', 'module' => 'teachers'],

            // Журнал
            ['name' => 'journal.view', 'display_name' => 'Дидани журнал', 'module' => 'journal'],
            ['name' => 'journal.attendance', 'display_name' => 'Сабти давомот', 'module' => 'journal'],
            ['name' => 'journal.grades', 'display_name' => 'Сабти баҳо', 'module' => 'journal'],
            ['name' => 'journal.ratings', 'display_name' => 'Сабти рейтинг', 'module' => 'journal'],
            ['name' => 'journal.finalize', 'display_name' => 'Тасдиқи баҳои ниҳоӣ', 'module' => 'journal'],
            ['name' => 'journal.edit_finalized', 'display_name' => 'Таҳрири баҳои тасдиқшуда', 'module' => 'journal'],

            // Рейтингҳо
            ['name' => 'ratings.view', 'display_name' => 'Дидани рейтингҳо', 'module' => 'ratings'],

            // Имтиҳон
            ['name' => 'exams.view', 'display_name' => 'Дидани имтиҳонҳо', 'module' => 'exams'],
            ['name' => 'exams.create', 'display_name' => 'Сохтани имтиҳон', 'module' => 'exams'],
            ['name' => 'exams.edit', 'display_name' => 'Таҳрири имтиҳон', 'module' => 'exams'],
            ['name' => 'exams.delete', 'display_name' => 'Нест кардани имтиҳон', 'module' => 'exams'],
            ['name' => 'exams.manage_questions', 'display_name' => 'Идоракунии саволҳо', 'module' => 'exams'],
            ['name' => 'exams.grade', 'display_name' => 'Баҳогузории имтиҳон', 'module' => 'exams'],
            ['name' => 'exams.take', 'display_name' => 'Супоридани имтиҳон', 'module' => 'exams'],

            // Қарздорӣ
            ['name' => 'debts.view', 'display_name' => 'Дидани қарздориҳо', 'module' => 'debts'],
            ['name' => 'debts.manage', 'display_name' => 'Идоракунии қарздориҳо', 'module' => 'debts'],
            ['name' => 'debts.resolve', 'display_name' => 'Ҳал кардани қарздорӣ', 'module' => 'debts'],

            // Transcript
            ['name' => 'transcript.view', 'display_name' => 'Дидани transcript', 'module' => 'transcript'],
            ['name' => 'transcript.generate', 'display_name' => 'Сохтани transcript', 'module' => 'transcript'],
            ['name' => 'transcript.export', 'display_name' => 'Содироти transcript', 'module' => 'transcript'],

            // Ҳисоботҳо
            ['name' => 'reports.view', 'display_name' => 'Дидани ҳисоботҳо', 'module' => 'reports'],
            ['name' => 'reports.export', 'display_name' => 'Содироти ҳисоботҳо', 'module' => 'reports'],

            // Аудит
            ['name' => 'audit.view', 'display_name' => 'Дидани аудит', 'module' => 'audit'],

            // Танзимот
            ['name' => 'settings.view', 'display_name' => 'Дидани танзимот', 'module' => 'settings'],
            ['name' => 'settings.edit', 'display_name' => 'Таҳрири танзимот', 'module' => 'settings'],
        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(['name' => $perm['name']], $perm);
        }

        // ====== ТАЪИНОТИИ ИҶОЗАТҲО БА НАҚШҲО ======
        $rolePermissions = [
            'super_admin' => Permission::pluck('id')->toArray(), // Ҳама

            'admin' => Permission::whereNotIn('name', ['settings.edit'])
                ->pluck('id')->toArray(),

            'dean' => Permission::whereIn('module', ['students', 'teachers', 'journal', 'ratings', 'debts', 'transcript', 'reports'])
                ->where('name', 'not like', '%.delete%')
                ->where('name', 'not like', '%.create%')
                ->pluck('id')->toArray(),

            'vice_dean' => Permission::whereIn('module', ['students', 'teachers', 'journal', 'ratings', 'debts', 'reports'])
                ->where('name', 'like', '%.view%')
                ->pluck('id')->toArray(),

            'department_head' => Permission::whereIn('module', ['teachers', 'journal', 'ratings', 'exams', 'reports'])
                ->pluck('id')->toArray(),

            'registrar' => Permission::whereIn('module', ['structure', 'students', 'teachers', 'debts', 'transcript', 'reports'])
                ->pluck('id')->toArray(),

            'teacher' => Permission::whereIn('name', [
                'journal.view', 'journal.attendance', 'journal.grades', 'journal.ratings', 'journal.finalize',
                'exams.view', 'exams.create', 'exams.edit', 'exams.manage_questions', 'exams.grade',
                'ratings.view', 'students.view',
            ])->pluck('id')->toArray(),

            'accountant' => Permission::whereIn('name', [
                'students.view', 'debts.view', 'reports.view', 'reports.export',
            ])->pluck('id')->toArray(),

            'operator' => Permission::whereIn('name', [
                'students.view', 'journal.view', 'ratings.view', 'reports.view',
            ])->pluck('id')->toArray(),

            'student' => Permission::whereIn('name', [
                'exams.take', 'ratings.view', 'transcript.view',
            ])->pluck('id')->toArray(),
        ];

        foreach ($rolePermissions as $roleName => $permissionIds) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->permissions()->sync($permissionIds);
            }
        }
    }
}
