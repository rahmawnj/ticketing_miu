<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipAdminFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_type',
        'admin_fee',
    ];
}
