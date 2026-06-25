<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TravelRequest extends Model
{
    protected $fillable = [
        'request_no',
        'user_id',
        'department_id',
        'destination',
        'purpose',
        'date_from',
        'date_to',
        'estimated_cost',
        'status',
        'type',
        'category',
        'assigned_by',
        'acknowledged_at',
        'submitted_at',
        'budget_code',
        'grant_account',
        'grant_title',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'submitted_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isAssigned(): bool
    {
        return $this->type === 'assigned';
    }

    public function isAcknowledged(): bool
    {
        return !is_null($this->acknowledged_at);
    }

    public function needsAcknowledgement(): bool
    {
        return $this->isAssigned() && !$this->isAcknowledged() && $this->status === 'assigned';
    }

    /**
     * Audit-log entries for this travel request.
     */
    public function itinerary(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Itinerary::class);
    }

    public function liquidation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Liquidation::class);
    }

    public function travelOrder(): HasOne
    {
        return $this->hasOne(TravelOrder::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TravelRequestAttachment::class);
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * The currently actionable approval row — lowest level still pending.
     */
    public function currentPendingApproval(): ?Approval
    {
        return $this->approvals()
            ->where('action', 'pending')
            ->orderBy('level')
            ->first();
    }
}
