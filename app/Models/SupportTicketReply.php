<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'support_ticket_id',
    'user_id',
    'sender_type',
    'message',
])]
class SupportTicketReply extends Model
{
    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attachments()
    {
        return $this->hasMany(SupportTicketAttachment::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (SupportTicketReply $reply) {
            $reply->loadMissing('attachments');

            foreach ($reply->attachments as $attachment) {
                $attachment->delete();
            }
        });
    }
}
