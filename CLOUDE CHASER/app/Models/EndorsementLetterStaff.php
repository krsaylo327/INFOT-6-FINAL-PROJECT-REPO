<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndorsementLetterStaff extends Model
{
    protected $table = 'endorsement_letter_staff';

    protected $fillable = [
        'endorsement_letter_id',
        'user_id',
        'position',
        'role_in_event',
        'notified_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
    ];

    public function endorsementLetter(): BelongsTo
    {
        return $this->belongsTo(EndorsementLetter::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
