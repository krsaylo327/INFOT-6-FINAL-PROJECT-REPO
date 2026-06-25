<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invitation extends Model
{
    protected $fillable = [
        'received_invitation_id',
        'issued_by',
        'assigned_to',
        'event_name',
        'destination',
        'venue',
        'type',
        'date_from',
        'date_to',
        'details',
        'status',
        'reject_reason',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to'   => 'date',
    ];

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function assignedDean(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function travelOrder(): HasOne
    {
        return $this->hasOne(TravelOrder::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InvitationAttachment::class);
    }

    public function receivedInvitation(): BelongsTo
    {
        return $this->belongsTo(ReceivedInvitation::class);
    }

    public function endorsementLetter(): HasOne
    {
        return $this->hasOne(EndorsementLetter::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isEndorsed(): bool
    {
        return $this->status === 'endorsed';
    }

    public function isActed(): bool
    {
        return $this->status === 'acted';
    }

    public function canRespond(): bool
    {
        return in_array($this->status, ['open']);
    }

    public function formattedDates(): string
    {
        if (!$this->date_from) return 'TBD';
        if (!$this->date_to || $this->date_from->isSameDay($this->date_to)) {
            return $this->date_from->format('F j, Y');
        }
        if ($this->date_from->month === $this->date_to->month && $this->date_from->year === $this->date_to->year) {
            return $this->date_from->format('F j') . '–' . $this->date_to->format('j, Y');
        }
        return $this->date_from->format('F j') . ' – ' . $this->date_to->format('F j, Y');
    }
}
