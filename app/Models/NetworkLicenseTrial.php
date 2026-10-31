<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkLicenseTrial extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'expires_at' => 'datetime', 'limits' => 'array',
            'user_id' => 'integer', 'product_id' => 'integer', 'network_license_id' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(NetworkLicense::class, 'network_license_id');
    }
}
