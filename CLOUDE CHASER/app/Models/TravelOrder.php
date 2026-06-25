<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\TravelRequest;

class TravelOrder extends Model
{
    protected $fillable = [
        'invitation_id',
        'travel_request_id',
        'endorsement_letter_id',
        'initiation_type',
        'to_number',
        'type',
        'traveler_id',
        'dean_id',
        'noted_by',
        'department_id',
        'event_name',
        'destination',
        'venue',
        'date_from',
        'date_to',
        'purpose',
        'status',
        'issued_by',
        'issued_at',
        'receipt_timing',
        'budget_code',
        'grant_account',
        'grant_title',
        'records_released_by',
        'records_released_at',
        'records_remarks',
        'returned_at',
        'return_report',
        'returned_by',
    ];

    protected $casts = [
        'date_from'              => 'date',
        'date_to'                => 'date',
        'issued_at'           => 'datetime',
        'returned_at'         => 'datetime',
        'records_released_at' => 'datetime',
    ];

    public function travelRequest(): BelongsTo
    {
        return $this->belongsTo(TravelRequest::class);
    }

    public function endorsementLetter(): BelongsTo
    {
        return $this->belongsTo(EndorsementLetter::class);
    }

    public function traveler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traveler_id');
    }

    public function travelers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'travel_order_traveler');
    }

    public function dean(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dean_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function noter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'noted_by');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function recordsOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'records_released_by');
    }

    public function returner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function signatures(): MorphMany
    {
        return $this->morphMany(Signature::class, 'signable');
    }

    public function issueSignature(): ?Signature
    {
        return $this->signatures()->where('purpose', 'to_issuance')->latest('signed_at')->first();
    }

    public function vehicleRequest(): HasOne
    {
        return $this->hasOne(VehicleRequest::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TravelOrderAttachment::class);
    }

    public function waivers(): HasMany
    {
        return $this->hasMany(TravelOrderAttachment::class)->where('kind', 'waiver');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(TravelOrderAttachment::class)->where('kind', 'receipt');
    }

    public function expenseReport(): HasOne
    {
        return $this->hasOne(ExpenseReport::class);
    }

    public function isPersonal(): bool
    {
        return $this->initiation_type === 'personal';
    }

    public function isOfficial(): bool
    {
        return $this->initiation_type === 'official';
    }

    public function isIssued(): bool
    {
        return $this->status === 'issued';
    }

    public function isPendingRelease(): bool
    {
        return $this->status === 'pending_release';
    }

    public function isPendingSignature(): bool
    {
        return $this->status === 'pending_signature';
    }

    public function isReturned(): bool
    {
        return $this->status === 'returned';
    }

    public function isReleasedByRecords(): bool
    {
        return $this->records_released_at !== null;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function formattedDates(): string
    {
        if ($this->date_from->isSameDay($this->date_to)) {
            return $this->date_from->format('F j, Y');
        }
        if ($this->date_from->month === $this->date_to->month && $this->date_from->year === $this->date_to->year) {
            return $this->date_from->format('F j') . '–' . $this->date_to->format('j, Y');
        }
        return $this->date_from->format('F j') . ' – ' . $this->date_to->format('F j, Y');
    }

    public function typeLabel(): string
    {
        return $this->type === 'academic' ? 'Academic' : 'Research';
    }

    public function vpLabel(): string
    {
        return $this->type === 'academic'
            ? 'Vice President for Academic Affairs'
            : 'Vice President for Research Extension and Innovation';
    }
}
