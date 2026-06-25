<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleRequest extends Model
{
    protected $fillable = [
        'travel_order_id',
        'requested_by',
        'vehicle_type',
        'departure_datetime',
        'return_datetime',
        'passengers',
        'pickup_location',
        'dropoff_location',
        'purpose',
        'status',
        'admin_remarks',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'departure_datetime' => 'datetime',
        'return_datetime'    => 'datetime',
        'reviewed_at'        => 'datetime',
    ];

    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(TravelOrder::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isDenied(): bool   { return $this->status === 'denied'; }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'approved' => 'bg-emerald-100 text-emerald-700',
            'denied'   => 'bg-rose-100 text-rose-700',
            default    => 'bg-amber-100 text-amber-700',
        };
    }
}
