<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class ReportPenyewaanExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $groupedRows;
    protected $grandQty;
    protected $grandPpn;
    protected $grandTotal;

    public function __construct($groupedRows, $grandQty, $grandPpn, $grandTotal)
    {
        $this->groupedRows = $groupedRows;
        $this->grandQty = $grandQty;
        $this->grandPpn = $grandPpn;
        $this->grandTotal = $grandTotal;
    }

    public function collection()
    {
        $rows = new Collection();

        foreach ($this->groupedRows as $dateGroup) {
            $rows->push([
                'TANGGAL',
                '',
                $dateGroup['tanggal_label'] ?? '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ]);

            foreach (($dateGroup['groups'] ?? []) as $group) {
                foreach (($group['details'] ?? []) as $detail) {
                    $rows->push([
                        $detail['no'],
                        $detail['no_bukti'],
                        $detail['tanggal'],
                        $detail['kasir'],
                        $detail['kode_trx'],
                        $group['transaction_type_label'],
                        $group['item_name'],
                        $detail['qty'],
                        $detail['metode'],
                        $detail['ppn'],
                        $detail['total_bayar'],
                    ]);
                }

                $rows->push([
                    'TOTAL',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $group['item_name'],
                    $group['subtotal_qty'],
                    '',
                    $group['subtotal_ppn'],
                    $group['subtotal_total'],
                ]);
            }

            $rows->push([
                'TOTAL TANGGAL',
                '',
                $dateGroup['tanggal_label'] ?? '',
                '',
                '',
                '',
                '',
                $dateGroup['subtotal_qty'] ?? 0,
                '',
                $dateGroup['subtotal_ppn'] ?? 0,
                $dateGroup['subtotal_total'] ?? 0,
            ]);
        }

        $rows->push([
            'GRAND TOTAL',
            '',
            '',
            '',
            '',
            '',
            '',
            $this->grandQty,
            '',
            $this->grandPpn,
            $this->grandTotal,
        ]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            'No',
            'No Bukti',
            'Tanggal',
            'FO/Kasir',
            'Kode Transaksi',
            'Jenis Transaksi',
            'Item',
            'Qty',
            'Metode',
            'PBJT',
            'Total Bayar',
        ];
    }
}
