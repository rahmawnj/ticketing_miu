<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $guarded = [];

    public static function nextNoTrxByType(string $transactionType, ?Carbon $date = null): int
    {
        $date = $date ?: now('Asia/Jakarta');

        $lastNo = self::query()
            ->whereDate('created_at', $date->toDateString())
            ->max('no_trx');

        return ((int) $lastNo) + 1;
    }

    public static function buildTicketCodeByType(string $transactionType, ?Carbon $date = null, ?int $noTrx = null): string
    {
        $date = $date ?: now('Asia/Jakarta');
        $sequence = $noTrx ?: self::nextNoTrxByType($transactionType, $date);

        $prefix = match ($transactionType) {
            'ticket' => 'TKT',
            'registration' => 'REG',
            'renewal' => 'RENEW',
            'rental' => 'RENT',
            default => 'TRX',
        };

        return $prefix . '/' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function detail()
    {
        return $this->hasMany(DetailTransaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
