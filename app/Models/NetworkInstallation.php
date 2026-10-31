<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkInstallation extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['credential_hash'];

    protected function casts(): array
    {
        return ['last_verified_at' => 'datetime', 'deactivated_at' => 'datetime'];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(NetworkLicense::class, 'network_license_id');
    }
}
