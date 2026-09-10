<?php

namespace App\Traits;

trait ResolvesDeanFaculty
{
    protected static ?int $deanFacultyIdCache = null;

    protected function facultyId(): ?int
    {
        $user = request()->user();

        if (!$user || !$user->isDean()) {
            return null;
        }

        if (static::$deanFacultyIdCache === null) {
            static::$deanFacultyIdCache = $user->deanFaculty?->id;
        }

        return static::$deanFacultyIdCache;
    }

    protected function applyFacultyScope($query, string $relationChain = 'specialty.department.faculty')
    {
        $facultyId = $this->facultyId();

        if ($facultyId) {
            return $query->whereHas($relationChain, fn($q) => $q->where('faculty_id', $facultyId));
        }

        return $query;
    }

    protected function abortIfFacultyMismatch($model): void
    {
        $facultyId = $this->facultyId();

        if ($facultyId && $model) {
            $modelFacultyId = $this->extractFacultyId($model);
            if ($modelFacultyId !== null && $modelFacultyId !== $facultyId) {
                abort(403, 'Шумо ба ин саҳифа дастрасӣ надоред.');
            }
        }
    }

    protected function extractFacultyId($model): ?int
    {
        return match (get_class($model)) {
            \App\Models\Student::class => $model->specialty?->department?->faculty?->id,
            \App\Models\Teacher::class => $model->department?->faculty?->id,
            \App\Models\Group::class => $model->specialty?->department?->faculty?->id,
            \App\Models\Specialty::class => $model->department?->faculty?->id,
            \App\Models\Subject::class => $model->department?->faculty?->id,
            \App\Models\AcademicDebt::class => $model->student?->specialty?->department?->faculty?->id,
            \App\Models\SubjectAssignment::class => $model->group?->specialty?->department?->faculty?->id,
            \App\Models\SemesterGrade::class => $model->subjectAssignment?->group?->specialty?->department?->faculty?->id,
            default => null,
        };
    }
}
