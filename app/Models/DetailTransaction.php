<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailTransaction extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public static function applyTicketCodeMode(Transaction $transaction, string $mode): void
    {
        $normalizedMode = in_array($mode, ['shared', 'unique'], true) ? $mode : 'unique';

        if ($normalizedMode === 'shared') {
            static::ensureSharedPerTicketCodes($transaction);
            return;
        }

        static::ensureUniquePerTicketCodes($transaction);
    }

    public static function ensureSharedPerTicketCodes(Transaction $transaction): void
    {
        $details = static::query()
            ->where('transaction_id', $transaction->id)
            ->orderBy('id')
            ->get();

        if ($details->isEmpty()) {
            return;
        }

        $prefix = static::ticketCodePrefix($transaction);

        foreach ($details as $detail) {
            $qty = max((int) $detail->qty, 1);
            $scanned = max(0, min((int) $detail->scanned, $qty));
            $ticketCode = trim((string) $detail->ticket_code);
            if ($ticketCode === '') {
                $ticketCode = $prefix . '/S/' . str_pad((string) $detail->id, 6, '0', STR_PAD_LEFT);
            }

            $detail->update([
                'ticket_code' => $ticketCode,
                'qty' => $qty,
                'scanned' => $scanned,
                'status' => $scanned >= $qty ? 'close' : 'open',
            ]);
        }
    }

    public static function ensureUniquePerTicketCodes(Transaction $transaction): void
    {
        $details = static::query()
            ->where('transaction_id', $transaction->id)
            ->orderBy('id')
            ->get();

        if ($details->isEmpty()) {
            return;
        }

        foreach ($details as $detail) {
            $qty = max((int) $detail->qty, 1);
            if ($qty <= 1) {
                continue;
            }

            $lineTotal = max((int) round((float) $detail->total), 0);
            $baseTotal = intdiv($lineTotal, $qty);
            $totalRemainder = $lineTotal - ($baseTotal * $qty);

            $linePpnCents = max((int) round((float) $detail->ppn * 100), 0);
            $basePpnCents = intdiv($linePpnCents, $qty);
            $ppnRemainder = $linePpnCents - ($basePpnCents * $qty);

            $scannedRemaining = max((int) $detail->scanned, 0);
            $originalGate = $detail->gate;

            for ($piece = 1; $piece <= $qty; $piece++) {
                $pieceTotal = $baseTotal + ($piece <= $totalRemainder ? 1 : 0);
                $piecePpn = ($basePpnCents + ($piece <= $ppnRemainder ? 1 : 0)) / 100;
                $pieceScanned = $scannedRemaining > 0 ? 1 : 0;
                if ($scannedRemaining > 0) {
                    $scannedRemaining--;
                }

                $payload = [
                    'qty' => 1,
                    'total' => $pieceTotal,
                    'ppn' => $piecePpn,
                    'scanned' => $pieceScanned,
                    'status' => $pieceScanned === 1 ? 'close' : 'open',
                    'gate' => $pieceScanned === 1 ? $originalGate : null,
                ];

                if ($piece === 1) {
                    $detail->update($payload);
                    continue;
                }

                static::query()->create([
                    'transaction_id' => $detail->transaction_id,
                    'ticket_id' => $detail->ticket_id,
                    'ticket_code' => null,
                    'qty' => $payload['qty'],
                    'total' => $payload['total'],
                    'ppn' => $payload['ppn'],
                    'status' => $payload['status'],
                    'scanned' => $payload['scanned'],
                    'gate' => $payload['gate'],
                ]);
            }
        }

        $details = static::query()
            ->where('transaction_id', $transaction->id)
            ->orderBy('id')
            ->get();

        $prefix = static::ticketCodePrefix($transaction);

        foreach ($details as $detail) {
            $ticketCode = $prefix . '/U/' . str_pad((string) $detail->id, 6, '0', STR_PAD_LEFT);
            $normalizedScanned = max(0, min((int) $detail->scanned, 1));

            $detail->update([
                'ticket_code' => $ticketCode,
                'qty' => 1,
                'scanned' => $normalizedScanned,
                'status' => $normalizedScanned >= 1 ? 'close' : 'open',
            ]);
        }
    }

    private static function ticketCodePrefix(Transaction $transaction): string
    {
        $ticketDate = $transaction->created_at
            ? Carbon::parse($transaction->created_at)->timezone('Asia/Jakarta')
            : now('Asia/Jakarta');
        $dateSegment = $ticketDate->format('ymd');
        $trxSegment = str_pad((string) (((int) $transaction->no_trx > 0) ? (int) $transaction->no_trx : (int) $transaction->id), 6, '0', STR_PAD_LEFT);

        return 'TKT/' . $dateSegment . '/' . $trxSegment;
    }

    function scopeFilterDaterange($query)
    {
        if (request('daterange') ?? false) {
            $daterange = explode(' - ', request('daterange'));
            $from = Carbon::createFromFormat('m/d/Y', $daterange[0])->format('Y-m-d');
            $to = Carbon::createFromFormat('m/d/Y', $daterange[1])->addDay(1)->format('Y-m-d');

            $query->whereBetween('scanned_at', [$from, $to]);
        }
    }
}
