<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'proposal_id',
    'author',
    'body',
    'admin_read_at',
])]
class ProposalComment extends Model
{
    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }
}
