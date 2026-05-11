<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'phone',
    'email',
    'subject',
    'message',
    'admin_read_at',
])]
class ContactMessage extends Model
{
    //
}
