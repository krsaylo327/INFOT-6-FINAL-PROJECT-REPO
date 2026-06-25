<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Itinerary extends Model
{
    protected $fillable = [
        'travel_request_id',
        'departure_place',
        'arrival_place',
        'departure_at',
        'return_at',
        'transport_mode',
        'accommodation',
        'daily_allowance',
        'notes',
        'status',
    ];

    protected $casts = [
        'departure_at'    => 'datetime',
        'return_at'       => 'datetime',
        'daily_allowance' => 'decimal:2',
    ];

    public function travelRequest(): BelongsTo
    {
        return $this->belongsTo(TravelRequest::class);
    }
}
