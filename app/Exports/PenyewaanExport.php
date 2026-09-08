<?php

namespace App\Exports;

use App\Models\Sewa;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PenyewaanExport implements FromView
{
    protected $from, $to, $kasir;

    function __construct($from, $to, $kasir)
    {
        $this->from = $from;
        $this->to = $to;
        $this->kasir = $kasir;
    }

    public function view(): View
    {
        $sewa = Sewa::get();

        return view('report.penyewaan-export', [
            'sewa' => $sewa,
            'from' => $this->from,
            'to' => $this->to,
            'kasir' => $this->kasir,
        ]);
    }
}
