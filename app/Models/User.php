<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'login',
        'email',
        'phone',
        'password',
        'must_change_password',
        'first_name',
        'last_name',
        'middle_name',
        'avatar',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    // ==================== РОБИТАҲО ====================

    /**
     * Нақшҳои корбар
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')->withTimestamps();
    }

    /**
     * Иҷозатҳои мустақими корбар
     */
    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permission')
            ->withPivot('granted')
            ->withTimestamps();
    }

    /**
     * Профили донишҷӯ (агар role=student)
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Профили омӯзгор (агар role=teacher)
     */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    // ==================== МЕТОДҲО ====================

    /**
     * Номи пурра
     */
    public function getFullNameAttribute(): string
    {
        $parts = [$this->last_name, $this->first_name];
        if ($this->middle_name) {
            $parts[] = $this->middle_name;
        }
        return implode(' ', $parts);
    }

    /**
     * Номи кӯтоҳ (Фамилия Н.П.)
     */
    public function getShortNameAttribute(): string
    {
        $name = $this->last_name . ' ' . mb_substr($this->first_name, 0, 1) . '.';
        if ($this->middle_name) {
            $name .= mb_substr($this->middle_name, 0, 1) . '.';
        }
        return $name;
    }

    /**
     * Оё корбар нақши мушаххасро дорад?
     */
    public function hasRole(string|UserRole $role): bool
    {
        $roleName = $role instanceof UserRole ? $role->value : $role;
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Оё корбар ягонеро аз нақшҳо дорад?
     */
    public function hasAnyRole(array $roles): bool
    {
        $roleNames = array_map(fn($r) => $r instanceof UserRole ? $r->value : $r, $roles);
        return $this->roles()->whereIn('name', $roleNames)->exists();
    }

    /**
     * Оё корбар иҷозати мушаххасро дорад?
     */
    public function hasPermission(string $permission): bool
    {
        // Аввал аз иҷозатҳои мустақим санҷ
        $directPermission = $this->directPermissions()
            ->where('permissions.name', $permission)
            ->first();

        if ($directPermission) {
            return $directPermission->pivot->granted;
        }

        // Баъд аз нақшҳо санҷ
        return $this->roles()
            ->whereHas('permissions', fn($q) => $q->where('permissions.name', $permission))
            ->exists();
    }

    /**
     * Оё Super Admin аст?
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SUPER_ADMIN);
    }

    /**
     * Оё Admin аст?
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole([UserRole::SUPER_ADMIN, UserRole::ADMIN]);
    }

    /**
     * Оё омӯзгор аст?
     */
    public function isTeacher(): bool
    {
        return $this->hasRole(UserRole::TEACHER);
    }

    /**
     * Оё донишҷӯ аст?
     */
    public function isStudent(): bool
    {
        return $this->hasRole(UserRole::STUDENT);
    }

    /**
     * Гирифтани нақши асосии корбар
     */
    public function getPrimaryRoleAttribute(): ?Role
    {
        return $this->roles()->orderByDesc('level')->first();
    }

    /**
     * Фаъол аст?
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
