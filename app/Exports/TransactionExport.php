<?php

namespace App\Exports;

use App\Models\Ticket;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class TransactionExport implements FromView
{
    protected $from, $to, $kasir, $memberships, $sewa;

    function __construct($from, $to, $kasir, $memberships, $sewa)
    {
        $this->from = $from;
        $this->to = $to;
        $this->kasir = $kasir;
        $this->memberships = $memberships;
        $this->sewa = $sewa;
    }

    public function view(): View
    {
        // Samakan filter ticket dengan rekap (ambil semua)
        $tickets = Ticket::get();

        return view('report.transaction-export', [
            'tickets' => $tickets,
            'memberships' => $this->memberships,
            'sewa' => $this->sewa,
            'from' => $this->from,
            'to' => $this->to,
            'kasir' => $this->kasir,
        ]);
    }
}
