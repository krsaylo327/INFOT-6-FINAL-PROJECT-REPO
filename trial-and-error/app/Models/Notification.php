<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'title',
        'message',
        'is_read',
        'user_id',
        'dedupe_key',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
