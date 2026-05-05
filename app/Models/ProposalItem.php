<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'proposal_id',
    'item_type',
    'item_id',
    'title',
    'description',
    'quantity',
    'unit_price',
])]
class ProposalItem extends Model
{
    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function subtotal(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }
}
