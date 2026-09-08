<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    use HasFactory;
    protected $fillable = ["name", "code", "duration_days", "price", "max_person", "max_access", "is_active", 'ppn', 'use_ppn'];

    function gates()
    {
        return $this->belongsToMany(GateAccess::class, 'gate_access_membership');
    }

    function members()
    {
        return $this->hasMany(Member::class);
    }
}
