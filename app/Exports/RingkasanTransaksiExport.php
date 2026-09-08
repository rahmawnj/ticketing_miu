<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class RingkasanTransaksiExport implements FromView
{
    private string $from;
    private string $to;
    private array $rows;
    private array $footer;

    public function __construct(string $from, string $to, array $rows, array $footer)
    {
        $this->from = $from;
        $this->to = $to;
        $this->rows = $rows;
        $this->footer = $footer;
    }

    public function view(): View
    {
        return view('report.ringkasan-transaksi-export', [
            'from' => Carbon::parse($this->from),
            'to' => Carbon::parse($this->to),
            'rows' => $this->rows,
            'footer' => $this->footer,
        ]);
    }
}

