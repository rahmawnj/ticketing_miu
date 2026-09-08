@php
    $queryTrxAll = App\Models\Transaction::where(['is_active' => 1])->whereBetween('created_at', [$from, $to]);
    if ($kasir != 'all' && $kasir) {
        $queryTrxAll->where('transactions.user_id', $kasir);
    }
    $idTrxAll = $queryTrxAll->pluck('id');
    $adminFeeExpr = "(CASE WHEN transaction_type IN ('registration', 'renewal') THEN admin_fee ELSE 0 END)";

    $methodOrder = [
        'qris' => 'QRIS',
        'debit' => 'Debit',
        'kredit' => 'Kredit',
        'transfer' => 'Transfer',
        'lain_lain' => 'Lain-lain',
    ];
    $knownMethodValues = [
        'qris',
        'qr',
        'debit',
        'kredit',
        'credit',
        'credit card',
        'kartu kredit',
        'transfer',
        'lain-lain',
        'tap',
        'cash',
    ];
    $methodKeys = array_keys($methodOrder);

    $applyMethodFilter = function ($query, $methodKey) use ($knownMethodValues) {
        if ($methodKey === 'qris') {
            $query->whereIn('metode', ['qris', 'qr']);
        } elseif ($methodKey === 'debit') {
            $query->where('metode', 'debit');
        } elseif ($methodKey === 'kredit') {
            $query->whereIn('metode', ['kredit', 'credit', 'credit card', 'kartu kredit']);
        } elseif ($methodKey === 'transfer') {
            $query->where('metode', 'transfer');
        } elseif ($methodKey === 'lain_lain') {
            $query->where(function ($q) use ($knownMethodValues) {
                $q->whereIn('metode', ['lain-lain', 'tap', 'cash'])
                    ->orWhereNull('metode')
                    ->orWhere('metode', '')
                    ->orWhereNotIn('metode', $knownMethodValues);
            });
        }

        return $query;
    };

    $sumTrxByMethod = function ($baseQuery, $methodKey, $sumExpression) use ($applyMethodFilter) {
        $query = clone $baseQuery;
        $query = $applyMethodFilter($query, $methodKey);
        return (float) $query->sum(\DB::raw($sumExpression));
    };

    $sumTicketByMethod = function ($ticketId, $trxBaseQuery, $methodKey) use ($applyMethodFilter) {
        $trxQuery = clone $trxBaseQuery;
        $trxQuery = $applyMethodFilter($trxQuery, $methodKey);
        $trxIds = $trxQuery->pluck('id');
        if ($trxIds->isEmpty()) {
            return 0;
        }

        return (float) App\Models\DetailTransaction::whereIn('transaction_id', $trxIds)
            ->where('ticket_id', $ticketId)
            ->sum(\DB::raw('total + ppn'));
    };
@endphp

<table>
    <thead>
        <tr>
            <th colspan="10" style="font-weight: bold;">Report Transaction Ticket Tanggal {{ Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($to)->subDay(1)->format('d/m/Y') }}</th>
        </tr>
        <tr>
            <th style="background-color: #f2f2f2;">Jenis Ticket</th>
            <th style="background-color: #f2f2f2;">Jumlah</th>
            <th style="background-color: #f2f2f2;">Harga Ticket</th>
            <th style="background-color: #f2f2f2;">PBJT</th>
            <th style="background-color: #f2f2f2;">Total Harga Ticket</th>
            <th style="background-color: #f2f2f2;">QRIS</th>
            <th style="background-color: #f2f2f2;">Debit</th>
            <th style="background-color: #f2f2f2;">Kredit</th>
            <th style="background-color: #f2f2f2;">Transfer</th>
            <th style="background-color: #f2f2f2;">Lain-lain</th>
        </tr>
    </thead>
    <tbody>
        @php
            $queryTrxTicket = App\Models\Transaction::where(['is_active' => 1, 'transaction_type' => 'ticket'])->whereBetween('created_at', [$from, $to]);
            if ($kasir != 'all' && $kasir) {
                $queryTrxTicket->where('transactions.user_id', $kasir);
            }
            $idTrxTicket = $queryTrxTicket->pluck('id');
            $totalQtyTicket = 0;
            $totalAmountTicket = 0;
            $totalTicketByMethod = array_fill_keys($methodKeys, 0);
        @endphp

        @foreach($tickets as $ticket)
            @php
                $qty = App\Models\DetailTransaction::whereIn('transaction_id', $idTrxTicket)->where('ticket_id', $ticket->id)->sum('qty');
                $totalPerTicket = App\Models\DetailTransaction::whereIn('transaction_id', $idTrxTicket)->where('ticket_id', $ticket->id)->sum(\DB::raw('total + ppn'));
                $ticketByMethod = [];
                foreach ($methodKeys as $methodKey) {
                    $ticketByMethod[$methodKey] = $sumTicketByMethod((int) $ticket->id, $queryTrxTicket, $methodKey);
                }

                if ($qty > 0) {
                    $totalQtyTicket += $qty;
                    $totalAmountTicket += $totalPerTicket;
                    foreach ($methodKeys as $methodKey) {
                        $totalTicketByMethod[$methodKey] += $ticketByMethod[$methodKey];
                    }
                }
            @endphp

            @if($qty > 0)
                <tr>
                    <td>{{ $ticket->name }}</td>
                    <td>{{ $qty }}</td>
                    <td>{{ number_format($ticket->harga + $ticket->ppn, 0, ',', '.') }}</td>
                    <td>{{ $ticket->use_ppn == 1 ? 'YA' : '-' }}</td>
                    <td>{{ number_format($totalPerTicket, 0, ',', '.') }}</td>
                    @foreach($methodKeys as $methodKey)
                        <td>{{ number_format($ticketByMethod[$methodKey], 0, ',', '.') }}</td>
                    @endforeach
                </tr>
            @endif
        @endforeach

        <tr>
            <th style="font-weight: bold;">Total Penjualan Ticket :</th>
            <th style="font-weight: bold;">{{ $totalQtyTicket }}</th>
            <th colspan="2"></th>
            <th style="font-weight: bold;">{{ number_format($totalAmountTicket, 0, ',', '.') }}</th>
            @foreach($methodKeys as $methodKey)
                <th style="font-weight: bold;">{{ number_format($totalTicketByMethod[$methodKey], 0, ',', '.') }}</th>
            @endforeach
        </tr>

        <tr><td colspan="10"></td></tr>

        <tr>
            <th colspan="10" style="font-weight: bold;">Report Transaction Renewal Tanggal {{ Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($to)->subDay(1)->format('d/m/Y') }}</th>
        </tr>
        <tr>
            <th style="background-color: #f2f2f2;">Jenis Transaksi</th>
            <th style="background-color: #f2f2f2;">Jumlah</th>
            <th style="background-color: #f2f2f2;">Harga</th>
            <th style="background-color: #f2f2f2;">PBJT</th>
            <th style="background-color: #f2f2f2;">Total Harga</th>
            <th style="background-color: #f2f2f2;">QRIS</th>
            <th style="background-color: #f2f2f2;">Debit</th>
            <th style="background-color: #f2f2f2;">Kredit</th>
            <th style="background-color: #f2f2f2;">Transfer</th>
            <th style="background-color: #f2f2f2;">Lain-lain</th>
        </tr>

        @php
            $orderedMemberships = $memberships->sortBy('name');
            $totalQtyRenewal = 0;
            $totalAmountRenewal = 0;
            $totalQtyRegistration = 0;
            $totalAmountRegistration = 0;
            $totalQtyLainLain = 0;
            $totalAmountLainLain = 0;
            $totalRenewalByMethod = array_fill_keys($methodKeys, 0);
            $totalRegistrationByMethod = array_fill_keys($methodKeys, 0);
            $totalLainLainByMethod = array_fill_keys($methodKeys, 0);
        @endphp

        @foreach($orderedMemberships as $membership)
            @php
                $queryTrxRenewal = App\Models\Transaction::where(['is_active' => 1, 'transaction_type' => 'renewal', 'ticket_id' => $membership->id])->whereBetween('created_at', [$from, $to]);
                if ($kasir != 'all' && $kasir) {
                    $queryTrxRenewal->where('transactions.user_id', $kasir);
                }
                $qtyRenewal = $queryTrxRenewal->count();
                $totalPerRenewal = $queryTrxRenewal->sum(\DB::raw('(bayar - kembali) + ppn + admin_fee'));
                $renewalByMethod = [];
                foreach ($methodKeys as $methodKey) {
                    $renewalByMethod[$methodKey] = $sumTrxByMethod($queryTrxRenewal, $methodKey, '(bayar - kembali) + ppn + admin_fee');
                }

                $totalQtyRenewal += $qtyRenewal;
                $totalAmountRenewal += $queryTrxRenewal->sum(\DB::raw('(bayar - kembali) + ppn + admin_fee'));
                foreach ($methodKeys as $methodKey) {
                    $totalRenewalByMethod[$methodKey] += $renewalByMethod[$methodKey];
                }
            @endphp

            @if($qtyRenewal > 0)
                <tr>
                    <td>Renewal ({{ $membership->name }})</td>
                    <td>{{ $qtyRenewal }}</td>
                    <td>{{ number_format($membership->price, 0, ',', '.') }}</td>
                    <td>{{ $membership->use_ppn == 1 ? 'YA' : '-' }}</td>
                    <td>{{ number_format($totalPerRenewal, 0, ',', '.') }}</td>
                    @foreach($methodKeys as $methodKey)
                        <td>{{ number_format($renewalByMethod[$methodKey], 0, ',', '.') }}</td>
                    @endforeach
                </tr>
            @endif
        @endforeach
        <tr>
            <th style="font-weight: bold;">Total Penjualan Renewal :</th>
            <th style="font-weight: bold;">{{ $totalQtyRenewal }}</th>
            <th colspan="2"></th>
            <th style="font-weight: bold;">{{ number_format($totalAmountRenewal, 0, ',', '.') }}</th>
            @foreach($methodKeys as $methodKey)
                <th style="font-weight: bold;">{{ number_format($totalRenewalByMethod[$methodKey], 0, ',', '.') }}</th>
            @endforeach
        </tr>

        <tr>
            <th colspan="10" style="font-weight: bold;">Report Transaction Registration Tanggal {{ Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($to)->subDay(1)->format('d/m/Y') }}</th>
        </tr>
        @foreach($orderedMemberships as $membership)
            @php
                $queryTrxRegistration = App\Models\Transaction::where(['is_active' => 1, 'transaction_type' => 'registration', 'ticket_id' => $membership->id])->whereBetween('created_at', [$from, $to]);
                if ($kasir != 'all' && $kasir) {
                    $queryTrxRegistration->where('transactions.user_id', $kasir);
                }
                $qtyRegistration = $queryTrxRegistration->count();
                $totalPerRegistration = $queryTrxRegistration->sum(\DB::raw('(bayar - kembali) + ppn + admin_fee'));
                $registrationByMethod = [];
                foreach ($methodKeys as $methodKey) {
                    $registrationByMethod[$methodKey] = $sumTrxByMethod($queryTrxRegistration, $methodKey, '(bayar - kembali) + ppn + admin_fee');
                }

                $totalQtyRegistration += $qtyRegistration;
                $totalAmountRegistration += $queryTrxRegistration->sum(\DB::raw('(bayar - kembali) + ppn + admin_fee'));
                foreach ($methodKeys as $methodKey) {
                    $totalRegistrationByMethod[$methodKey] += $registrationByMethod[$methodKey];
                }
            @endphp

            @if($qtyRegistration > 0)
                <tr>
                    <td>Registration ({{ $membership->name }})</td>
                    <td>{{ $qtyRegistration }}</td>
                    <td>{{ number_format($membership->price, 0, ',', '.') }}</td>
                    <td>{{ $membership->use_ppn == 1 ? 'YA' : '-' }}</td>
                    <td>{{ number_format($totalPerRegistration, 0, ',', '.') }}</td>
                    @foreach($methodKeys as $methodKey)
                        <td>{{ number_format($registrationByMethod[$methodKey], 0, ',', '.') }}</td>
                    @endforeach
                </tr>
            @endif
        @endforeach
        <tr>
            <th style="font-weight: bold;">Total Penjualan Registration :</th>
            <th style="font-weight: bold;">{{ $totalQtyRegistration }}</th>
            <th colspan="2"></th>
            <th style="font-weight: bold;">{{ number_format($totalAmountRegistration, 0, ',', '.') }}</th>
            @foreach($methodKeys as $methodKey)
                <th style="font-weight: bold;">{{ number_format($totalRegistrationByMethod[$methodKey], 0, ',', '.') }}</th>
            @endforeach
        </tr>

        <tr>
            <th colspan="10" style="font-weight: bold;">Report Transaction Lain-lain Tanggal {{ Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($to)->subDay(1)->format('d/m/Y') }}</th>
        </tr>
        @foreach($sewa as $sewaItem)
            @php
                $rentalPenyewaanIds = App\Models\Penyewaan::where('sewa_id', $sewaItem->id)->pluck('id');
                $queryTrxRental = App\Models\Transaction::where(['is_active' => 1, 'transaction_type' => 'rental'])
                    ->whereIn('ticket_id', $rentalPenyewaanIds)
                    ->whereBetween('transactions.created_at', [$from, $to]);
                if ($kasir != 'all' && $kasir) {
                    $queryTrxRental->where('transactions.user_id', $kasir);
                }
                            $qtyRental = (clone $queryTrxRental)
                                ->leftJoin('penyewaans as p', 'p.id', '=', 'transactions.ticket_id')
                                ->sum(\DB::raw('COALESCE(p.qty, 1)'));
                $totalPerRental = $queryTrxRental->sum(\DB::raw('(bayar - kembali) + ppn'));
                $rentalByMethod = [];
                foreach ($methodKeys as $methodKey) {
                    $rentalByMethod[$methodKey] = $sumTrxByMethod($queryTrxRental, $methodKey, '(bayar - kembali) + ppn');
                }

                $totalQtyLainLain += $qtyRental;
                $totalAmountLainLain += $queryTrxRental->sum(\DB::raw('(bayar - kembali) + ppn'));
                foreach ($methodKeys as $methodKey) {
                    $totalLainLainByMethod[$methodKey] += $rentalByMethod[$methodKey];
                }
            @endphp

            @if($qtyRental > 0)
                <tr>
                    <td>Rental ({{ $sewaItem->name }})</td>
                    <td>{{ $qtyRental }}</td>
                    <td>{{ number_format($sewaItem->harga, 0, ',', '.') }}</td>
                    <td>{{ $sewaItem->use_ppn == 1 ? 'YA' : '-' }}</td>
                    <td>{{ number_format($totalPerRental, 0, ',', '.') }}</td>
                    @foreach($methodKeys as $methodKey)
                        <td>{{ number_format($rentalByMethod[$methodKey], 0, ',', '.') }}</td>
                    @endforeach
                </tr>
            @endif
        @endforeach

        @php
            $queryOtherTypes = App\Models\Transaction::where('is_active', 1)
                ->whereBetween('created_at', [$from, $to])
                ->whereNotIn('transaction_type', ['ticket', 'renewal', 'registration', 'rental']);
            if ($kasir != 'all' && $kasir) {
                $queryOtherTypes->where('transactions.user_id', $kasir);
            }
            $otherTypeRows = $queryOtherTypes
                ->select(
                    'transaction_type',
                    \DB::raw('COUNT(*) as qty'),
                    \DB::raw('SUM((bayar - kembali) + ppn) as total_with_ppn'),
                    \DB::raw('SUM(bayar - kembali) as total_without_ppn'),
                    \DB::raw("SUM(CASE WHEN metode IN ('qris', 'qr') THEN ((bayar - kembali) + ppn) ELSE 0 END) as total_qris"),
                    \DB::raw("SUM(CASE WHEN metode = 'debit' THEN ((bayar - kembali) + ppn) ELSE 0 END) as total_debit"),
                    \DB::raw("SUM(CASE WHEN metode IN ('kredit', 'credit', 'credit card', 'kartu kredit') THEN ((bayar - kembali) + ppn) ELSE 0 END) as total_kredit"),
                    \DB::raw("SUM(CASE WHEN metode = 'transfer' THEN ((bayar - kembali) + ppn) ELSE 0 END) as total_transfer"),
                    \DB::raw("SUM(CASE WHEN (metode IN ('lain-lain', 'tap', 'cash') OR metode IS NULL OR metode = '' OR metode NOT IN ('qris', 'qr', 'debit', 'kredit', 'credit', 'credit card', 'kartu kredit', 'transfer', 'lain-lain', 'tap', 'cash')) THEN ((bayar - kembali) + ppn) ELSE 0 END) as total_lain_lain")
                )
                ->groupBy('transaction_type')
                ->get();
        @endphp
        @foreach($otherTypeRows as $otherRow)
            @php
                $totalQtyLainLain += (int) $otherRow->qty;
                $totalAmountLainLain += (float) $otherRow->total_with_ppn;
                $otherByMethod = [
                    'qris' => (float) ($otherRow->total_qris ?? 0),
                    'debit' => (float) ($otherRow->total_debit ?? 0),
                    'kredit' => (float) ($otherRow->total_kredit ?? 0),
                    'transfer' => (float) ($otherRow->total_transfer ?? 0),
                    'lain_lain' => (float) ($otherRow->total_lain_lain ?? 0),
                ];
                foreach ($methodKeys as $methodKey) {
                    $totalLainLainByMethod[$methodKey] += $otherByMethod[$methodKey];
                }
            @endphp
            <tr>
                <td>{{ ucwords(str_replace('_', ' ', (string) $otherRow->transaction_type)) }}</td>
                <td>{{ (int) $otherRow->qty }}</td>
                <td>-</td>
                <td>-</td>
                <td>{{ number_format((float) $otherRow->total_with_ppn, 0, ',', '.') }}</td>
                @foreach($methodKeys as $methodKey)
                    <td>{{ number_format($otherByMethod[$methodKey], 0, ',', '.') }}</td>
                @endforeach
            </tr>
        @endforeach

        <tr>
            <th style="font-weight: bold;">Total Penjualan Lain-lain :</th>
            <th style="font-weight: bold;">{{ $totalQtyLainLain }}</th>
            <th colspan="2"></th>
            <th style="font-weight: bold;">{{ number_format($totalAmountLainLain, 0, ',', '.') }}</th>
            @foreach($methodKeys as $methodKey)
                <th style="font-weight: bold;">{{ number_format($totalLainLainByMethod[$methodKey], 0, ',', '.') }}</th>
            @endforeach
        </tr>

        <tr><td colspan="10"></td></tr>

        @php
            $totalQtyNonTicket = $totalQtyRenewal + $totalQtyRegistration + $totalQtyLainLain;
            $totalAmountNonTicket = $totalAmountRenewal + $totalAmountRegistration + $totalAmountLainLain;
            $totalSalesQty = App\Models\DetailTransaction::whereIn('transaction_id', $idTrxTicket)->sum('qty') + $totalQtyNonTicket;

            $totalDiscount = $queryTrxAll->sum('disc');
            $totalPPN = App\Models\DetailTransaction::whereIn('transaction_id', $idTrxAll)->sum('ppn') +
                        App\Models\Transaction::whereIn('id', $idTrxAll)->whereIn('transaction_type', ['renewal', 'registration', 'rental'])->sum('ppn');
            $totalAdminFee = App\Models\Transaction::whereIn('id', $idTrxAll)
                ->sum(\DB::raw($adminFeeExpr));

            $qrisid = $queryTrxAll->clone()->whereIn('metode', ['qris', 'qr'])->pluck('id');
            $debitid = $queryTrxAll->clone()->where('metode', 'debit')->pluck('id');
            $kreditid = $queryTrxAll->clone()->whereIn('metode', ['kredit', 'credit', 'credit card', 'kartu kredit'])->pluck('id');
            $transferid = $queryTrxAll->clone()->where('metode', 'transfer')->pluck('id');
            $lainnyaid = $queryTrxAll->clone()
                ->where(function ($q) {
                    $q->whereIn('metode', ['lain-lain', 'tap', 'cash'])
                        ->orWhereNull('metode')
                        ->orWhere('metode', '')
                        ->orWhereNotIn('metode', [
                            'qris',
                            'qr',
                            'debit',
                            'kredit',
                            'credit',
                            'credit card',
                            'kartu kredit',
                            'transfer',
                            'lain-lain',
                            'tap',
                            'cash',
                        ]);
                })
                ->pluck('id');

            $calculateTotalPerMethod = function ($trxIds) {
                $detailTotal = App\Models\DetailTransaction::whereIn('transaction_id', $trxIds)->sum(\DB::raw('total + ppn'));
                $trxNonDetailTotal = App\Models\Transaction::whereIn('id', $trxIds)
                    ->whereIn('transaction_type', ['renewal', 'registration', 'rental'])
                    ->sum(\DB::raw('(bayar - kembali) + ppn + ' . "(CASE WHEN transaction_type IN ('registration', 'renewal') THEN admin_fee ELSE 0 END)"));
                return $detailTotal + $trxNonDetailTotal;
            };

            $qrisTotal = $calculateTotalPerMethod($qrisid);
            $debitTotal = $calculateTotalPerMethod($debitid);
            $kreditTotal = $calculateTotalPerMethod($kreditid);
            $transferTotal = $calculateTotalPerMethod($transferid);
            $lainnyaTotal = $calculateTotalPerMethod($lainnyaid);

            $qrisQty = $qrisid->count();
            $debitQty = $debitid->count();
            $kreditQty = $kreditid->count();
            $transferQty = $transferid->count();
            $lainnyaQty = $lainnyaid->count();

            $pembayaranLainnyaTotal = $lainnyaTotal;
            $pembayaranLainnyaQty = $lainnyaQty;
            $grandTotalIncome = $qrisTotal + $debitTotal + $kreditTotal + $transferTotal + $pembayaranLainnyaTotal;
            $grandTotalQtyAll = $qrisQty + $debitQty + $kreditQty + $transferQty + $pembayaranLainnyaQty;

            $totalSalesAmount = $grandTotalIncome;
            $totalAmountSetelahDiskon = $grandTotalIncome - $totalDiscount;
            // Nilai ini sudah include PBJT + admin dari basis grand total.
            $totalAmountAkhirPlusPBJTAdmin = $totalAmountSetelahDiskon;
        @endphp

        <tr>
            <th colspan="2">Total Penjualan :</th>
            <th>{{ $totalSalesQty }}</th>
            <th colspan="6"></th>
            <th>{{ number_format($totalSalesAmount, 0, ',', '.') }}</th>
        </tr>
        <tr>
            <th colspan="9">Total Discount :</th>
            <th>{{ number_format($totalDiscount, 0, ',', '.') }}</th>
        </tr>
        <tr>
            <th colspan="9">Total PBJT :</th>
            <th>{{ number_format($totalPPN, 0, ',', '.') }}</th>
        </tr>
        <tr>
            <th colspan="9">Total Biaya Admin :</th>
            <th>{{ number_format($totalAdminFee, 0, ',', '.') }}</th>
        </tr>

        <tr>
            <th colspan="2">TOTAL QRIS</th>
            <th colspan="6">Rp {{ number_format($qrisTotal, 2, ',', '.') }}</th>
            <th>TOTAL TRANSAKSI QRIS</th>
            <th>{{ $qrisQty }} Transaksi</th>
        </tr>
        <tr>
            <th colspan="2">TOTAL DEBIT</th>
            <th colspan="6">Rp {{ number_format($debitTotal, 2, ',', '.') }}</th>
            <th>TOTAL TRANSAKSI DEBIT</th>
            <th>{{ $debitQty }} Transaksi</th>
        </tr>
        <tr>
            <th colspan="2">TOTAL KREDIT</th>
            <th colspan="6">Rp {{ number_format($kreditTotal, 2, ',', '.') }}</th>
            <th>TOTAL TRANSAKSI KREDIT</th>
            <th>{{ $kreditQty }} Transaksi</th>
        </tr>
        <tr>
            <th colspan="2">TOTAL TRANSFER</th>
            <th colspan="6">Rp {{ number_format($transferTotal, 2, ',', '.') }}</th>
            <th>TOTAL TRANSAKSI TRANSFER</th>
            <th>{{ $transferQty }} Transaksi</th>
        </tr>
        <tr>
            <th colspan="2">TOTAL PEMBAYARAN LAINNYA</th>
            <th colspan="6">Rp {{ number_format($pembayaranLainnyaTotal, 2, ',', '.') }}</th>
            <th>TOTAL TRANSAKSI PEMBAYARAN LAINNYA</th>
            <th>{{ $pembayaranLainnyaQty }} Transaksi</th>
        </tr>
        <tr>
            <th colspan="2">GRANDTOTAL INCOME</th>
            <th colspan="6">Rp {{ number_format($grandTotalIncome, 2, ',', '.') }}</th>
            <th>GRANDTOTAL TRANSAKSI</th>
            <th>{{ $grandTotalQtyAll }} Transaksi</th>
        </tr>
        <tr>
            <th colspan="9">Total Amount Akhir (Total Penjualan - Diskon) :</th>
            <th>{{ number_format($totalAmountSetelahDiskon, 0, ',', '.') }}</th>
        </tr>
        <tr>
            <th colspan="9">Total Amount Akhir (Total Penjualan - Diskon) + PBJT + Biaya Admin:</th>
            <th>{{ number_format($totalAmountAkhirPlusPBJTAdmin, 0, ',', '.') }}</th>
        </tr>
    </tbody>
</table>
