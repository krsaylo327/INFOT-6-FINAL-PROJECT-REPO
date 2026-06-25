<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'signature_path',
        'signature_uploaded_at',
        'role',
        'status',
        'employee_id',
        'requested_position',
        'contact_number',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'approver_level',
        'approver_type',
        'department_id',
        'disabled_at',
        'disable_reason',
        'disabled_by',
    ];

    public function avatarUrl(): string
    {
        return $this->avatar
            ? asset('storage/' . $this->avatar)
            : '';
    }

    public function hasSignature(): bool
    {
        return !empty($this->signature_path);
    }

    public function signatureUrl(): ?string
    {
        return $this->signature_path
            ? route('profile.signature.show', ['user' => $this->id])
            : null;
    }

    public const STATUS_PENDING  = 'pending';
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DISABLED = 'disabled';

    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'password'              => 'hashed',
            'approved_at'           => 'datetime',
            'disabled_at'           => 'datetime',
            'signature_uploaded_at' => 'datetime',
        ];
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class, 'signer_id');
    }

    public function isPending(): bool  { return $this->status === self::STATUS_PENDING; }
    public function isActive(): bool   { return $this->status === self::STATUS_ACTIVE; }
    public function isRejected(): bool { return $this->status === self::STATUS_REJECTED; }
    public function isDisabled(): bool { return $this->status === self::STATUS_DISABLED; }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE   => 'bg-emerald-100 text-emerald-700',
            self::STATUS_PENDING  => 'bg-amber-100 text-amber-700',
            self::STATUS_DISABLED => 'bg-orange-100 text-orange-700',
            self::STATUS_REJECTED => 'bg-rose-100 text-rose-700',
            default               => 'bg-slate-100 text-slate-600',
        };
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

        public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function travelRequests(): HasMany
    {
        return $this->hasMany(TravelRequest::class);
    }

    public function approvalsToReview(): HasMany
    {
        return $this->hasMany(Approval::class, 'approver_id');
    }

    /**
     * Endorsement letters created by this user (as a Dean).
     */
    public function endorsementLetters(): HasMany
    {
        return $this->hasMany(EndorsementLetter::class, 'dean_id');
    }

    /**
     * Endorsement letters this user is reviewing (as VPAA/VPREI).
     */
    public function endorsementsToReview(): HasMany
    {
        return $this->hasMany(EndorsementLetter::class, 'reviewed_by');
    }

    /**
     * Endorsement letters this user is named on as endorsed staff.
     */
    public function endorsedFor(): BelongsToMany
    {
        return $this->belongsToMany(EndorsementLetter::class, 'endorsement_letter_staff')
            ->withPivot(['position', 'role_in_event', 'notified_at'])
            ->withTimestamps();
    }

    public function isVPAA(): bool
    {
        return $this->role === 'approver' && $this->approver_type === 'vp_academic';
    }

    public function isVPREI(): bool
    {
        return $this->role === 'approver' && $this->approver_type === 'vp_research';
    }

    public function isPresident(): bool
    {
        return $this->role === 'dean' && $this->department?->abbreviation === 'PRES';
    }

    public function isRecordsOfficer(): bool
    {
        return $this->role === 'records_officer';
    }
}
