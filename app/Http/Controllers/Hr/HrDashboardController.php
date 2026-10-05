<?php

namespace App\Http\Controllers\Hr;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Панели асосӣи Кадр.
 *
 * Танҳо ҳисобҳои корманд ва сӣ тезтасдиқи шахсияти донишҷӯ — бе ягон
 * рақами баҳо, қарз ё пул.
 */
class HrDashboardController extends Controller
{
    /** Танҳо корбарони корӣ — суперадмин, админ ва донишҷӯ дар барҳисоб нестанд. */
    private const EMPLOYEE_ROLES = [
        UserRole::TEACHER->value,
        UserRole::DEAN->value,
        UserRole::VICE_DEAN->value,
        UserRole::DEPARTMENT_HEAD->value,
        UserRole::ACCOUNTANT->value,
        UserRole::OPERATOR->value,
        UserRole::HR->value,
    ];

    public function index(Request $request): View
    {
        Gate::authorize('manageAny', $request->user());

        // Ҳамаи ҳисобҳо аз як зиёр аст: «корбари корӣ» дар як шарт.
        $employeeQuery = fn () => User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', self::EMPLOYEE_ROLES))
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', [
                UserRole::SUPER_ADMIN->value,
                UserRole::ADMIN->value,
                UserRole::STUDENT->value,
            ]));

        $stats = [
            'total' => $employeeQuery()->count(),
            'active' => $employeeQuery()->where('status', 'active')->count(),
            'inactive' => $employeeQuery()->whereIn('status', ['inactive', 'blocked'])->count(),
            'teachers' => $employeeQuery()
                ->whereHas('roles', fn ($q) => $q->where('name', UserRole::TEACHER->value))
                ->count(),
        ];

        // Шумораи корманд аз ҳар як нақш
        $perRole = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', self::EMPLOYEE_ROLES))
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', [
                UserRole::SUPER_ADMIN->value,
                UserRole::ADMIN->value,
                UserRole::STUDENT->value,
            ]))
            ->with('roles')
            ->get(['id'])
            ->flatMap(fn ($u) => $u->roles->pluck('name'))
            ->countBy()
            ->all();

        // Ҷумҳаи фавран дар корманд
        $searchTerm = trim((string) $request->query('q', ''));
        $quickResults = null;

        if ($searchTerm !== '') {
            $like = '%' . $searchTerm . '%';
            $quickResults = $employeeQuery()
                ->where(function ($q) use ($like) {
                    $q->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('middle_name', 'like', $like)
                        ->orWhere('login', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                })
                ->with('roles')
                ->orderBy('last_name')
                ->limit(10)
                ->get();
        }

        // Тезитасдиқи донишҷӯ: ном ё рақами донишҷӯ. Танҳо identity.
        $studentTerm = trim((string) $request->query('student', ''));
        $studentResults = null;

        if ($studentTerm !== '') {
            $like = '%' . $studentTerm . '%';
            $studentResults = Student::query()
                ->where(function ($q) use ($like) {
                    $q->where('student_id_number', 'like', $like)
                        ->orWhereHas('user', fn ($uq) => $uq->where(function ($uq) use ($like) {
                            $uq->where('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('middle_name', 'like', $like);
                        }));
                })
                ->with(['user:id,first_name,last_name,middle_name,avatar', 'group:id,name'])
                ->orderBy('last_name')
                ->limit(10)
                ->get();
        }

        return view('hr.dashboard', [
            'stats' => $stats,
            'perRole' => $perRole,
            'roleLabels' => collect(UserRole::employeeRoles())
                ->mapWithKeys(fn (string $slug) => [$slug => UserRole::from($slug)->label()]),
            'searchTerm' => $searchTerm,
            'quickResults' => $quickResults,
            'studentTerm' => $studentTerm,
            'studentResults' => $studentResults,
        ]);
    }
}