<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Approval extends Model
{
    protected $fillable = [
        'travel_request_id',
        'approver_id',
        'level',
        'action',
        'remarks',
        'acted_at',
        'is_noter',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function travelRequest(): BelongsTo
    {
        return $this->belongsTo(TravelRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function signatures(): MorphMany
    {
        return $this->morphMany(Signature::class, 'signable');
    }

    public function decisionSignature(): ?Signature
    {
        return $this->signatures()->where('purpose', 'travel_approval')->latest('signed_at')->first();
    }
}