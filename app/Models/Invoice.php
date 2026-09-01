<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'company_id',
    'invoice_no',
    'customer_name',
    'customer_email',
    'customer_phone',
    'company_name',
    'subject',
    'note',
    'terms',
    'discount_type',
    'discount_value',
    'status',
    'issue_date',
    'due_date',
    'sent_at',
])]
class Invoice extends Model
{
    use HasFactory;

    protected $casts = [
        'discount_value' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class)->oldest();
    }

    public function subtotal(): float
    {
        return $this->items->sum(fn ($item) => (float) $item->quantity * (float) $item->unit_price);
    }

    public function discountAmount(): float
    {
        $subtotal = $this->subtotal();

        return match ($this->discount_type) {
            'percentage' => ($subtotal * (float) $this->discount_value) / 100,
            'fixed' => (float) $this->discount_value,
            default => 0,
        };
    }

    public function total(): float
    {
        return max($this->subtotal() - $this->discountAmount(), 0);
    }
}
