<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoDailyUsage extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['usage_date' => 'date', 'checks' => 'integer'];
    }
}
