<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgreementSubscription extends Model
{
    protected $fillable = [
        'agreement_id',
        'user_id',
        'notify_on_expiration',
    ];

    public function agreement()
    {
        return $this->belongsTo(Agreement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
