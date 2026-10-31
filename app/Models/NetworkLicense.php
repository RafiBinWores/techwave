<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NetworkLicense extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['key_hash'];

    protected function casts(): array
    {
        return ['revoked_at' => 'datetime', 'product_purchase_id' => 'integer', 'user_id' => 'integer', 'product_id' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(ProductPurchase::class, 'product_purchase_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function installations(): HasMany
    {
        return $this->hasMany(NetworkInstallation::class);
    }

    public function trial(): HasOne
    {
        return $this->hasOne(NetworkLicenseTrial::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(NetworkLicenseEvent::class)->latest();
    }
}
