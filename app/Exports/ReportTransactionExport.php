<?php

namespace App\Exports;

use App\Models\Membership;
use App\Models\Penyewaan;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReportTransactionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $data;

    // Gunakan variabel static untuk counter nomor urut agar tidak reset ke 1 terus
    private $rowNumber = 1;

    function __construct($data)
    {
        $this->data = $data;
    }

    private function resolveProductDescription($data): string
    {
        if ($data->transaction_type === 'ticket') {
            $names = $data->detail->pluck('ticket.name')->filter()->unique()->values();
            return $names->isNotEmpty() ? $names->implode(', ') : '-';
        }

        if (in_array($data->transaction_type, ['registration', 'renewal'])) {
            return Membership::find($data->ticket_id)?->name ?? '-';
        }

        if ($data->transaction_type === 'rental') {
            return Penyewaan::with('sewa')->find($data->ticket_id)?->sewa?->name ?? '-';
        }

        return '-';
    }

    public function collection()
    {
        return $this->data;
    }

    public function map($data): array
    {
        $totalDetail = (float) $data->detail()->sum('total');
        $storedDisc = (float) ($data->disc ?? 0);
        $disc = $storedDisc > 0
            ? $storedDisc
            : ($totalDetail * (float) ($data->discount ?? 0) / 100);

        if (($data->transaction_type ?? '') === 'ticket') {
            $dpp = (float) $data->detail()->sum('total');
            $ppn = (float) $data->detail()->sum('ppn');
        } else {
            $dpp = max(0.0, ((float) ($data->bayar ?? 0)) - ((float) ($data->kembali ?? 0)));
            $ppn = (float) ($data->ppn ?? 0);
        }

        $adminFee = in_array($data->transaction_type, ['registration', 'renewal'], true)
            ? (float) ($data->admin_fee ?? 0)
            : 0.0;
        $total = $dpp - $disc + $ppn + $adminFee;

        return [
            $this->rowNumber++, // Nomor urut
            Carbon::parse($data->created_at)->format('d/m/Y H:i:s'),
            $data->user->name ?? '-', // <--- MENAMPILKAN NAMA KASIR
            ucfirst($data->transaction_type ?? '-'),
            $data->ticket_code,
            $this->resolveProductDescription($data),
            strtoupper($data->metode ?? '-'),
            $data->amount,
            $dpp,
            $total,
            $ppn,
            $adminFee,
            $disc,
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Tanggal',
            'Nama Kasir',
            'Transaction Type',
            'Ticket Code',
            'Keterangan Produk',
            'Metode Pembayaran',
            'Amount',
            'Jumlah',
            'Total',
            'PBJT',
            'Biaya Admin',
            'Discount',
        ];
    }
}
