<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ExpenseReport extends Model
{
    protected $fillable = [
        'travel_order_id',
        'submitted_by',
        'total_amount',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'remarks',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at'  => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(TravelOrder::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExpenseItem::class);
    }

    public function signatures(): MorphMany
    {
        return $this->morphMany(Signature::class, 'signable');
    }

    public function reviewSignature(): ?Signature
    {
        return $this->signatures()->where('purpose', 'expense_review')->latest('signed_at')->first();
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isQueried(): bool
    {
        return $this->status === 'queried';
    }

    public function recalculateTotal(): void
    {
        $this->total_amount = $this->items()->sum('amount');
        $this->save();
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'draft'     => 'bg-slate-100 text-slate-600',
            'submitted' => 'bg-amber-100 text-amber-700',
            'approved'  => 'bg-emerald-100 text-emerald-700',
            'queried'   => 'bg-rose-100 text-rose-700',
            default     => 'bg-slate-100 text-slate-600',
        };
    }
}
