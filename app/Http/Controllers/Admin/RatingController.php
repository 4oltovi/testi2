<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\Semester;
use App\Services\RatingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RatingController extends Controller
{
    private RatingService $ratingService;

    public function __construct(RatingService $ratingService)
    {
        $this->ratingService = $ratingService;
    }

    /**
     * НАВ: рейтинги гурӯҳҳо бо GPA миёна илова шуд
     */
    public function index(Request $request): View
    {
        [$semesters, $academicYears, $semesterId, $academicYearId] = $this->resolveSemesterFilters($request);

        $faculties = Faculty::active()->orderBy('sort_order')->get();
        $groups = Group::active()->orderBy('name')->get();

        $facultyRating = $semesterId ? $this->ratingService->getFacultyRating($semesterId) : collect();
        $topStudents = $semesterId ? $this->ratingService->getTopStudents($semesterId, 10) : collect();

        // Рейтинги гурӯҳҳо (бо GPA миёна) — як дархост, бе N+1
        $groupStats = Group::active()
            ->with('activeStudents')
            ->get()
            ->map(function ($g) {
                return [
                    'id'       => $g->id,
                    'name'     => $g->name,
                    'avg_gpa'  => round((float) $g->activeStudents->avg('cumulative_gpa'), 2),
                    'students' => $g->activeStudents->count(),
                ];
            })
            ->sortByDesc('avg_gpa')
            ->values();

        return view('admin.ratings.index', compact(
            'semesters',
            'faculties',
            'groups',
            'academicYears',
            'semesterId',
            'academicYearId',
            'facultyRating',
            'topStudents',
            'groupStats'
        ));
    }

    public function group(Group $group, Request $request): View
    {
        [$semesters, $academicYears, $semesterId, $academicYearId] = $this->resolveSemesterFilters($request);

        $groupRating = $semesterId ? $this->ratingService->getGroupRating($group->id, $semesterId) : collect();
        $group->load(['specialty.faculty', 'course']);

        return view('admin.ratings.group', compact('group', 'groupRating', 'semesters', 'semesterId', 'academicYears', 'academicYearId'));
    }

    public function faculty(Faculty $faculty, Request $request): View
    {
        [$semesters, $academicYears, $semesterId, $academicYearId] = $this->resolveSemesterFilters($request);

        $groupsRating = $semesterId
            ? $this->ratingService->getGroupsRating($semesterId, $faculty->id)
            : collect();

        $topStudents = $semesterId
            ? $this->ratingService->getTopStudents($semesterId, 20, $faculty->id)
            : collect();

        return view('admin.ratings.faculty', compact('faculty', 'groupsRating', 'topStudents', 'semesters', 'semesterId', 'academicYears', 'academicYearId'));
    }

    public function topStudents(Request $request): View
    {
        [$semesters, $academicYears, $semesterId, $academicYearId] = $this->resolveSemesterFilters($request);

        $faculties = Faculty::active()->get();
        $facultyId = $request->get('faculty_id');

        $topStudents = $semesterId
            ? $this->ratingService->getTopStudents($semesterId, 50, $facultyId)
            : collect();

        return view('admin.ratings.top-students', compact('topStudents', 'semesters', 'semesterId', 'academicYears', 'academicYearId', 'faculties'));
    }

    public function statements(Request $request)
    {
        [$semesters, $academicYears, $semesterId, $academicYearId] = $this->resolveSemesterFilters($request);
        $groups = Group::active()->orderBy('name')->get();
        $selectedGroupId = $request->get('group_id');

        $vedomosts = [];

        if ($selectedGroupId && $semesterId) {
            $group = Group::with('activeStudents')->find($selectedGroupId);

            if ($group) {
                $subjectAssignments = \App\Models\SubjectAssignment::where('semester_id', $semesterId)
                    ->where('group_id', $selectedGroupId)
                    ->with(['subject', 'teacher'])
                    ->get();

                foreach ($subjectAssignments as $assignment) {
                    $studentGrades = \App\Models\SemesterGrade::where('subject_assignment_id', $assignment->id)
                        ->where('semester_id', $semesterId)
                        ->whereIn('student_id', $group->activeStudents->pluck('id'))
                        ->with('student')
                        ->get()
                        ->keyBy('student_id');

                    $studentsData = $group->activeStudents->map(function ($student) use ($studentGrades) {
                        $grade = $studentGrades->get($student->id);
                        return [
                            'id' => $student->id,
                            'student_id_number' => $student->student_id_number,
                            'full_name' => $student->full_name,
                            'rating1_score' => $grade?->rating1_score,
                            'rating2_score' => $grade?->rating2_score,
                        ];
                    })->sortBy('full_name')->values();

                    $vedomosts[] = [
                        'subject' => $assignment->subject,
                        'teacher' => $assignment->teacher,
                        'group' => $group,
                        'semester' => \App\Models\Semester::find($semesterId),
                        'students' => $studentsData,
                    ];
                }
            }
        }

        if ($request->get('download') === 'pdf' && !empty($vedomosts)) {
            $pdf = \PDF::loadView('admin.ratings.statements_pdf', [
                'vedomosts' => $vedomosts,
                'institutionName' => \App\Models\Setting::get('institution_name', 'Номи муассиса'),
                'logo' => \App\Models\Setting::get('logo') ? asset('storage/' . \App\Models\Setting::get('logo')) : null,
            ]);
            return $pdf->download('vedomost-rating-' . $group->full_name . '.pdf');
        }

        $institutionName = \App\Models\Setting::get('institution_name', 'Номи муассиса');
        $logo = \App\Models\Setting::get('logo') ? asset('storage/' . \App\Models\Setting::get('logo')) : null;

        return view('admin.ratings.statements', compact(
            'semesters',
            'academicYears',
            'semesterId',
            'academicYearId',
            'groups',
            'selectedGroupId',
            'vedomosts',
            'institutionName',
            'logo'
        ));
    }


    /**
     * Рӯйхати семестрҳо ва филтри асосӣ
     */
    private function resolveSemesterFilters(Request $request): array
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $academicYearId = $request->get('academic_year_id', \App\Models\Setting::get('current_academic_year_id'));

        $semesterQuery = Semester::with('academicYear');
        if ($academicYearId) {
            $semesterQuery->where('academic_year_id', $academicYearId);
        }
        $semesters = $semesterQuery->orderBy('name')->get();

        $semesterId = $request->get('semester_id', \App\Models\Setting::get('current_semester_id') ?? \App\Models\Semester::current()?->id);

        return [$semesters, $academicYears, $semesterId, $academicYearId];
    }
}