<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceivedInvitation extends Model
{
    protected $fillable = [
        'received_by',
        'logged_by',
        'sender_org',
        'sender_name',
        'sender_email',
        'sender_phone',
        'event_name',
        'event_venue',
        'event_destination',
        'event_date_from',
        'event_date_to',
        'event_type',
        'description',
        'received_at',
        'status',
        'declined_reason',
    ];

    protected $casts = [
        'event_date_from' => 'date',
        'event_date_to'   => 'date',
        'received_at'     => 'date',
    ];

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReceivedInvitationAttachment::class);
    }

    public function forwardedInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'received_invitation_id');
    }

    public function isNew(): bool
    {
        return $this->status === 'new';
    }

    public function isForwarded(): bool
    {
        return $this->status === 'forwarded';
    }

    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'new'       => 'bg-amber-100 text-amber-700',
            'forwarded' => 'bg-emerald-100 text-emerald-700',
            'declined'  => 'bg-slate-100 text-slate-600',
            default     => 'bg-slate-100 text-slate-600',
        };
    }

    public function formattedDates(): string
    {
        if (!$this->event_date_from) return 'TBD';
        if (!$this->event_date_to || $this->event_date_from->isSameDay($this->event_date_to)) {
            return $this->event_date_from->format('F j, Y');
        }
        if ($this->event_date_from->month === $this->event_date_to->month && $this->event_date_from->year === $this->event_date_to->year) {
            return $this->event_date_from->format('F j') . '–' . $this->event_date_to->format('j, Y');
        }
        return $this->event_date_from->format('F j') . ' – ' . $this->event_date_to->format('F j, Y');
    }
}
