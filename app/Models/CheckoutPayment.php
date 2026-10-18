<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CheckoutPayment extends Model
{
    protected $guarded = [];

    protected $hidden = ['session_key', 'gateway_url', 'customer'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'sandbox' => 'boolean', 'needs_review' => 'boolean', 'customer' => 'array', 'paid_at' => 'datetime'];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
