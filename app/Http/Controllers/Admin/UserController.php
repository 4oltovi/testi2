<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Рӯйхати корбарон
     */
    public function index(Request $request): View
    {
        $query = User::with('roles')->latest();

        // Филтр аз рӯйи ном
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('login', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Филтр аз рӯйи нақш
        if ($role = $request->get('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        // Филтр аз рӯйи ҳолат
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $users = $query->paginate(25)->withQueryString();
        $roles = Role::orderBy('level', 'desc')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Формаи сохтани корбари нав
     */
    public function create(): View
    {
        $roles = Role::orderBy('level', 'desc')->get();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Сабти корбари нав
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => 'required|string|max:50|unique:users,login|alpha_dash',
            'email' => 'nullable|email|max:100|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'password' => 'required|string|min:8|confirmed',
            'status' => 'required|in:active,inactive,blocked',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
        ], [
            'login.required' => 'Логин ҳатмӣ аст.',
            'login.unique' => 'Ин логин аллакай истифода шудааст.',
            'login.alpha_dash' => 'Логин танҳо ҳарфҳо, рақамҳо ва тире дошта метавонад.',
            'first_name.required' => 'Ном ҳатмӣ аст.',
            'last_name.required' => 'Насаб ҳатмӣ аст.',
            'password.required' => 'Парол ҳатмӣ аст.',
            'password.min' => 'Парол бояд ҳадди ақал 8 рамз дошта бошад.',
            'password.confirmed' => 'Тасдиқи парол мувофиқат намекунад.',
            'roles.required' => 'Ҳадди ақал як нақш интихоб кунед.',
        ]);

        $user = User::create([
            'login' => $validated['login'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
        ]);

        // Таъинотии нақшҳо
        $user->roles()->sync($validated['roles']);

        AuditLog::log('create', "Корбари нав сохта шуд: {$user->login}", User::class, $user->id);

        return redirect()->route('admin.users.index')
            ->with('success', "Корбар «{$user->full_name}» бомуваффақият сохта шуд.");
    }

    /**
     * Намоиши корбар
     */
    public function show(User $user): View
    {
        $user->load('roles.permissions');
        return view('admin.users.show', compact('user'));
    }

    /**
     * Формаи таҳрир
     */
    public function edit(User $user): View
    {
        $roles = Role::orderBy('level', 'desc')->get();
        $userRoles = $user->roles->pluck('id')->toArray();
        return view('admin.users.edit', compact('user', 'roles', 'userRoles'));
    }

    /**
     * Навсозии корбар
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'login' => "required|string|max:50|unique:users,login,{$user->id}|alpha_dash",
            'email' => "nullable|email|max:100|unique:users,email,{$user->id}",
            'phone' => 'nullable|string|max:20',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:8|confirmed',
            'status' => 'required|in:active,inactive,blocked',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
        ]);

        $oldValues = $user->toArray();

        $user->update([
            'login' => $validated['login'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'status' => $validated['status'],
        ]);

        // Парол (агар дода шуда бошад)
        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        // Нақшҳо
        $user->roles()->sync($validated['roles']);

        AuditLog::log(
            'update',
            "Корбар навсозӣ шуд: {$user->login}",
            User::class,
            $user->id,
            $oldValues,
            $user->fresh()->toArray()
        );

        return redirect()->route('admin.users.index')
            ->with('success', "Корбар «{$user->full_name}» бомуваффақият навсозӣ шуд.");
    }

    /**
     * Нест кардан (soft delete)
     */
    public function destroy(User $user): RedirectResponse
    {
        // Санҷиш: худатро нест карда наметавонӣ
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Шумо ҳисоби худро нест карда наметавонед.');
        }

        // Санҷиш: super admin-ро танҳо super admin нест карда метавонад
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return back()->with('error', 'Танҳо суперадмин метавонад суперадминро нест кунад.');
        }

        $name = $user->full_name;
        $user->delete(); // soft delete

        AuditLog::log('delete', "Корбар нест карда шуд: {$name}", User::class, $user->id);

        return redirect()->route('admin.users.index')
            ->with('success', "Корбар «{$name}» нест карда шуд.");
    }

    /**
     * Блок кардани корбар
     */
    public function block(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Шумо худро блок карда наметавонед.');
        }

        $user->update(['status' => 'blocked']);
        AuditLog::log('update', "Корбар блок шуд: {$user->login}", User::class, $user->id);

        return back()->with('success', "Корбар «{$user->full_name}» блок карда шуд.");
    }

    /**
     * Фаъол кардани корбар
     */
    public function activate(User $user): RedirectResponse
    {
        $user->update(['status' => 'active']);
        AuditLog::log('update', "Корбар фаъол шуд: {$user->login}", User::class, $user->id);

        return back()->with('success', "Корбар «{$user->full_name}» фаъол карда шуд.");
    }

    /**
     * Бозсозии парол (аз тарафи админ)
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'new_password' => 'required|string|min:8',
        ]);

        $user->update(['password' => Hash::make($request->new_password)]);
        AuditLog::log('update', "Пароли корбар бозсозӣ шуд: {$user->login}", User::class, $user->id);

        return back()->with('success', "Пароли корбар «{$user->full_name}» бозсозӣ шуд.");
    }
}
