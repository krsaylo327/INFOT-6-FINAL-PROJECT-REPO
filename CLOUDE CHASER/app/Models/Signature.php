<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Signature extends Model
{
    protected $fillable = [
        'signer_id',
        'signable_type',
        'signable_id',
        'purpose',
        'signature_image_path',
        'document_hash',
        'verification_code',
        'signer_name_snapshot',
        'signer_position_snapshot',
        'ip_address',
        'decision_remarks',
        'decision',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_id');
    }

    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function generateVerificationCode(): string
    {
        do {
            $code = strtoupper(Str::random(12));
        } while (self::where('verification_code', $code)->exists());

        return $code;
    }

    public static function computeDocumentHash(array $fields): string
    {
        ksort($fields);
        return hash('sha256', json_encode($fields));
    }

    public function purposeLabel(): string
    {
        return match($this->purpose) {
            'endorsement_review' => 'Endorsement Letter Review',
            'to_issuance'        => 'Travel Order Issuance',
            'travel_approval'    => 'Travel Request Approval',
            'expense_review'     => 'Expense Report Review',
            default              => ucwords(str_replace('_', ' ', $this->purpose)),
        };
    }

    public function decisionBadgeClass(): string
    {
        return match($this->decision) {
            'approved' => 'bg-emerald-100 text-emerald-700',
            'rejected' => 'bg-rose-100 text-rose-700',
            'noted'    => 'bg-amber-100 text-amber-700',
            'issued'   => 'bg-indigo-100 text-indigo-700',
            default    => 'bg-slate-100 text-slate-700',
        };
    }
}
