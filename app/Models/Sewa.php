<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sewa extends Model
{
    use HasFactory;
    protected $table = 'sewa';
    protected $guarded = [];

    function penyewaans(): HasMany
    {
        return $this->hasMany(Penyewaan::class, 'sewa_id');
    }
}
