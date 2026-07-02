<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowLog extends Model
{
    protected $fillable = [
        'agreement_id',
        'user_name',
        'from_status',
        'to_status',
        'remarks',
    ];
}
