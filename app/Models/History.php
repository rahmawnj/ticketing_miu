<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class History extends Model
{
    use HasFactory;
    protected $fillable = ['id', 'gate', 'member_id', 'user_id', 'waktu'];

    function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    function scopeFilterDaterange($query)
    {
        if (request('daterange') ?? false) {
            $daterange = explode(' - ', request('daterange'));
            $from = Carbon::createFromFormat('m/d/Y', $daterange[0])->format('Y-m-d');
            $to = Carbon::createFromFormat('m/d/Y', $daterange[1])->addDay(1)->format('Y-m-d');

            $query->whereBetween('waktu', [$from, $to]);
        }
    }

    function scopeFilterMember($query)
    {
        if (request('member') ?? false) {
            if (request('member') != 'all') {
                $query->where('member_id', request('member'));
            }
        }
    }

    function scopeFilterKaryawan($query)
    {
        if (request('karyawan') ?? false) {
            if (request('karyawan') != 'all') {
                $query->where('user_id', request('karyawan'));
            }
        }
    }
}
