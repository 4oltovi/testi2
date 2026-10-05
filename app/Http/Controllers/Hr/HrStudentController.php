<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Policies\HrPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Кадр — дидани донишҷӯён (танҳо хондан).
 *
 * Рақми тасдиқи шахсият: вақте донишҷӯ маълумотнома (сертификати) сӯҳбад
 * мекунад, Кадр бояд тавонад бинӯрад, ки ин ҳақиқа ӯ ҳамон донишҷӯ аст.
 *
 * ҲАҶ ҲЕҶ як сабаби зиёд аз ин дар ин контроллер НЕ ҲАСТ: ягон нависанда
 * (POST/PUT/PATCH/DELETE) нест, ҳеҷ як роҳи нест кардан нест. Ҳамаи ҳуқуқҳо дар
 * `HrPolicy` ба `false` мегарданд, то ки HTML-и пинҳоншударо аз истифодабаранд
 * ҳифз накунад.
 *
 * Поляҳои пинҳон: баҳо, GPA, кредит, қарздорӣ, ҳазфи ишқтибоӣ, таърихи
 * гузаронидан, ҳисобот ва ҳамаи маълумоти пулӣ.
 */
class HrStudentController extends Controller
{
    public function __construct(private readonly HrPolicy $policy) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyStudent', $request->user());

        $query = Student::query()
            // Танҳо чӣ кор барои тасдиқи шахсият лозим аст. Ҳеҷ қарз,
            // ҳеҷ баҳо, ҳеҷ семестр relationship ба кӯшида намешавад.
            ->with(['user:id,first_name,last_name,middle_name,status,avatar', 'group:id,name', 'specialty:id,name'])
            // Паиваст ба `users` барои ҷорӣ кардан аст, аз ҳамин сабаб номҳои
            // сутунҳо бо `students.` нишон дода мешаван (амбокси: `id`).
            ->select([
                'students.id', 'students.user_id', 'students.group_id',
                'students.specialty_id', 'students.course_id',
                'students.student_id_number', 'students.status',
                'students.enrollment_date', 'students.birth_date',
            ]);

        // Ҷумҳа аз ном, рақами донишҷӯ ё гурӯҳ
        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function (Builder $q) use ($search) {
                $like = '%' . $search . '%';

                $q->where('student_id_number', 'like', $like)
                    ->orWhereHas('user', fn ($uq) => $uq->where(function ($uq) use ($like) {
                        $uq->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('middle_name', 'like', $like);
                    }))
                    ->orWhereHas('group', fn ($gq) => $gq->where('name', 'like', $like));
            });
        }

        if ($groupId = $request->query('group')) {
            $query->where('group_id', $groupId);
        }

        $students = $query
            // `last_name`/`first_name` дар ҷои `students` нест, онҳо дар
            // `users` мебошанд — барои ҷорӣ кардани бо ном бояд гузориш
            // ба ҷои корбар пайваст шавад.
            ->join('users', 'users.id', '=', 'students.user_id')
            ->whereNull('users.deleted_at')
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->paginate(20)
            ->withQueryString();

        return view('hr.students.index', compact('students'));
    }

    public function show(Student $student): View
    {
        Gate::authorize('viewStudent', $student);

        // Ҳамаи алоқаҳо бо ҳадаф аст: танҳо identity.
        $student->loadMissing([
            'user:id,first_name,last_name,middle_name,status,avatar',
            'group:id,name',
            'specialty:id,name',
            'course:id,name',
        ]);

        return view('hr.students.show', compact('student'));
    }
}