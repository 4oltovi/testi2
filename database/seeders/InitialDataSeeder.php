<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Specialty;
use App\Models\Subject;
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

        // ====== МУАССИСА ======
        $institution = Institution::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Донишгоҳи давлатии тиббии Тоҷикистон',
                'short_name' => 'ДДТТ',
                'address' => 'ш. Душанбе, кӯч. Маяковский 7',
                'phone' => '+992 372 224-45-67',
                'email' => 'info@tajmedun.tj',
                'website' => 'https://tajmedun.tj',
            ]
        );

        // ====== СОЛИ ТАҲСИЛӢ ======
        $academicYear = AcademicYear::updateOrCreate(
            ['name' => '2024-2025'],
            [
                'start_date' => '2024-09-01',
                'end_date' => '2025-06-30',
                'is_current' => true,
                'status' => 'active',
            ]
        );

        // ====== СЕМЕСТРҲО ======
        $semester1 = Semester::updateOrCreate(
            ['academic_year_id' => $academicYear->id, 'number' => 1],
            [
                'name' => 'Семестри 1',
                'start_date' => '2024-09-01',
                'end_date' => '2025-01-15',
                'exam_start_date' => '2024-12-20',
                'exam_end_date' => '2025-01-15',
                'retake_start_date' => '2025-01-16',
                'retake_end_date' => '2025-01-31',
                'is_current' => false,
                'status' => 'completed',
            ]
        );

        $semester2 = Semester::updateOrCreate(
            ['academic_year_id' => $academicYear->id, 'number' => 2],
            [
                'name' => 'Семестри 2',
                'start_date' => '2025-02-03',
                'end_date' => '2025-06-15',
                'exam_start_date' => '2025-05-25',
                'exam_end_date' => '2025-06-15',
                'retake_start_date' => '2025-06-16',
                'retake_end_date' => '2025-06-30',
                'is_current' => true,
                'status' => 'active',
            ]
        );

        // ====== КУРСҲО ======
        for ($i = 1; $i <= 6; $i++) {
            Course::updateOrCreate(
                ['number' => $i],
                ['name' => "Курси {$i}"]
            );
        }

        // ====== ФАКУЛТЕТҲО ======
        $faculties = [
            ['name' => 'Факултети тиббӣ', 'short_name' => 'ФТ', 'code' => 'FT'],
            ['name' => 'Факултети педиатрия', 'short_name' => 'ФП', 'code' => 'FP'],
            ['name' => 'Факултети фармация', 'short_name' => 'ФФ', 'code' => 'FF'],
            ['name' => 'Факултети стоматология', 'short_name' => 'ФС', 'code' => 'FS'],
        ];

        foreach ($faculties as $fData) {
            Faculty::updateOrCreate(
                ['code' => $fData['code']],
                array_merge($fData, ['institution_id' => $institution->id, 'is_active' => true])
            );
        }

        // ====== КАФЕДРАҲО ======
        $ft = Faculty::where('code', 'FT')->first();
        $departments = [
            ['name' => 'Кафедраи анатомия', 'short_name' => 'Анат', 'code' => 'ANAT', 'faculty_id' => $ft->id],
            ['name' => 'Кафедраи физиология', 'short_name' => 'Физ', 'code' => 'PHYS', 'faculty_id' => $ft->id],
            ['name' => 'Кафедраи биохимия', 'short_name' => 'Биох', 'code' => 'BIOC', 'faculty_id' => $ft->id],
            ['name' => 'Кафедраи гистология', 'short_name' => 'Гист', 'code' => 'HIST', 'faculty_id' => $ft->id],
            ['name' => 'Кафедраи забонҳо', 'short_name' => 'Заб', 'code' => 'LANG', 'faculty_id' => $ft->id],
        ];

        foreach ($departments as $dData) {
            Department::updateOrCreate(
                ['code' => $dData['code']],
                array_merge($dData, ['is_active' => true])
            );
        }

        // ====== ИХТИСОСҲО ======
        $anatDept = Department::where('code', 'ANAT')->first();
        $specialties = [
            [
                'department_id' => $anatDept->id,
                'name' => 'Кори тиббӣ',
                'code' => '1-79 01 01',
                'education_level' => 'specialist',
                'study_years' => 6,
                'total_credits' => 360,
                'study_form' => 'full_time',
            ],
            [
                'department_id' => $anatDept->id,
                'name' => 'Педиатрия',
                'code' => '1-79 01 02',
                'education_level' => 'specialist',
                'study_years' => 6,
                'total_credits' => 360,
                'study_form' => 'full_time',
            ],
        ];

        foreach ($specialties as $sData) {
            Specialty::updateOrCreate(['code' => $sData['code']], $sData);
        }

        // ====== ГУРӮҲҲО ======
        $specialty1 = Specialty::where('code', '1-79 01 01')->first();
        $course1 = Course::where('number', 1)->first();

        $groups = [
            ['name' => 'ТИ-1-24', 'code' => 'TI-1-24'],
            ['name' => 'ТИ-2-24', 'code' => 'TI-2-24'],
            ['name' => 'ТИ-3-24', 'code' => 'TI-3-24'],
        ];

        foreach ($groups as $gData) {
            Group::updateOrCreate(
                ['code' => $gData['code']],
                array_merge($gData, [
                    'specialty_id' => $specialty1->id,
                    'course_id' => $course1->id,
                    'academic_year_id' => $academicYear->id,
                    'max_students' => 25,
                    'is_active' => true,
                ])
            );
        }

        // ====== ФАНҲО ======
        $subjects = [
            ['department_id' => $anatDept->id, 'name' => 'Анатомияи одам', 'code' => 'ANAT101', 'credits' => 6, 'total_hours' => 180, 'lecture_hours' => 60, 'practice_hours' => 60, 'independent_hours' => 60, 'exam_type' => 'exam'],
            ['department_id' => Department::where('code', 'PHYS')->first()->id, 'name' => 'Физиологияи нормалӣ', 'code' => 'PHYS101', 'credits' => 5, 'total_hours' => 150, 'lecture_hours' => 50, 'practice_hours' => 50, 'independent_hours' => 50, 'exam_type' => 'exam'],
            ['department_id' => Department::where('code', 'BIOC')->first()->id, 'name' => 'Биохимия', 'code' => 'BIOC101', 'credits' => 4, 'total_hours' => 120, 'lecture_hours' => 40, 'practice_hours' => 40, 'independent_hours' => 40, 'exam_type' => 'exam'],
            ['department_id' => Department::where('code', 'HIST')->first()->id, 'name' => 'Гистология', 'code' => 'HIST101', 'credits' => 4, 'total_hours' => 120, 'lecture_hours' => 40, 'practice_hours' => 40, 'independent_hours' => 40, 'exam_type' => 'exam'],
            ['department_id' => Department::where('code', 'LANG')->first()->id, 'name' => 'Забони тоҷикӣ', 'code' => 'LANG101', 'credits' => 2, 'total_hours' => 60, 'lecture_hours' => 20, 'practice_hours' => 20, 'independent_hours' => 20, 'exam_type' => 'credit'],
            ['department_id' => Department::where('code', 'LANG')->first()->id, 'name' => 'Забони англисӣ', 'code' => 'LANG102', 'credits' => 3, 'total_hours' => 90, 'lecture_hours' => 30, 'practice_hours' => 30, 'independent_hours' => 30, 'exam_type' => 'credit'],
        ];

        foreach ($subjects as $subData) {
            Subject::updateOrCreate(['code' => $subData['code']], array_merge($subData, ['is_active' => true]));
        }

        // ====== АУДИТОРИЯҲО ======
        $classrooms = [
            ['name' => '101', 'building' => 'Бинои 1', 'floor' => 1, 'capacity' => 100, 'type' => 'lecture', 'has_projector' => true],
            ['name' => '201', 'building' => 'Бинои 1', 'floor' => 2, 'capacity' => 30, 'type' => 'practice'],
            ['name' => '202', 'building' => 'Бинои 1', 'floor' => 2, 'capacity' => 30, 'type' => 'practice'],
            ['name' => '301', 'building' => 'Бинои 1', 'floor' => 3, 'capacity' => 25, 'type' => 'lab', 'has_computers' => true],
            ['name' => '302', 'building' => 'Бинои 1', 'floor' => 3, 'capacity' => 20, 'type' => 'computer', 'has_computers' => true, 'has_projector' => true],
        ];

        foreach ($classrooms as $cData) {
            Classroom::updateOrCreate(
                ['name' => $cData['name'], 'building' => $cData['building']],
                array_merge($cData, ['is_active' => true])
            );
        }

        // ====== ВАҚТНОМАИ ДАРСҲО ======
        $lessonTimes = [
            ['number' => 1, 'start_time' => '08:00', 'end_time' => '09:20', 'label' => 'Дарси 1'],
            ['number' => 2, 'start_time' => '09:30', 'end_time' => '10:50', 'label' => 'Дарси 2'],
            ['number' => 3, 'start_time' => '11:00', 'end_time' => '12:20', 'label' => 'Дарси 3'],
            ['number' => 4, 'start_time' => '13:00', 'end_time' => '14:20', 'label' => 'Дарси 4'],
            ['number' => 5, 'start_time' => '14:30', 'end_time' => '15:50', 'label' => 'Дарси 5'],
            ['number' => 6, 'start_time' => '16:00', 'end_time' => '17:20', 'label' => 'Дарси 6'],
        ];

        foreach ($lessonTimes as $lt) {
            \App\Models\LessonTime::updateOrCreate(['number' => $lt['number']], $lt);
        }
    }
}
