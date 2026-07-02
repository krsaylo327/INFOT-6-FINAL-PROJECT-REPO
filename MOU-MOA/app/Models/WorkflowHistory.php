<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowHistory extends Model
{
    protected $fillable = [
        'agreement_id',
        'action',
        'performed_by',
        'from_status',
        'to_status',
        'remarks',
    ];
}
