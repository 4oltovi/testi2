<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\CurrentGrade;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Semester;
use App\Models\SemesterGrade;
use App\Models\Specialty;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Сохтани маълумоти тестӣ...');

        // Соли таҳсилӣ
        $year = AcademicYear::firstOrCreate(['name' => '2024-2025'], [
            'start_date' => '2024-09-01', 'end_date' => '2025-06-30',
            'is_current' => true, 'status' => 'active',
        ]);

        $sem1 = Semester::firstOrCreate(['academic_year_id' => $year->id, 'number' => 1], [
            'name' => 'Семестри 1', 'start_date' => '2024-09-01', 'end_date' => '2025-01-15',
            'exam_start_date' => '2024-12-20', 'exam_end_date' => '2025-01-15',
            'is_current' => false, 'status' => 'completed',
        ]);

        $sem2 = Semester::firstOrCreate(['academic_year_id' => $year->id, 'number' => 2], [
            'name' => 'Семестри 2', 'start_date' => '2025-02-03', 'end_date' => '2025-06-15',
            'exam_start_date' => '2025-05-25', 'exam_end_date' => '2025-06-15',
            'is_current' => true, 'status' => 'active',
        ]);

        // Муассиса
        $inst = Institution::firstOrCreate(['id' => 1], [
            'name' => 'Донишгоҳи давлатии тиббии Тоҷикистон', 'short_name' => 'ДДТТ',
        ]);

        // Факултет
        $faculty = Faculty::firstOrCreate(['code' => 'FT'], [
            'institution_id' => $inst->id, 'name' => 'Факултети тиббӣ',
            'short_name' => 'ФТ', 'is_active' => true,
        ]);

        // Кафедра
        $dept = Department::firstOrCreate(['code' => 'ANAT'], [
            'faculty_id' => $faculty->id, 'name' => 'Кафедраи анатомия',
            'short_name' => 'Анат', 'is_active' => true,
        ]);

        $deptLang = Department::firstOrCreate(['code' => 'LANG'], [
            'faculty_id' => $faculty->id, 'name' => 'Кафедраи забонҳо',
            'short_name' => 'Заб', 'is_active' => true,
        ]);

        // Ихтисос
        $spec = Specialty::firstOrCreate(['code' => '1-79 01 01'], [
            'department_id' => $dept->id, 'name' => 'Кори тиббӣ',
            'education_level' => 'specialist', 'study_years' => 6,
            'total_credits' => 360, 'study_form' => 'full_time', 'is_active' => true,
        ]);

        // Курсҳо
        $course1 = Course::firstOrCreate(['number' => 1], ['name' => 'Курси 1']);
        $course2 = Course::firstOrCreate(['number' => 2], ['name' => 'Курси 2']);

        // Фанҳо
        $subj1 = Subject::firstOrCreate(['code' => 'ANAT101'], [
            'department_id' => $dept->id, 'name' => 'Анатомияи одам',
            'credits' => 6, 'total_hours' => 180, 'lecture_hours' => 60,
            'practice_hours' => 60, 'independent_hours' => 60, 'exam_type' => 'exam', 'is_active' => true,
        ]);

        $subj2 = Subject::firstOrCreate(['code' => 'LANG101'], [
            'department_id' => $deptLang->id, 'name' => 'Забони тоҷикӣ',
            'credits' => 3, 'total_hours' => 90, 'lecture_hours' => 30,
            'practice_hours' => 30, 'independent_hours' => 30, 'exam_type' => 'exam', 'is_active' => true,
        ]);

        $subj3 = Subject::firstOrCreate(['code' => 'LANG102'], [
            'department_id' => $deptLang->id, 'name' => 'Забони англисӣ',
            'credits' => 3, 'total_hours' => 90, 'lecture_hours' => 30,
            'practice_hours' => 30, 'independent_hours' => 30, 'exam_type' => 'exam', 'is_active' => true,
        ]);

        // Гурӯҳҳо
        $group1 = Group::firstOrCreate(['code' => 'TI-1-24'], [
            'specialty_id' => $spec->id, 'course_id' => $course1->id,
            'academic_year_id' => $year->id, 'name' => 'ТИ-1-24',
            'max_students' => 25, 'is_active' => true,
        ]);

        $group2 = Group::firstOrCreate(['code' => 'TI-2-24'], [
            'specialty_id' => $spec->id, 'course_id' => $course1->id,
            'academic_year_id' => $year->id, 'name' => 'ТИ-2-24',
            'max_students' => 25, 'is_active' => true,
        ]);

        // Нақшаи таълимӣ (curriculum)
        $cur1 = Curriculum::firstOrCreate(
            ['specialty_id' => $spec->id, 'subject_id' => $subj1->id, 'semester_id' => $sem2->id],
            ['course_id' => $course1->id, 'credits' => 6, 'total_hours' => 180,
             'lecture_hours' => 60, 'practice_hours' => 60, 'independent_hours' => 60,
             'exam_type' => 'exam', 'control_type' => 'rating_exam', 'is_active' => true]
        );

        $cur2 = Curriculum::firstOrCreate(
            ['specialty_id' => $spec->id, 'subject_id' => $subj2->id, 'semester_id' => $sem2->id],
            ['course_id' => $course1->id, 'credits' => 3, 'total_hours' => 90,
             'lecture_hours' => 30, 'practice_hours' => 30, 'independent_hours' => 30,
             'exam_type' => 'exam', 'control_type' => 'rating_exam', 'is_active' => true]
        );

        // Омӯзгорон (2 нафар)
        $teacherRole = Role::where('name', 'teacher')->first();
        $studentRole = Role::where('name', 'student')->first();

        $teacher1User = User::firstOrCreate(['login' => 'teacher1'], [
            'first_name' => 'Раҳимов', 'last_name' => 'Аҳмад', 'middle_name' => 'Саидович',
            'password' => Hash::make('teacher123'), 'status' => 'active',
        ]);
        if ($teacherRole) $teacher1User->roles()->syncWithoutDetaching([$teacherRole->id]);
        $teacher1 = Teacher::firstOrCreate(['user_id' => $teacher1User->id], [
            'department_id' => $dept->id, 'employee_id' => 'T-001',
            'position' => 'Доцент', 'employment_type' => 'full_time',
            'rate' => 1.0, 'hire_date' => '2015-09-01', 'status' => 'active',
        ]);

        $teacher2User = User::firstOrCreate(['login' => 'teacher2'], [
            'first_name' => 'Каримова', 'last_name' => 'Малика', 'middle_name' => 'Ғаниевна',
            'password' => Hash::make('teacher123'), 'status' => 'active',
        ]);
        if ($teacherRole) $teacher2User->roles()->syncWithoutDetaching([$teacherRole->id]);
        $teacher2 = Teacher::firstOrCreate(['user_id' => $teacher2User->id], [
            'department_id' => $deptLang->id, 'employee_id' => 'T-002',
            'position' => 'Муаллими калон', 'employment_type' => 'full_time',
            'rate' => 1.0, 'hire_date' => '2018-09-01', 'status' => 'active',
        ]);

        // Таъинот (subject assignments)
        $assign1 = SubjectAssignment::firstOrCreate(
            ['curriculum_id' => $cur1->id, 'teacher_id' => $teacher1User->id, 'group_id' => $group1->id, 'lesson_type' => 'practice'],
            ['semester_id' => $sem2->id, 'hours_per_week' => 4, 'is_active' => true]
        );

        $assign2 = SubjectAssignment::firstOrCreate(
            ['curriculum_id' => $cur2->id, 'teacher_id' => $teacher2User->id, 'group_id' => $group1->id, 'lesson_type' => 'practice'],
            ['semester_id' => $sem2->id, 'hours_per_week' => 2, 'is_active' => true]
        );

        // Донишҷӯён (10 нафар дар гурӯҳи 1)
        $studentNames = [
            ['Алиев', 'Фирдавс', 'Рустамович'],
            ['Бобоева', 'Мадина', 'Шарифовна'],
            ['Ғанизода', 'Комрон', 'Бахтиёрович'],
            ['Давлатов', 'Сорбон', 'Неъматович'],
            ['Зокиров', 'Исмоил', 'Ҳасанович'],
            ['Каримова', 'Нигора', 'Рашидовна'],
            ['Муродов', 'Фаррух', 'Абдулович'],
            ['Назарова', 'Ситора', 'Ғайратовна'],
            ['Раҳимзода', 'Дилшод', 'Маҳмудович'],
            ['Султонов', 'Ҷамшед', 'Акрамович'],
        ];

        $students = [];
        foreach ($studentNames as $i => $name) {
            $sUser = User::firstOrCreate(['login' => 'student' . ($i + 1)], [
                'first_name' => $name[1], 'last_name' => $name[0], 'middle_name' => $name[2],
                'password' => Hash::make('student123'), 'status' => 'active',
            ]);
            if ($studentRole) $sUser->roles()->syncWithoutDetaching([$studentRole->id]);

            $student = Student::firstOrCreate(['user_id' => $sUser->id], [
                'group_id' => $group1->id, 'specialty_id' => $spec->id, 'course_id' => $course1->id,
                'student_id_number' => 'ST-2024-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'record_book_number' => 'ZK-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'birth_date' => '200' . rand(3, 6) . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT),
                'gender' => $i % 3 == 1 ? 'female' : 'male',
                'nationality' => 'Тоҷик', 'citizenship' => 'Тоҷикистон',
                'education_form' => $i < 7 ? 'budget' : 'contract',
                'study_form' => 'full_time',
                'enrollment_date' => '2024-09-01',
                'status' => 'active',
                'cumulative_gpa' => 0,
            ]);
            $students[] = $student;
        }

        // Давомот (2 ҳафта)
        $this->command->info('Сохтани давомот...');
        $statuses = ['present', 'present', 'present', 'present', 'absent', 'present', 'late', 'present', 'present', 'excused'];
        foreach ($students as $si => $student) {
            for ($day = 1; $day <= 10; $day++) {
                $date = date('Y-m-d', strtotime("2025-02-03 +{$day} days"));
                Attendance::firstOrCreate([
                    'student_id' => $student->id,
                    'subject_assignment_id' => $assign1->id,
                    'lesson_date' => $date,
                    'lesson_number' => 1,
                ], [
                    'status' => $statuses[($si + $day) % 10],
                    'marked_by' => $teacher1User->id,
                ]);
            }
        }

        // Баҳоҳои ҷорӣ (ҳафтаи 1-8)
        $this->command->info('Сохтани баҳоҳо...');
        foreach ($students as $si => $student) {
            for ($week = 1; $week <= 8; $week++) {
                $score = rand(45, 100);
                CurrentGrade::firstOrCreate([
                    'student_id' => $student->id,
                    'subject_assignment_id' => $assign1->id,
                    'semester_id' => $sem2->id,
                    'week_number' => $week,
                    'grade_type' => 'classwork',
                ], [
                    'grade_date' => date('Y-m-d', strtotime("2025-02-03 +" . ($week * 7) . " days")),
                    'score' => $score,
                    'max_score' => 100,
                    'graded_by' => $teacher1User->id,
                ]);
            }
        }

        // Баҳоҳои семестрӣ (R1, R2, КМ, Имтиҳон)
        $this->command->info('Сохтани баҳоҳои семестрӣ...');
        $gradeExamples = [
            [85, 90, 88, 92], // Аълочӣ
            [75, 78, 80, 70], // Хуб
            [60, 65, 55, 58], // Миёна
            [70, 72, 68, 75], // Хуб
            [50, 55, 45, 40], // Ғайриқаноат - Fx
            [80, 85, 78, 82], // Хуб
            [90, 88, 92, 95], // Аъло
            [65, 60, 70, 55], // Миёна
            [55, 50, 48, 35], // F - нагузашт
            [72, 78, 75, 80], // Хуб
        ];

        foreach ($students as $si => $student) {
            $grades = $gradeExamples[$si];

            $totalScore = $grades[0] * 0.15 + $grades[1] * 0.15 + $grades[2] * 0.30 + $grades[3] * 0.40;
            $letterGrade = \App\Enums\GradeScale::fromPercentage($totalScore);

            SemesterGrade::firstOrCreate([
                'student_id' => $student->id,
                'subject_assignment_id' => $assign1->id,
                'semester_id' => $sem2->id,
            ], [
                'curriculum_id' => $cur1->id,
                'rating1_score' => $grades[0],
                'rating2_score' => $grades[1],
                'independent_work_score' => $grades[2],
                'exam_score' => $grades[3],
                'total_score' => round($totalScore, 2),
                'letter_grade' => $letterGrade->value,
                'grade_point' => $letterGrade->gradePoint(),
                'traditional_grade' => $letterGrade->traditionalGrade(),
                'credits_earned' => $letterGrade->isPassing() ? 6 : 0,
                'status' => $letterGrade->isPassing() ? 'passed' : ($letterGrade->canRetake() ? 'retake' : 'failed'),
                'is_finalized' => true,
                'finalized_at' => now(),
                'finalized_by' => $teacher1User->id,
            ]);

            // GPA навсозӣ
            $student->update([
                'cumulative_gpa' => $letterGrade->gradePoint(),
                'total_credits_earned' => $letterGrade->isPassing() ? 6 : 0,
                'has_debts' => !$letterGrade->isPassing(),
            ]);
        }

        // 5 донишҷӯ дар гурӯҳи 2
        $this->command->info('Сохтани гурӯҳи 2...');
        $group2Names = [
            ['Ҳасанов', 'Бахтиёр', 'Муродович'],
            ['Олимова', 'Зебо', 'Маҳмадовна'],
            ['Раҷабов', 'Шарифҷон', 'Раҳимович'],
            ['Тоҷиев', 'Рустам', 'Сафарович'],
            ['Холова', 'Фарзона', 'Абдуллоевна'],
        ];

        foreach ($group2Names as $i => $name) {
            $sUser = User::firstOrCreate(['login' => 'student_g2_' . ($i + 1)], [
                'first_name' => $name[1], 'last_name' => $name[0], 'middle_name' => $name[2],
                'password' => Hash::make('student123'), 'status' => 'active',
            ]);
            if ($studentRole) $sUser->roles()->syncWithoutDetaching([$studentRole->id]);

            Student::firstOrCreate(['user_id' => $sUser->id], [
                'group_id' => $group2->id, 'specialty_id' => $spec->id, 'course_id' => $course1->id,
                'student_id_number' => 'ST-2024-' . str_pad(11 + $i, 3, '0', STR_PAD_LEFT),
                'birth_date' => '2005-0' . ($i + 3) . '-1' . $i,
                'gender' => $i % 2 == 0 ? 'male' : 'female',
                'nationality' => 'Тоҷик', 'education_form' => 'budget',
                'study_form' => 'full_time', 'enrollment_date' => '2024-09-01',
                'status' => 'active', 'cumulative_gpa' => 0,
            ]);
        }

        $this->command->info('✅ Маълумоти тестӣ сохта шуд!');
        $this->command->info('   - 2 гурӯҳ (ТИ-1-24: 10 донишҷӯ, ТИ-2-24: 5 донишҷӯ)');
        $this->command->info('   - 2 омӯзгор');
        $this->command->info('   - 3 фан бо нақшаи таълимӣ');
        $this->command->info('   - Давомот (10 рӯз)');
        $this->command->info('   - Баҳоҳои ҷорӣ (8 ҳафта)');
        $this->command->info('   - Баҳоҳои семестрӣ бо GPA');
        $this->command->info('');
        $this->command->info('   Логин омӯзгор: teacher1 / teacher123');
        $this->command->info('   Логин донишҷӯ: student1 / student123');
    }
}
