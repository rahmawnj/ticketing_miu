<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class TransactionDailyReceiptExport implements FromView
{
    private $startDate;
    private $endDate;
    private array $groups;
    private string $reportTitle;

    public function __construct($startDate, $endDate, array $groups, string $reportTitle)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->groups = $groups;
        $this->reportTitle = $reportTitle;
    }

    public function view(): View
    {
        return view('transaction.export-daily', [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'groups' => $this->groups,
            'generatedAt' => now('Asia/Jakarta'),
            'reportTitle' => $this->reportTitle,
        ]);
    }
}
