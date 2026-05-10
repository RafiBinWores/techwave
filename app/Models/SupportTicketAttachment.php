<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'support_ticket_id',
    'support_ticket_reply_id',
    'file_name',
    'file_path',
    'file_type',
    'file_size',
])]
class SupportTicketAttachment extends Model
{
    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function reply()
    {
        return $this->belongsTo(SupportTicketReply::class, 'support_ticket_reply_id');
    }

    public function url(): string
    {
        return Storage::url($this->file_path);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->file_type, 'image/');
    }
}
