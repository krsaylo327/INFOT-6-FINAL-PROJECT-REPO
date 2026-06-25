<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivedInvitationAttachment extends Model
{
    protected $fillable = [
        'received_invitation_id',
        'original_name',
        'stored_path',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function receivedInvitation(): BelongsTo
    {
        return $this->belongsTo(ReceivedInvitation::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function formattedSize(): string
    {
        if ($this->size >= 1_048_576) {
            return round($this->size / 1_048_576, 1) . ' MB';
        }
        return round($this->size / 1_024) . ' KB';
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }
}
