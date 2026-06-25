<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Liquidation extends Model
{
    protected $fillable = [
        'travel_request_id',
        'total_claimed',
        'total_approved',
        'status',
        'submitted_at',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'submitted_at'   => 'datetime',
        'approved_at'    => 'datetime',
        'total_claimed'  => 'decimal:2',
        'total_approved' => 'decimal:2',
    ];

    public function travelRequest(): BelongsTo
    {
        return $this->belongsTo(TravelRequest::class);
    }
}
