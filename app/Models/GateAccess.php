<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GateAccess extends Model
{
    use HasFactory;
    protected $fillable  = ["gate_access_id", "name", "is_active"];
}
