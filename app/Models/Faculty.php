<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faculty extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'institution_id', 'name', 'short_name', 'code',
        'dean_id', 'phone', 'email', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function dean(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dean_id');
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function specialties(): HasManyThrough
    {
        return $this->hasManyThrough(Specialty::class, Department::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Шумораи донишҷӯёни факултет
     */
    public function getStudentsCountAttribute(): int
    {
        return Student::whereHas('specialty.department', fn($q) => $q->where('faculty_id', $this->id))
            ->active()
            ->count();
    }
}
