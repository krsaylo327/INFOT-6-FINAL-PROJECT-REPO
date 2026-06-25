<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EndorsementLetter extends Model
{
    protected $fillable = [
        'invitation_id',
        'dean_id',
        'category',
        'reason_for_endorsing',
        'justification',
        'expected_outcomes',
        'budget_code',
        'grant_account',
        'grant_title',
        'estimated_cost',
        'status',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_remarks',
    ];

    protected $casts = [
        'submitted_at'   => 'datetime',
        'reviewed_at'    => 'datetime',
        'estimated_cost' => 'decimal:2',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function dean(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dean_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'endorsement_letter_staff')
            ->withPivot(['position', 'role_in_event', 'notified_at'])
            ->withTimestamps();
    }

    public function travelOrder(): HasOne
    {
        return $this->hasOne(TravelOrder::class);
    }

    public function signatures(): MorphMany
    {
        return $this->morphMany(Signature::class, 'signable');
    }

    public function reviewSignature(): ?Signature
    {
        return $this->signatures()->where('purpose', 'endorsement_review')->latest('signed_at')->first();
    }

    public function isDraft(): bool      { return $this->status === 'draft'; }
    public function isSubmitted(): bool  { return $this->status === 'submitted'; }
    public function isApproved(): bool   { return $this->status === 'approved'; }
    public function isRejected(): bool   { return $this->status === 'rejected'; }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'approved'  => 'bg-emerald-100 text-emerald-700',
            'submitted' => 'bg-indigo-100 text-indigo-700',
            'rejected'  => 'bg-rose-100 text-rose-700',
            'draft'     => 'bg-slate-100 text-slate-700',
            default     => 'bg-slate-100 text-slate-600',
        };
    }

    public function reviewerLabel(): string
    {
        return $this->category === 'research'
            ? 'VP for Research, Extension and Innovation'
            : 'VP for Academic Affairs';
    }
}
