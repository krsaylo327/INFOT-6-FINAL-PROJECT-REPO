<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = ['name', 'abbreviation'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function travelRequests(): HasMany
    {
        return $this->hasMany(TravelRequest::class);
    }
}