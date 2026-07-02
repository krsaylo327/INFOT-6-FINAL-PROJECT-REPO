<?php

namespace App\Models;

use App\Support\AgreementWorkflowMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Agreement extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'type',
        'partner_organization',
        'partner_organization_id',
        'description',
        'signed_at',
        'expires_at',
        'status',
        'document',
        'workflow_status',
        'agreement_status',
        'current_handler',
        'submitted_by',
    ];

    protected $casts = [
        'signed_at' => 'date',
        'expires_at' => 'date',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(AgreementVersion::class);
    }

    public function workflowHistories(): HasMany
    {
        return $this->hasMany(WorkflowHistory::class);
    }

    public function workflowLogs(): HasMany
    {
        return $this->hasMany(WorkflowLog::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(AgreementSubscription::class);
    }

    public function partnerOrganization(): BelongsTo
    {
        return $this->belongsTo(PartnerOrganization::class, 'partner_organization_id');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isCoordinator()) {
            return $query;
        }

        if ($user->isAuthorizedPersonnel()) {
            return $query->where('partner_organization_id', $user->organization_id);
        }

        return $query->where('submitted_by', $user->id);
    }

    /**
     * Recompute and set the agreement `status` based on workflow, dates, and fields.
     */
    public function syncStatus(): self
    {
        $expiresAt = $this->expires_at ? Carbon::parse($this->expires_at) : null;

        if ($this->status === 'terminated' || $this->status === 'disabled') {
            return $this;
        }

        if ($this->workflow_status === 'draft' || $this->status === 'draft') {
            $this->status = 'draft';

            return $this;
        }

        if (blank($this->submitted_by) && blank($this->signed_at)) {
            $this->status = 'draft';

            return $this;
        }

        if ($this->workflow_status === 'active_agreement') {
            $this->status = 'active';
        } elseif (in_array($this->workflow_status, AgreementWorkflowMap::reviewStages(), true)) {
            $this->status = 'for_review';
        } elseif (blank($this->signed_at)) {
            $this->status = 'for_review';
        } elseif ($expiresAt && $expiresAt->isPast()) {
            $this->status = 'expired';
        } elseif ($this->status === 'expired' && $expiresAt && $expiresAt->isFuture()) {
            $this->status = 'renewed';
        } elseif (blank($expiresAt) || $expiresAt->isFuture()) {
            $this->status = 'active';
        }

        return $this;
    }
}
