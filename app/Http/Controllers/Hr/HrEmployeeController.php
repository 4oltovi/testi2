<?php

namespace App\Http\Controllers\Hr;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Policies\HrPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Кадр — идоракунии корманд.
 *
 * «Корманд» = корбарони корӣ, ки суперадмин, админ ва донишҷӉ НЕ ҳастанд
 * (таърифи марказӣ дар `UserRole::employeeRoles()`).
 *
 * Ҳамаи қоидаҳо дар `HrPolicy` ҷойгиранд; дар ин контроллер танҳо такрори
 * ҳифзи server-side аст, ки ҳеҷ гоҳа ишорати онро аз UI гузаридан нашавад.
 */
class HrEmployeeController extends Controller
{
    public function __construct(private readonly HrPolicy $policy) {}

    /**
     * Рӯйхати корманд: ҷумҳа, филтр аз роли ва ҳолат, пагинация.
     */
    public function index(Request $request): View
    {
        Gate::authorize('manageAny', $request->user());

        $query = User::query()
            // Танҳо корбарони корӣ...
            ->whereHas('roles', fn ($q) => $q->whereIn('name', UserRole::employeeRoles()))
            // ... ва сӣ НА админ/суперадмин/донишҷӯ (ҳатто агар дар як вақт
            // нақши корӣ ҳам дошта бошанд).
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', $this->forbiddenRoleNames()));

        // Ҷумҳа аз ном, login ё телефон
        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('middle_name', 'like', $like)
                    ->orWhere('login', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        // Филтр аз рол
        if ($role = (string) $request->query('role', '')) {
            if (in_array($role, UserRole::employeeRoles(), true)) {
                $query->whereHas('roles', fn ($q) => $q->where('name', $role));
            }
        }

        // Филтр аз ҳолат
        if ($status = (string) $request->query('status', '')) {
            if (in_array($status, ['active', 'inactive', 'blocked'], true)) {
                $query->where('status', $status);
            }
        }

        $employees = $query->with('roles')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        return view('hr.employees.index', [
            'employees' => $employees,
            'roles' => $this->selectableRoles(),
            'employeeRoleSlugs' => UserRole::employeeRoles(),
        ]);
    }

    public function show(User $employee): View
    {
        Gate::authorize('manageView', $employee);

        $employee->loadMissing(['roles', 'teacher']);

        return view('hr.employees.show', [
            'employee' => $employee,
            'roles' => $this->selectableRoles(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('manageCreate', $request->user());

        return view('hr.employees.create', [
            'employee' => new User(['status' => 'active']),
            'roles' => $this->selectableRoles(),
            'selectedRoleIds' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manageCreate', $request->user());

        $data = $this->validated($request);

        $user = new User();
        $user->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'login' => $data['login'],
            'status' => $data['status'] ?? 'active',
        ]);
        $user->password = Hash::make($data['password']);
        $user->save();

        $user->roles()->sync($this->resolveRoleIds($data['roles']));

        AuditLog::log(
            'create',
            "Кадр кормандро сохта шуд: {$user->full_name} (login: {$user->login})",
            User::class,
            $user->id,
            null,
            ['login' => $user->login, 'status' => $user->status],
        );

        return redirect()->route('hr.employees.show', $user)
            ->with('success', "Корманд «{$user->full_name}» бомуваффақият сохта шуд.");
    }

    public function edit(User $employee): View
    {
        Gate::authorize('manageUpdate', $employee);

        $employee->loadMissing('roles');

        return view('hr.employees.edit', [
            'employee' => $employee,
            'roles' => $this->selectableRoles(),
            'selectedRoleIds' => $employee->roles->pluck('id')->all(),
            // Кадр нақши худро иваз карда наметавонад — чекбоксхо қайта
            // интихобнашаванд ва дар сервер ҳам санҷида мешаванд.
            'canChangeRoles' => $this->policy->manageChangeRoles(request()->user(), $employee),
        ]);
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        Gate::authorize('manageUpdate', $employee);

        $data = $this->validated($request, $employee);

        // Ҳифзи аз UI: нақши худро иваз кардан мумкин нест.
        if ($request->filled('roles')) {
            Gate::authorize('manageChangeRoles', $employee);
        }

        $beforeRoles = $employee->roles->pluck('name')->sort()->values()->all();

        $employee->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'login' => $data['login'],
            'status' => $data['status'] ?? $employee->status,
        ]);

        if (! empty($data['password'])) {
            $employee->password = Hash::make($data['password']);
        }

        $employee->save();

        if ($request->filled('roles')) {
            $employee->roles()->sync($this->resolveRoleIds($data['roles']));
        }

        $afterRoles = $employee->roles()->pluck('name')->sort()->values()->all();

        AuditLog::log(
            'update',
            "Кадр кормандро таҳрир кард: {$employee->full_name} (login: {$employee->login})",
            User::class,
            $employee->id,
            ['roles' => $beforeRoles],
            [
                'roles' => $afterRoles,
                'status' => $employee->status,
                'login' => $employee->login,
            ],
        );

        if ($beforeRoles !== $afterRoles) {
            AuditLog::log(
                'role_change',
                "Кадр нақши кормандро иваз кард: {$employee->full_name} — "
                    . implode(', ', $beforeRoles) . ' → ' . implode(', ', $afterRoles),
                User::class,
                $employee->id,
                ['roles' => $beforeRoles],
                ['roles' => $afterRoles],
            );
        }

        return redirect()->route('hr.employees.show', $employee)
            ->with('success', "Маълумоти корманд «{$employee->full_name}» барошид шуд.");
    }

    /**
     * Фаъол ⇄ ғайрифаъол кардани корманд.
     *
     * Нест кардан (DELETE) дар ин панели мавҷуд НЕСТ — ҳамон ки тавсия шудааст,
     * ғайрифаъол кардан бехатартар аст.
     */
    public function toggleStatus(Request $request, User $employee): RedirectResponse
    {
        Gate::authorize('manageToggleStatus', $employee);

        $newStatus = $request->boolean('activate')
            ? 'active'
            : 'inactive';

        $oldStatus = $employee->status;

        $employee->update(['status' => $newStatus]);

        $word = $newStatus === 'active' ? 'фаъол карда шуд' : 'ғайрифаъол карда шуд';

        AuditLog::log(
            $newStatus === 'active' ? 'activate' : 'deactivate',
            "Корманд «{$employee->full_name}» {$word}",
            User::class,
            $employee->id,
            ['status' => $oldStatus],
            ['status' => $newStatus],
        );

        return back()->with('success', "Корманд «{$employee->full_name}» {$word}.");
    }

    // =========================================================
    // Ёргашҳо
    // =========================================================

    /**
     * Санҷидани воситаҳои такдир. Нақшҳо танҳо аз рӯйхати корманд қабул
     * мешаванд — админ, суперадмин ва донишҷӯ сарбории Rule::notIn доранд.
     */
    private function validated(Request $request, ?User $employee = null): array
    {
        $allowedRoleIds = $this->selectableRoles()->pluck('id')->all();
        $forbiddenRoleIds = Role::whereIn('name', $this->forbiddenRoleNames())->pluck('id')->all();

        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'email' => [
                'nullable', 'email', 'max:100',
                Rule::unique('users', 'email')->ignore($employee?->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'login' => [
                'required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_.-]+$/',
                Rule::unique('users', 'login')->ignore($employee?->id),
            ],
            'status' => ['required', Rule::in(['active', 'inactive', 'blocked'])],
            // Дар вақти сохтан ҳатмӣ, дар вақти таҳрир ихтиёрӣ (барои reset).
            'password' => [$employee ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'roles' => ['array'],
            'roles.*' => [
                'integer',
                Rule::in($allowedRoleIds),
                Rule::notIn($forbiddenRoleIds),
            ],
        ], [], [
            'roles.*' => 'нақш',
            'password' => 'гуфуши парол',
        ]);
    }

    /**
     * Нақшҳое, ки Кадр метавонад интихоб кунад — танҳо корманд.
     */
    private function selectableRoles()
    {
        return Role::whereIn('name', UserRole::assignableEmployeeRoles())
            ->orderByDesc('level')
            ->get();
    }

    /**
     * Нақшҳое, ки Кадр ҳеҷ ҳолат наметавонад диҳад ё бардорад.
     */
    private function forbiddenRoleNames(): array
    {
        return [
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::STUDENT->value,
        ];
    }

    /**
     * Аз рӯйхати нақшҳо — танҳо ID-ҳои корманд. Ҳамаи дигар (ҳатто агар
     * дар POST санҷида нашуда бошанд) боз мешаванд.
     */
    private function resolveRoleIds(array $roles): array
    {
        return Role::whereIn('id', $roles)
            ->whereIn('name', UserRole::assignableEmployeeRoles())
            ->pluck('id')
            ->all();
    }
}