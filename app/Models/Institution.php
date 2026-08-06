<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    protected $fillable = [
        'name', 'short_name', 'address', 'phone',
        'email', 'website', 'rector_name', 'logo', 'description',
    ];

    public function faculties(): HasMany
    {
        return $this->hasMany(Faculty::class);
    }
}
