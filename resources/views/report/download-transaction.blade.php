<!DOCTYPE html>
<html>
<head>
    <title>miu ticketing system</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; }
        .table {
            width: 100%;
            max-width: 80mm !important;
            margin: 0 auto;
            border-collapse: collapse;
        }
        .table border, .table th, .table td {
            border: 1px solid black;
            padding: 5px;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .mb-3 { margin-bottom: 15px; }
        .mt-3 { margin-top: 15px; }

        @media print {
            .table {
                max-width: 72mm !important;
            }
            @page { margin: 0; }
        }
    </style>
</head>
<body>

@php
    $kasir = filled($kasir ?? null) ? $kasir : 'all';

    // Penentuan Nama Kasir
    $namaKasir = 'All';
    if($kasir && $kasir != 'all') {
        $u = $users->where('id', $kasir)->first();
        $namaKasir = $u ? $u->name : 'Unknown';
    }

    // Query Dasar Transaksi
    $baseQuery = App\Models\Transaction::where('is_active', 1)->whereBetween('created_at', [$from, $to]);
    if ($kasir && $kasir != 'all') {
        $baseQuery->where('transactions.user_id', $kasir);
    }

    $idTrxAll = $baseQuery->pluck('id');
    $adminFeeExpr = "(CASE WHEN transaction_type IN ('registration', 'renewal') THEN admin_fee ELSE 0 END)";
@endphp

<div class="table">
    <div class="text-center mb-3">
        <strong>REPORT TRANSACTION</strong><br>
        {{ Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($to)->subDay(1)->format('d/m/Y') }}<br>
        Kasir: {{ $namaKasir }}
    </div>

    <table class="table" border="1" style="margin-bottom: 10px;">
        <thead>
            <tr>
                <th colspan="3">Report Transaction Ticket</th>
            </tr>
            <tr>
                <th>Jenis</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalQtyTicket = 0;
                $totalAmountTicket = 0;
                $queryTrxTicket = (clone $baseQuery)->where('transaction_type', 'ticket');
                $idTrxTicket = $queryTrxTicket->pluck('id');
            @endphp
            @foreach($tickets as $ticket)
                @php
                    $qty = App\Models\DetailTransaction::whereIn('transaction_id', $idTrxTicket)->where('ticket_id', $ticket->id)->sum('qty');
                    $totalPerTicket = App\Models\DetailTransaction::whereIn('transaction_id', $idTrxTicket)->where('ticket_id', $ticket->id)->sum(\DB::raw('total + ppn'));
                @endphp
                @if($qty > 0)
                @php $totalQtyTicket += $qty; $totalAmountTicket += $totalPerTicket; @endphp
                <tr>
                    <td>{{ $ticket->name }}</td>
                    <td class="text-center">{{ $qty }}</td>
                    <td class="text-end">{{ number_format($totalPerTicket, 0, ',', '.') }}</td>
                </tr>
                @endif
            @endforeach
            <tr>
                <th>Subtotal Ticket</th>
                <th class="text-center">{{ $totalQtyTicket }}</th>
                <th class="text-end">{{ number_format($totalAmountTicket, 0, ',', '.') }}</th>
            </tr>
        </tbody>
    </table>

    <table class="table" border="1" style="margin-bottom: 10px;">
        <thead>
            <tr>
                <th colspan="3">Report Transaction Renewal</th>
            </tr>
            <tr>
                <th>Jenis</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalQtyRenewal = 0;
                $totalAmountRenewal = 0;
                $totalQtyRegistration = 0;
                $totalAmountRegistration = 0;
                $totalQtyLainLain = 0;
                $totalAmountLainLain = 0;
                $orderedMemberships = $memberships->sortBy('name');
            @endphp

            @foreach($orderedMemberships as $membership)
                @php
                    $queryRenewal = (clone $baseQuery)->where(['transaction_type' => 'renewal', 'ticket_id' => $membership->id]);
                    $qtyRenewal = $queryRenewal->count();
                    $totalRenewal = $queryRenewal->sum(\DB::raw('(bayar - kembali) + ppn + admin_fee'));
                @endphp
                @if($qtyRenewal > 0)
                @php $totalQtyRenewal += $qtyRenewal; $totalAmountRenewal += $totalRenewal; @endphp
                <tr>
                    <td>Renewal {{ $membership->name }}</td>
                    <td class="text-center">{{ $qtyRenewal }}</td>
                    <td class="text-end">{{ number_format($totalRenewal, 0, ',', '.') }}</td>
                </tr>
                @endif
            @endforeach
            <tr>
                <th>Subtotal Renewal</th>
                <th class="text-center">{{ $totalQtyRenewal }}</th>
                <th class="text-end">{{ number_format($totalAmountRenewal, 0, ',', '.') }}</th>
            </tr>
        </tbody>
    </table>

    <table class="table" border="1" style="margin-bottom: 10px;">
        <thead>
            <tr>
                <th colspan="3">Report Transaction Registration</th>
            </tr>
            <tr>
                <th>Jenis</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orderedMemberships as $membership)
                @php
                    $queryRegistration = (clone $baseQuery)->where(['transaction_type' => 'registration', 'ticket_id' => $membership->id]);
                    $qtyRegistration = $queryRegistration->count();
                    $totalRegistration = $queryRegistration->sum(\DB::raw('(bayar - kembali) + ppn + admin_fee'));
                @endphp
                @if($qtyRegistration > 0)
                @php $totalQtyRegistration += $qtyRegistration; $totalAmountRegistration += $totalRegistration; @endphp
                <tr>
                    <td>Registration {{ $membership->name }}</td>
                    <td class="text-center">{{ $qtyRegistration }}</td>
                    <td class="text-end">{{ number_format($totalRegistration, 0, ',', '.') }}</td>
                </tr>
                @endif
            @endforeach
            <tr>
                <th>Subtotal Registration</th>
                <th class="text-center">{{ $totalQtyRegistration }}</th>
                <th class="text-end">{{ number_format($totalAmountRegistration, 0, ',', '.') }}</th>
            </tr>
        </tbody>
    </table>

    <table class="table" border="1" style="margin-bottom: 10px;">
        <thead>
            <tr>
                <th colspan="3">Report Transaction Lain-lain</th>
            </tr>
            <tr>
                <th>Jenis</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sewa as $sewaItem)
                @php
                    $rentalPenyewaanIds = App\Models\Penyewaan::where('sewa_id', $sewaItem->id)->pluck('id');
                    $queryR = (clone $baseQuery)->where('transaction_type', 'rental')->whereIn('ticket_id', $rentalPenyewaanIds);
                    $qtyR = $queryR->count();
                    $totalR = $queryR->sum(\DB::raw('(bayar - kembali) + ppn'));
                @endphp
                @if($qtyR > 0)
                @php $totalQtyLainLain += $qtyR; $totalAmountLainLain += $totalR; @endphp
                <tr>
                    <td>Rental {{ $sewaItem->name }}</td>
                    <td class="text-center">{{ $qtyR }}</td>
                    <td class="text-end">{{ number_format($totalR, 0, ',', '.') }}</td>
                </tr>
                @endif
            @endforeach

            @php
                $otherTypeRows = (clone $baseQuery)
                    ->whereNotIn('transaction_type', ['ticket', 'renewal', 'registration', 'rental'])
                    ->select('transaction_type', \DB::raw('COUNT(*) as qty'), \DB::raw('SUM((bayar - kembali) + ppn) as total_with_ppn'))
                    ->groupBy('transaction_type')
                    ->get();
            @endphp
            @foreach($otherTypeRows as $otherRow)
                @php
                    $totalQtyLainLain += (int) $otherRow->qty;
                    $totalAmountLainLain += (float) $otherRow->total_with_ppn;
                @endphp
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', (string) $otherRow->transaction_type)) }}</td>
                    <td class="text-center">{{ (int) $otherRow->qty }}</td>
                    <td class="text-end">{{ number_format((float) $otherRow->total_with_ppn, 0, ',', '.') }}</td>
                </tr>
            @endforeach

            <tr>
                <th>Subtotal Lain-lain</th>
                <th class="text-center">{{ $totalQtyLainLain }}</th>
                <th class="text-end">{{ number_format($totalAmountLainLain, 0, ',', '.') }}</th>
            </tr>
        </tbody>
    </table>

    <table class="table" border="1">
        @php
            $totalQtyNonTicket = $totalQtyRenewal + $totalQtyRegistration + $totalQtyLainLain;
            $totalAmountNonTicket = $totalAmountRenewal + $totalAmountRegistration + $totalAmountLainLain;
            $totalSalesAmount = $totalAmountTicket + $totalAmountNonTicket;
            $totalDiscount = (clone $baseQuery)->sum('disc');
            $totalPPN = App\Models\DetailTransaction::whereIn('transaction_id', $idTrxAll)->sum('ppn') +
                        App\Models\Transaction::whereIn('id', $idTrxAll)->whereIn('transaction_type', ['renewal', 'registration', 'rental'])->sum('ppn');
            $totalAdminFee = App\Models\Transaction::whereIn('id', $idTrxAll)->sum(\DB::raw($adminFeeExpr));

            // Per Method Calculation
            $calculateMethod = function($methods, $idAll) {
                $methods = (array) $methods;
                $ids = App\Models\Transaction::whereIn('id', $idAll)->whereIn('metode', $methods)->pluck('id');
                $d = App\Models\DetailTransaction::whereIn('transaction_id', $ids)->sum(\DB::raw('total + ppn'));
                $n = App\Models\Transaction::whereIn('id', $ids)
                    ->whereIn('transaction_type', ['renewal', 'registration', 'rental'])
                    ->sum(\DB::raw('(bayar - kembali) + ppn + ' . "(CASE WHEN transaction_type IN ('registration', 'renewal') THEN admin_fee ELSE 0 END)"));
                return $d + $n;
            };

            $qrisTotal = $calculateMethod(['qris', 'qr'], $idTrxAll);
            $debitTotal = $calculateMethod('debit', $idTrxAll);
            $kreditTotal = $calculateMethod(['kredit', 'credit', 'credit card', 'kartu kredit'], $idTrxAll);
            $transferTotal = $calculateMethod('transfer', $idTrxAll);
            $lainnyaIds = App\Models\Transaction::whereIn('id', $idTrxAll)
                ->where(function ($q) {
                    $q->whereIn('metode', ['lain-lain', 'tap', 'cash'])
                        ->orWhereNull('metode')
                        ->orWhere('metode', '')
                        ->orWhereNotIn('metode', ['qris', 'qr', 'debit', 'kredit', 'credit', 'credit card', 'kartu kredit', 'transfer', 'lain-lain', 'tap', 'cash']);
                })
                ->pluck('id');
            $lainnyaTotal = App\Models\DetailTransaction::whereIn('transaction_id', $lainnyaIds)->sum(\DB::raw('total + ppn'))
                + App\Models\Transaction::whereIn('id', $lainnyaIds)
                    ->whereIn('transaction_type', ['renewal', 'registration', 'rental'])
                    ->sum(\DB::raw('(bayar - kembali) + ppn + (CASE WHEN transaction_type IN (\'registration\', \'renewal\') THEN admin_fee ELSE 0 END)'));

            $finalTotalAmount = $totalSalesAmount - $totalDiscount;
        @endphp
        <tbody>
            <tr>
                <td colspan="2">Total Penjualan</td>
                <td class="text-end">{{ number_format($totalSalesAmount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="2">Total Diskon</td>
<td class="text-end">
    {{ $totalDiscount > 0 ? '-' : '' }}{{ number_format($totalDiscount, 0, ',', '.') }}
</td>            </tr>
            <tr>
                <td colspan="2">Total PBJT</td>
                <td class="text-end">{{ number_format($totalPPN, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="2">Total Biaya Admin</td>
                <td class="text-end">{{ number_format($totalAdminFee, 0, ',', '.') }}</td>
            </tr>
            <tr style="background-color: #eee;">
                <th colspan="2">TOTAL NET SALES</th>
                <th class="text-end">{{ number_format($finalTotalAmount, 0, ',', '.') }}</th>
            </tr>
            <tr>
                <th colspan="3" class="text-center">Metode Pembayaran</th>
            </tr>
            <tr>
                <td colspan="2">QRIS</td>
                <td class="text-end">{{ number_format($qrisTotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="2">Debit</td>
                <td class="text-end">{{ number_format($debitTotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="2">Kredit</td>
                <td class="text-end">{{ number_format($kreditTotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="2">Transfer</td>
                <td class="text-end">{{ number_format($transferTotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="2">Lain-lain</td>
                <td class="text-end">{{ number_format($lainnyaTotal, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</div>

<script src="{{ asset('/js/jquery.min.js') }}"></script>
<script>
    $(document).ready(function() {
        window.print();
        // Optional: window.close(); setelah print jika ingin menutup tab otomatis
    })
</script>
</body>
</html>

