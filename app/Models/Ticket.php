<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;
    protected $guarded = [];

    function gate()
    {
        return $this->belongsTo(GateAccess::class, 'tripod');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jenis()
    {
        return $this->belongsTo(JenisTicket::class, 'jenis_ticket_id');
    }

    public function terusan()
    {
        return $this->belongsToMany(Terusan::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    function detailTransactions(): HasMany
    {
        return $this->hasMany(DetailTransaction::class);
    }
}
