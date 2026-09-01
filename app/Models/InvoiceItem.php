<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'invoice_id',
    'item_type',
    'item_id',
    'title',
    'description',
    'quantity',
    'unit_price',
])]
class InvoiceItem extends Model
{
    use HasFactory;

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function subtotal(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }
}
