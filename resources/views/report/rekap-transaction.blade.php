@extends('layouts.master', ['title' => $title, 'breadcrumbs' => $breadcrumbs])

@push('style')
<link href="{{ asset('/') }}plugins/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet" />
<style>
    @media print {
        #header,
        #sidebar,
        .app-sidebar-bg,
        .app-sidebar-mobile-backdrop,
        .breadcrumb,
        .page-header {
            display: none !important;
        }

        #content.app-content {
            margin: 0 !important;
            padding: 0 !important;
        }

        .no-print,
        .panel-heading-btn {
            display: none !important;
        }

        .panel {
            border: 0 !important;
            box-shadow: none !important;
        }

        .panel-body {
            padding: 0 !important;
        }

        .table {
            width: 100% !important;
        }
    }
</style>
@endpush

@section('content')
<div class="panel panel-inverse">
    <div class="panel-heading">
        <h4 class="panel-title">{{ $title }}</h4>
        <div class="panel-heading-btn">
            <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
            <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
            <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
            <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
    </div>

    <div class="panel-body">

        <form action="" method="get" class="row mb-3 no-print">
            <div class="col-md-3">
                <label for="daterange">Tanggal</label>
                <input type="text" name="daterange" id="daterange" class="form-control"
                    value="{{ request('daterange') ?: now('Asia/Jakarta')->format('m/d/Y') . ' - ' . now('Asia/Jakarta')->format('m/d/Y') }}">
                <input type="hidden" name="from" id="from" value="{{ request('from') ?? Carbon\Carbon::now()->format('Y-m-d') }}">
                <input type="hidden" name="to" id="to" value="{{ request('to') ?? Carbon\Carbon::now()->format('Y-m-d') }}">
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label for="kasir">Kasir</label>
                    <select name="kasir" id="kasir" class="form-control">
                        <option value="all" selected>All</option>
                        @foreach($users as $user)
                        <option {{ request('kasir') == $user->id ? 'selected' : '' }} value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-3 mt-1">
                <button type="submit" class="btn btn-primary mt-3">Submit</button>
                <a href="{{ route('transactions.export') }}?from={{ request('from') }}&to={{ request('to') }}&kasir={{ request('kasir') }}" class="btn btn-success mt-3">Export</a>
                <a href="#" id="btn-print-view" class="btn btn-info mt-3"><i class="fas fa-print me-1"></i>Print</a>
            </div>
        </form>

        <div class="mb-5">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th colspan="10">Report Transaction Ticket Tanggal {{ Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ request('to') ? Carbon\Carbon::parse($to)->subDay(1)->format('d/m/Y') : Carbon\Carbon::parse($from)->format('d/m/Y') }}</th>
                    </tr>
                    <tr>
                        <th>Jenis Ticket</th>
                        <th class="text-center">Jumlah</th>
                        <th class="text-center">Harga Ticket</th>
                        <th class="text-center">PBJT</th>
                        <th class="text-end">Total Harga Ticket</th>
                        <th class="text-end">QRIS</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                        <th class="text-end">Transfer</th>
                        <th class="text-end">Lain-lain</th>
                    </tr>
                </thead>
                <tbody>
                    @php
    $queryTrxTicket = App\Models\Transaction::where(['is_active' => 1, 'transaction_type' => 'ticket'])->whereBetween('created_at', [$from, $to]);
    if (request('kasir') != 'all' && request('kasir')) {
        $queryTrxTicket->where('transactions.user_id', request('kasir'));
    }
    $idTrxTicket = $queryTrxTicket->pluck('id');
    $totalQtyTicket = 0;
    $totalAmountTicket = 0;
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
    $totalTicketByMethod = array_fill_keys($methodKeys, 0);

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
            <td class="text-center">{{ $qty }}</td>
            <td class="text-center">{{ number_format($ticket->harga + $ticket->ppn,0, ',', '.') }}</td>
            <td class="text-center">
                <input type="checkbox" class="form-check-input" {{ $ticket->use_ppn == 1 ? 'checked' : '' }} disabled>
            </td>
            <td class="text-end">
                {{ number_format($totalPerTicket, 0, ',', '.') }}
            </td>
            @foreach($methodKeys as $methodKey)
                <td class="text-end">{{ number_format($ticketByMethod[$methodKey], 0, ',', '.') }}</td>
            @endforeach
        </tr>
    @endif
@endforeach
                    <tr>
                        <th>Total Penjualan Ticket :</th>
                        <th class="text-center"><b>{{ $totalQtyTicket }}</b></th>
                        <th colspan="2"></th>
                        <th class="text-end"><b>{{ number_format($totalAmountTicket, 0, ',', '.') }}</b></th>
                        @foreach($methodKeys as $methodKey)
                            <th class="text-end"><b>{{ number_format($totalTicketByMethod[$methodKey], 0, ',', '.') }}</b></th>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>

        <hr/>

        <div class="mb-5">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th colspan="10">Report Transaction Renewal Tanggal {{ Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ request('to') ? Carbon\Carbon::parse($to)->subDay(1)->format('d/m/Y') : Carbon\Carbon::parse($from)->format('d/m/Y') }}</th>
                    </tr>
                    <tr>
                        <th>Jenis Transaksi</th>
                        <th class="text-center">Jumlah</th>
                        <th class="text-center">Harga</th>
                        <th class="text-center">PBJT</th>
                        <th class="text-end">Total Harga</th>
                        <th class="text-end">QRIS</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                        <th class="text-end">Transfer</th>
                        <th class="text-end">Lain-lain</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $orderedMemberships = $memberships->sortBy('name');
                        $adminFeeExpr = "(CASE WHEN transaction_type IN ('registration', 'renewal') THEN admin_fee ELSE 0 END)";
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
                            if (request('kasir') != 'all' && request('kasir')) {
                                $queryTrxRenewal->where('transactions.user_id', request('kasir'));
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
                            <td class="text-center">{{ $qtyRenewal }}</td>
                            <td class="text-center">{{ number_format($membership->price, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input" {{ $membership->use_ppn == 1 ? 'checked' : '' }} disabled>
                            </td>
                            <td class="text-end">
                                {{ number_format($totalPerRenewal, 0, ',', '.') }}
                            </td>
                            @foreach($methodKeys as $methodKey)
                                <td class="text-end">{{ number_format($renewalByMethod[$methodKey], 0, ',', '.') }}</td>
                            @endforeach
                        </tr>
                        @endif
                    @endforeach
                    <tr>
                        <th>Total Penjualan Renewal :</th>
                        <th class="text-center"><b>{{ $totalQtyRenewal }}</b></th>
                        <th colspan="2"></th>
                        <th class="text-end"><b>{{ number_format($totalAmountRenewal, 0, ',', '.') }}</b></th>
                        @foreach($methodKeys as $methodKey)
                            <th class="text-end"><b>{{ number_format($totalRenewalByMethod[$methodKey], 0, ',', '.') }}</b></th>
                        @endforeach
                    </tr>

                    <tr>
                        <th colspan="10">Report Transaction Registration Tanggal {{ Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ request('to') ? Carbon\Carbon::parse($to)->subDay(1)->format('d/m/Y') : Carbon\Carbon::parse($from)->format('d/m/Y') }}</th>
                    </tr>
                    @foreach($orderedMemberships as $membership)
                        @php
                            $queryTrxRegistration = App\Models\Transaction::where(['is_active' => 1, 'transaction_type' => 'registration', 'ticket_id' => $membership->id])->whereBetween('created_at', [$from, $to]);
                            if (request('kasir') != 'all' && request('kasir')) {
                                $queryTrxRegistration->where('transactions.user_id', request('kasir'));
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
                            <td class="text-center">{{ $qtyRegistration }}</td>
                            <td class="text-center">{{ number_format($membership->price, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input" {{ $membership->use_ppn == 1 ? 'checked' : '' }} disabled>
                            </td>
                            <td class="text-end">
                                {{ number_format($totalPerRegistration, 0, ',', '.') }}
                            </td>
                            @foreach($methodKeys as $methodKey)
                                <td class="text-end">{{ number_format($registrationByMethod[$methodKey], 0, ',', '.') }}</td>
                            @endforeach
                        </tr>
                        @endif
                    @endforeach
                    <tr>
                        <th>Total Penjualan Registration :</th>
                        <th class="text-center"><b>{{ $totalQtyRegistration }}</b></th>
                        <th colspan="2"></th>
                        <th class="text-end"><b>{{ number_format($totalAmountRegistration, 0, ',', '.') }}</b></th>
                        @foreach($methodKeys as $methodKey)
                            <th class="text-end"><b>{{ number_format($totalRegistrationByMethod[$methodKey], 0, ',', '.') }}</b></th>
                        @endforeach
                    </tr>

                    <tr>
                        <th colspan="10">Report Transaction Lain-lain Tanggal {{ Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ request('to') ? Carbon\Carbon::parse($to)->subDay(1)->format('d/m/Y') : Carbon\Carbon::parse($from)->format('d/m/Y') }}</th>
                    </tr>

                    @foreach($sewa as $sewaItem)
                        @php
                            $rentalPenyewaanIds = App\Models\Penyewaan::where('sewa_id', $sewaItem->id)->pluck('id');
                            $queryTrxRental = App\Models\Transaction::where(['is_active' => 1, 'transaction_type' => 'rental'])
                                ->whereIn('ticket_id', $rentalPenyewaanIds)
                                ->whereBetween('transactions.created_at', [$from, $to]);
                            if (request('kasir') != 'all' && request('kasir')) {
                                $queryTrxRental->where('transactions.user_id', request('kasir'));
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
                            <td class="text-center">{{ $qtyRental }}</td>
                            <td class="text-center">{{ number_format($sewaItem->harga, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input" {{ $sewaItem->use_ppn == 1 ? 'checked' : '' }} disabled>
                            </td>
                            <td class="text-end">
                                {{ number_format($totalPerRental, 0, ',', '.') }}
                            </td>
                            @foreach($methodKeys as $methodKey)
                                <td class="text-end">{{ number_format($rentalByMethod[$methodKey], 0, ',', '.') }}</td>
                            @endforeach
                        </tr>
                        @endif
                    @endforeach

                    @php
                        $queryOtherTypes = App\Models\Transaction::where('is_active', 1)
                            ->whereBetween('created_at', [$from, $to])
                            ->whereNotIn('transaction_type', ['ticket', 'renewal', 'registration', 'rental']);
                        if (request('kasir') != 'all' && request('kasir')) {
                            $queryOtherTypes->where('transactions.user_id', request('kasir'));
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
                            <td class="text-center">{{ (int) $otherRow->qty }}</td>
                            <td class="text-center">-</td>
                            <td class="text-center">-</td>
                            <td class="text-end">
                                {{ number_format((float) $otherRow->total_with_ppn, 0, ',', '.') }}
                            </td>
                            @foreach($methodKeys as $methodKey)
                                <td class="text-end">{{ number_format($otherByMethod[$methodKey], 0, ',', '.') }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    <tr>
                        <th>Total Penjualan Lain-lain :</th>
                        <th class="text-center"><b>{{ $totalQtyLainLain }}</b></th>
                        <th colspan="2"></th>
                        <th class="text-end"><b>{{ number_format($totalAmountLainLain, 0, ',', '.') }}</b></th>
                        @foreach($methodKeys as $methodKey)
                            <th class="text-end"><b>{{ number_format($totalLainLainByMethod[$methodKey], 0, ',', '.') }}</b></th>
                        @endforeach
                    </tr>
                    @php
                        $totalQtyNonTicket = $totalQtyRenewal + $totalQtyRegistration + $totalQtyLainLain;
                        $totalAmountNonTicket = $totalAmountRenewal + $totalAmountRegistration + $totalAmountLainLain;
                    @endphp
                </tbody>
            </table>
        </div>

        <hr/>

        @php
            $queryTrxAll = App\Models\Transaction::where(['is_active' => 1])->whereBetween('created_at', [$from, $to]);
            if (request('kasir') != 'all' && request('kasir')) {
                $queryTrxAll->where('transactions.user_id', request('kasir'));
            }
            $idTrxAll = $queryTrxAll->pluck('id');

            $totalSalesQty = App\Models\DetailTransaction::whereIn('transaction_id', $idTrxTicket)->sum('qty') + $totalQtyNonTicket;

            $totalDiscount = $queryTrxAll->sum('disc');
            $totalPPN = App\Models\DetailTransaction::whereIn('transaction_id', $idTrxAll)->sum('ppn') +
                                App\Models\Transaction::whereIn('id', $idTrxAll)->whereIn('transaction_type', ['renewal', 'registration', 'rental'])->sum('ppn');
            $totalAdminFee = App\Models\Transaction::whereIn('id', $idTrxAll)->sum(\DB::raw($adminFeeExpr));
            $qrisid = $queryTrxAll->clone()
                ->whereIn('metode', ['qris', 'qr'])
                ->pluck('id');
            $debitid = $queryTrxAll->clone()
                ->where('metode', 'debit')
                ->pluck('id');
            $kreditid = $queryTrxAll->clone()
                ->whereIn('metode', ['kredit', 'credit', 'credit card', 'kartu kredit'])
                ->pluck('id');
            $transferid = $queryTrxAll->clone()
                ->where('metode', 'transfer')
                ->pluck('id');
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

            // Samakan basis hitung total akhir dengan ringkasan metode pembayaran
            // agar tidak terjadi selisih antar bagian report.
            $totalSalesAmount = $grandTotalIncome;
            $totalAmountSetelahDiskon = $grandTotalIncome - $totalDiscount;
            // Nilai ini sudah include PBJT + admin dari basis grand total.
            $totalAmountAkhirPlusPBJTAdmin = $totalAmountSetelahDiskon;
        @endphp


        <table class="table table-bordered table-hover">
            <tbody>
                <tr>
                    <th>Total Penjualan :</th>
                    <th class="text-center">
                        <b>{{ $totalSalesQty }}</b>
                    </th>
                    <th></th>
                    <th></th>
                    <th class="text-end">
                        <b>{{ number_format($totalSalesAmount, 0, ',', '.') }}</b>
                    </th>
                </tr>
                <tr>
                    <th colspan="4">Total Discount :</th>
                    <th class="text-end">
                        <b>{{ number_format($totalDiscount, 0, ',', '.') }}</b>
                    </th>
                </tr>
                <tr>
                    <th colspan="4">Total PBJT :</th>
                    <th class="text-end">
                        <b>{{ number_format($totalPPN, 0, ',', '.') }}</b>
                    </th>
                </tr>
                <tr>
                    <th colspan="4">Total Biaya Admin :</th>
                    <th class="text-end">
                        <b>{{ number_format($totalAdminFee, 0, ',', '.') }}</b>
                    </th>
                </tr>
                <tr>
                    <th rowspan="6"></th>
                    <th>TOTAL QRIS</th>
                    <th class="text-end">Rp {{ number_format($qrisTotal, 2, ',', '.') }}</th>
                    <th>TOTAL TRANSAKSI QRIS</th>
                    <th class="text-end">{{ $qrisQty }} Transaksi</th>
                </tr>
                <tr>
                    <th>TOTAL DEBIT</th>
                    <th class="text-end">Rp {{ number_format($debitTotal, 2, ',', '.') }}</th>
                    <th>TOTAL TRANSAKSI DEBIT</th>
                    <th class="text-end">{{ $debitQty }} Transaksi</th>
                </tr>
                <tr>
                    <th>TOTAL KREDIT</th>
                    <th class="text-end">Rp {{ number_format($kreditTotal, 2, ',', '.') }}</th>
                    <th>TOTAL TRANSAKSI KREDIT</th>
                    <th class="text-end">{{ $kreditQty }} Transaksi</th>
                </tr>
                <tr>
                    <th>TOTAL TRANSFER</th>
                    <th class="text-end">Rp {{ number_format($transferTotal, 2, ',', '.') }}</th>
                    <th>TOTAL TRANSAKSI TRANSFER</th>
                    <th class="text-end">{{ $transferQty }} Transaksi</th>
                </tr>
                <tr>
                    <th>TOTAL PEMBAYARAN LAINNYA</th>
                    <th class="text-end">Rp {{ number_format($pembayaranLainnyaTotal, 2, ',', '.') }}</th>
                    <th>TOTAL TRANSAKSI PEMBAYARAN LAINNYA</th>
                    <th class="text-end">{{ $pembayaranLainnyaQty }} Transaksi</th>
                </tr>
                <tr class="table-secondary">
                    <th>GRANDTOTAL INCOME</th>
                    <th class="text-end">Rp {{ number_format($grandTotalIncome, 2, ',', '.') }}</th>
                    <th>GRANDTOTAL TRANSAKSI</th>
                    <th class="text-end">{{ $grandTotalQtyAll }} Transaksi</th>
                </tr>
                <tr>
                    <th colspan="4">Total Amount Akhir (Total Penjualan - Diskon) :</th>
                    <th class="text-end">
                        <b>{{ number_format($totalAmountSetelahDiskon, 0, ',', '.') }}</b>
                    </th>
                </tr>
                <tr class="table-primary">
                    <th colspan="4">Total Amount Akhir (Total Penjualan - Diskon) + PBJT + Biaya Admin:</th>
                    <th class="text-end">
                        <b>{{ number_format($totalAmountAkhirPlusPBJTAdmin, 0, ',', '.') }}</b>
                    </th>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('script')
<script src="{{ asset('/') }}plugins/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
<script src="{{ asset('/') }}plugins/sweetalert/dist/sweetalert.min.js"></script>
<script src="{{ asset('/') }}plugins/moment/min/moment.min.js"></script>
<script src="{{ asset('/') }}plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
<script>
    function syncRekapDateRangeFields() {
        const rangeValue = $("#daterange").val() || '';
        if (rangeValue.includes(' - ')) {
            const parts = rangeValue.split(' - ');
            const start = moment(parts[0], 'MM/DD/YYYY', true);
            const end = moment(parts[1], 'MM/DD/YYYY', true);
            if (start.isValid() && end.isValid()) {
                $("#from").val(start.format('YYYY-MM-DD'));
                $("#to").val(end.format('YYYY-MM-DD'));
                return;
            }
        }

        const today = moment().format('YYYY-MM-DD');
        $("#from").val(today);
        $("#to").val(today);
    }

    (function initRekapDateRange() {
        const today = moment();
        let start = today.clone();
        let end = today.clone();
        const rangeValue = $("#daterange").val();

        if (rangeValue && rangeValue.includes(' - ')) {
            const parts = rangeValue.split(' - ');
            const parsedStart = moment(parts[0], 'MM/DD/YYYY', true);
            const parsedEnd = moment(parts[1], 'MM/DD/YYYY', true);
            if (parsedStart.isValid() && parsedEnd.isValid()) {
                start = parsedStart;
                end = parsedEnd;
            }
        } else if ($("#from").val() && $("#to").val()) {
            const parsedFrom = moment($("#from").val(), 'YYYY-MM-DD', true);
            const parsedTo = moment($("#to").val(), 'YYYY-MM-DD', true);
            if (parsedFrom.isValid() && parsedTo.isValid()) {
                start = parsedFrom;
                end = parsedTo;
            }
        }

        $("#daterange").daterangepicker({
            opens: "right",
            autoUpdateInput: true,
            locale: {
                format: "MM/DD/YYYY",
                separator: " - "
            },
            startDate: start,
            endDate: end
        });

        $("#daterange").val(start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY'));
        syncRekapDateRangeFields();

        $("#daterange").on('apply.daterangepicker', function() {
            syncRekapDateRangeFields();
        });
    })();

    $("#btn-print-view").on('click', function(e) {
        e.preventDefault();
        window.print();
    });
</script>
@endpush
