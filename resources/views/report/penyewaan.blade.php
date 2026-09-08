@extends('layouts.master', ['title' => $title, 'breadcrumbs' => $breadcrumbs])

@push('style')
<link href="{{ asset('/') }}plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet" />
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
        <form action="" method="get">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="daterange">Tanggal</label>
                        <input type="text" name="daterange" id="daterange" class="form-control"
                            value="{{ request('daterange') ?: now('Asia/Jakarta')->format('m/d/Y') . ' - ' . now('Asia/Jakarta')->format('m/d/Y') }}">
                        <input type="hidden" name="from" id="from" value="{{ $from ?? request('from') }}">
                        <input type="hidden" name="to" id="to" value="{{ $to ?? request('to') }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="kasir">Kasir</label>
                        <select name="kasir" id="kasir" class="form-control">
                            <option value="all" {{ ($kasir ?? request('kasir', 'all')) == 'all' ? 'selected' : '' }}>All</option>
                            @foreach($users as $user)
                            <option {{ ($kasir ?? request('kasir')) == $user->id ? 'selected' : '' }} value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group mt-1">
                        <button type="submit" class="btn btn-primary mt-3">Submit</button>
                        <a href="{{ route('report.penyewaan.export') }}?from={{ $from }}&to={{ $to }}&kasir={{ $kasir ?? 'all' }}" class="btn btn-success mt-3"><i class="fas fa-file-excel me-1"></i>Download</a>
                    </div>
                </div>
            </div>
        </form>
        <table class="table table-bordered table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 120px">Tanggal Group</th>
                    <th style="width: 50px">No</th>
                    <th>No Bukti</th>
                    <th>Tanggal</th>
                    <th>FO/Kasir</th>
                    <th>Kode Transaksi</th>
                    <th>Jenis Transaksi</th>
                    <th>Item</th>
                    <th class="text-center">Qty</th>
                    <th>Metode</th>
                    <th class="text-end">PBJT</th>
                    <th class="text-end">Total Bayar</th>
                </tr>
            </thead>
            <tbody>
                @php $hasData = false; @endphp
                @forelse($groupedRows as $dateGroup)
                    @php $hasData = true; @endphp
                    @php
                        $dateCellRendered = false;
                        $dateRowspan = max((int) ($dateGroup['rowspan'] ?? 1), 1);
                    @endphp
                    @foreach($dateGroup['groups'] as $group)
                        @foreach($group['details'] as $detail)
                        <tr>
                            @if(!$dateCellRendered)
                                <td rowspan="{{ $dateRowspan }}" class="align-top fw-bold">{{ $dateGroup['tanggal_label'] }}</td>
                                @php $dateCellRendered = true; @endphp
                            @endif
                            <td>{{ $detail['no'] }}</td>
                            <td>{{ $detail['no_bukti'] }}</td>
                            <td>{{ $detail['tanggal'] }}</td>
                            <td>{{ $detail['kasir'] }}</td>
                            <td>{{ $detail['kode_trx'] }}</td>
                            <td>{{ $group['transaction_type_label'] }}</td>
                            <td>{{ $group['item_name'] }}</td>
                            <td class="text-center">{{ number_format($detail['qty'], 0, ',', '.') }}</td>
                            <td>{{ $detail['metode'] }}</td>
                            <td class="text-end">Rp. {{ number_format($detail['ppn'], 0, ',', '.') }}</td>
                            <td class="text-end">Rp. {{ number_format($detail['total_bayar'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        <tr style="background-color: #f5f5f5; font-weight: 700;">
                            <td colspan="6">TOTAL</td>
                            <td>{{ $group['item_name'] }}</td>
                            <td class="text-center">{{ number_format($group['subtotal_qty'], 0, ',', '.') }}</td>
                            <td></td>
                            <td class="text-end">Rp. {{ number_format($group['subtotal_ppn'], 0, ',', '.') }}</td>
                            <td class="text-end">Rp. {{ number_format($group['subtotal_total'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr style="background-color: #e8ecef; font-weight: 700;">
                        <td colspan="7" class="text-end">TOTAL TANGGAL {{ $dateGroup['tanggal_label'] }}</td>
                        <td class="text-center">{{ number_format($dateGroup['subtotal_qty'], 0, ',', '.') }}</td>
                        <td></td>
                        <td class="text-end">Rp. {{ number_format($dateGroup['subtotal_ppn'], 0, ',', '.') }}</td>
                        <td class="text-end">Rp. {{ number_format($dateGroup['subtotal_total'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                @endforelse
                @if(!$hasData)
                    <tr>
                        <td colspan="12" class="text-center text-muted">Tidak ada data transaksi pada rentang tanggal ini.</td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="8" class="text-end">GRAND TOTAL</th>
                    <th class="text-center">{{ number_format($grandQty, 0, ',', '.') }}</th>
                    <th></th>
                    <th class="text-end">Rp. {{ number_format($grandPpn, 0, ',', '.') }}</th>
                    <th class="text-end">Rp. {{ number_format($grandTotal, 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection

@push('script')
<script src="{{ asset('/') }}plugins/moment/min/moment.min.js"></script>
<script src="{{ asset('/') }}plugins/bootstrap-daterangepicker/daterangepicker.js"></script>

<script>
    function syncReportDateRangeFields() {
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

    (function initDateRange() {
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
        syncReportDateRangeFields();

        $("#daterange").on('apply.daterangepicker', function() {
            syncReportDateRangeFields();
        });
    })();

    $("form").on('submit', function() {
        syncReportDateRangeFields();
    });
</script>
@endpush
