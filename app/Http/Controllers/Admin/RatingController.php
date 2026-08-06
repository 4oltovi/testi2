<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
     * Саҳифаи рейтингҳо
     */
    public function index(Request $request): View
    {
        $currentSemester = Semester::current();
        $semesterId = $request->get('semester_id', $currentSemester?->id);

        $semesters = Semester::with('academicYear')->orderByDesc('start_date')->get();
        $faculties = Faculty::active()->orderBy('sort_order')->get();
        $groups = Group::active()->orderBy('name')->get();

        // Рейтинги факултетҳо
        $facultyRating = $semesterId ? $this->ratingService->getFacultyRating($semesterId) : collect();

        // Топ-10 донишҷӯён
        $topStudents = $semesterId ? $this->ratingService->getTopStudents($semesterId, 10) : collect();

        return view('admin.ratings.index', compact(
            'semesters', 'faculties', 'groups', 'currentSemester',
            'semesterId', 'facultyRating', 'topStudents'
        ));
    }

    /**
     * Рейтинги як гурӯҳ
     */
    public function group(Group $group, Request $request): View
    {
        $currentSemester = Semester::current();
        $semesterId = $request->get('semester_id', $currentSemester?->id);
        $semesters = Semester::with('academicYear')->orderByDesc('start_date')->get();

        $groupRating = $semesterId ? $this->ratingService->getGroupRating($group->id, $semesterId) : collect();

        $group->load(['specialty.department.faculty', 'course']);

        return view('admin.ratings.group', compact('group', 'groupRating', 'semesters', 'semesterId'));
    }

    /**
     * Рейтинги як факултет
     */
    public function faculty(Faculty $faculty, Request $request): View
    {
        $currentSemester = Semester::current();
        $semesterId = $request->get('semester_id', $currentSemester?->id);
        $semesters = Semester::with('academicYear')->orderByDesc('start_date')->get();

        $groupsRating = $semesterId
            ? $this->ratingService->getGroupsRating($semesterId, $faculty->id)
            : collect();

        $topStudents = $semesterId
            ? $this->ratingService->getTopStudents($semesterId, 20, $faculty->id)
            : collect();

        return view('admin.ratings.faculty', compact('faculty', 'groupsRating', 'topStudents', 'semesters', 'semesterId'));
    }

    /**
     * Топ донишҷӯён
     */
    public function topStudents(Request $request): View
    {
        $currentSemester = Semester::current();
        $semesterId = $request->get('semester_id', $currentSemester?->id);
        $semesters = Semester::with('academicYear')->orderByDesc('start_date')->get();
        $faculties = Faculty::active()->get();
        $facultyId = $request->get('faculty_id');

        $topStudents = $semesterId
            ? $this->ratingService->getTopStudents($semesterId, 50, $facultyId)
            : collect();

        return view('admin.ratings.top-students', compact('topStudents', 'semesters', 'faculties', 'semesterId', 'facultyId'));
    }
}
