<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryMembership extends Model
{
    use HasFactory;
    protected $fillable = ["member_id", "membership_id", "start_date", "end_date", "status"];

    function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    function membership()
    {
        return $this->belongsTo(Membership::class, 'membership_id');
    }
}
