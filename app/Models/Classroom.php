<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    protected $fillable = [
        'name', 'building', 'floor', 'capacity', 'type',
        'has_projector', 'has_computers', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'has_projector' => 'boolean',
            'has_computers' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFullNameAttribute(): string
    {
        $name = $this->name;
        if ($this->building) {
            $name = $this->building . ', ' . $name;
        }
        return $name;
    }
}
