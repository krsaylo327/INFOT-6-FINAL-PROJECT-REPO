<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseItem extends Model
{
    protected $fillable = [
        'expense_report_id',
        'description',
        'amount',
        'expense_date',
        'category',
        'receipt_path',
        'receipt_original_name',
        'receipt_mime_type',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    public function expenseReport(): BelongsTo
    {
        return $this->belongsTo(ExpenseReport::class);
    }

    public function categoryIcon(): string
    {
        return match ($this->category) {
            'transport'    => 'car',
            'lodging'      => 'bed',
            'meals'        => 'utensils',
            'registration' => 'ticket',
            default        => 'file',
        };
    }

    public function categoryLabel(): string
    {
        return ucfirst($this->category);
    }

    public function hasReceipt(): bool
    {
        return !empty($this->receipt_path);
    }

    public function isReceiptImage(): bool
    {
        return str_starts_with($this->receipt_mime_type ?? '', 'image/');
    }

    public function isReceiptPdf(): bool
    {
        return $this->receipt_mime_type === 'application/pdf';
    }
}
