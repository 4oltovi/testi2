<?php

namespace App\Services;

use App\Models\Group;
use App\Models\SemesterGrade;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * Хидмати рейтингҳо
 * Рейтинги донишҷӯён, гурӯҳҳо, факултетҳо
 */
class RatingService
{
    /**
     * Рейтинги донишҷӯён дар як фан (subject assignment)
     */
    public function getSubjectRating(int $subjectAssignmentId, int $semesterId): Collection
    {
        return SemesterGrade::where('subject_assignment_id', $subjectAssignmentId)
            ->where('semester_id', $semesterId)
            ->with('student.user')
            ->orderByDesc('total_score')
            ->get()
            ->values()
            ->map(function ($grade, $index) {
                return [
                    'rank' => $index + 1,
                    'student_name' => $grade->student?->user?->full_name,
                    'student_id' => $grade->student_id,
                    'total_score' => $grade->total_score,
                    'letter_grade' => $grade->letter_grade,
                    'grade_point' => $grade->grade_point,
                    'traditional_grade' => $grade->traditional_grade,
                    'status' => $grade->status,
                ];
            });
    }

    /**
     * Рейтинги донишҷӯён дар як гурӯҳ (аз рӯйи GPA)
     */
    public function getGroupRating(int $groupId, int $semesterId): Collection
    {
        $students = Student::where('group_id', $groupId)
            ->active()
            ->with(['user', 'semesterGpas' => fn($q) => $q->where('semester_id', $semesterId)])
            ->get()
            ->sortByDesc(fn($s) => $s->semesterGpas->first()?->gpa ?? 0)
            ->values();

        return $students->map(function ($student, $index) use ($semesterId) {
            $gpa = $student->semesterGpas->first();
            return [
                'rank' => $index + 1,
                'student_name' => $student->user?->full_name,
                'student_id' => $student->id,
                'gpa' => $gpa?->gpa ?? 0,
                'cumulative_gpa' => $gpa?->cumulative_gpa ?? $student->cumulative_gpa,
                'credits_earned' => $gpa?->credits_earned ?? 0,
                'subjects_passed' => $gpa?->subjects_passed ?? 0,
                'subjects_failed' => $gpa?->subjects_failed ?? 0,
                'has_debts' => $student->has_debts,
            ];
        });
    }

    /**
     * Рейтинги гурӯҳҳо дар як курс/факултет
     */
    public function getGroupsRating(int $semesterId, ?int $facultyId = null, ?int $courseId = null): Collection
    {
        $query = Group::active()
            ->with(['specialty.department.faculty', 'course', 'activeStudents.semesterGpas' => fn($q) => $q->where('semester_id', $semesterId)]);

        if ($facultyId) {
            $query->whereHas('specialty.department', fn($q) => $q->where('faculty_id', $facultyId));
        }

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        $groups = $query->get();

        return $groups->map(function ($group) use ($semesterId) {
            $students = $group->activeStudents;
            $gpas = $students->flatMap->semesterGpas;

            $avgGpa = $gpas->avg('gpa') ?? 0;
            $studentsWithDebts = $students->where('has_debts', true)->count();
            $totalStudents = $students->count();

            return [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'specialty' => $group->specialty?->name,
                'faculty' => $group->specialty?->department?->faculty?->short_name,
                'course' => $group->course?->number,
                'avg_gpa' => round($avgGpa, 2),
                'total_students' => $totalStudents,
                'students_with_debts' => $studentsWithDebts,
                'debt_percentage' => $totalStudents > 0
                    ? round(($studentsWithDebts / $totalStudents) * 100, 1)
                    : 0,
                'quality_percentage' => $totalStudents > 0
                    ? round((($totalStudents - $studentsWithDebts) / $totalStudents) * 100, 1)
                    : 0,
            ];
        })
        ->sortByDesc('avg_gpa')
        ->values()
        ->map(function ($item, $index) {
            $item['rank'] = $index + 1;
            return $item;
        });
    }

    /**
     * Рейтинги факултетҳо
     */
    public function getFacultyRating(int $semesterId): Collection
    {
        $groups = $this->getGroupsRating($semesterId);

        return $groups->groupBy('faculty')
            ->map(function ($facultyGroups, $facultyName) {
                return [
                    'faculty_name' => $facultyName,
                    'avg_gpa' => round($facultyGroups->avg('avg_gpa'), 2),
                    'total_students' => $facultyGroups->sum('total_students'),
                    'total_groups' => $facultyGroups->count(),
                    'students_with_debts' => $facultyGroups->sum('students_with_debts'),
                    'debt_percentage' => round($facultyGroups->avg('debt_percentage'), 1),
                ];
            })
            ->sortByDesc('avg_gpa')
            ->values()
            ->map(function ($item, $index) {
                $item['rank'] = $index + 1;
                return $item;
            });
    }

    /**
     * Топ-10 донишҷӯёни беҳтарин
     */
    public function getTopStudents(int $semesterId, int $limit = 10, ?int $facultyId = null): Collection
    {
        $query = Student::active()
            ->with(['user', 'group', 'specialty', 'semesterGpas' => fn($q) => $q->where('semester_id', $semesterId)]);

        if ($facultyId) {
            $query->whereHas('specialty.department', fn($q) => $q->where('faculty_id', $facultyId));
        }

        return $query->get()
            ->sortByDesc(fn($s) => $s->semesterGpas->first()?->gpa ?? 0)
            ->take($limit)
            ->values()
            ->map(function ($student, $index) {
                $gpa = $student->semesterGpas->first();
                return [
                    'rank' => $index + 1,
                    'student_name' => $student->user?->full_name,
                    'group' => $student->group?->name,
                    'specialty' => $student->specialty?->name,
                    'gpa' => $gpa?->gpa ?? 0,
                    'cumulative_gpa' => $student->cumulative_gpa,
                ];
            });
    }
}
