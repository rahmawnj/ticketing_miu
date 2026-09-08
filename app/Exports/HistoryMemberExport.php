<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HistoryMemberExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private $data;

    function __construct($data)
    {
        $this->data = $data;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama',
            'No Hp',
        ];
    }

    public function map($data): array
    {
        return [
            Carbon::parse($data->waktu)->format('d/m/Y H:i:s'),
            $data->member_id != 0 ? $data->member->nama : $data->user->name,
            $data->member_id != 0 ? $data->member->no_hp : $data->user->name,
        ];
    }
}
