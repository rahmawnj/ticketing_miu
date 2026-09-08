<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penyewaan extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function sewa()
    {
        return $this->belongsTo(Sewa::class);
    }

    public function history()
    {
        return $this->hasOne(HistoryPenyewaan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
