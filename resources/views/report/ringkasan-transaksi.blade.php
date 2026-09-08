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
        <form action="" method="get" class="row mb-3">
            <div class="col-md-4">
                <label for="daterange">Tanggal</label>
                <input type="text" name="daterange" id="daterange" class="form-control"
                    value="{{ request('daterange') ?: \Carbon\Carbon::parse($from)->format('m/d/Y') . ' - ' . \Carbon\Carbon::parse($to)->format('m/d/Y') }}">
                <input type="hidden" name="from" id="from" value="{{ $from }}">
                <input type="hidden" name="to" id="to" value="{{ $to }}">
            </div>
            <div class="col-md-4">
                <label for="kasir">Admin/Kasir</label>
                <select name="kasir" id="kasir" class="form-control">
                    <option value="all" {{ ($kasir ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ (string)($kasir ?? 'all') === (string)$user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Submit</button>
                <a href="{{ route('reports.ringkasan-transaksi.export') }}?from={{ $from }}&to={{ $to }}&kasir={{ $kasir ?? 'all' }}" class="btn btn-success">
                    <i class="fas fa-file-excel me-1"></i>Download
                </a>
            </div>
        </form>

        <table class="table table-bordered table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 150px">Tanggal</th>
                    <th class="text-end">Member</th>
                    <th class="text-end">Ticket</th>
                    <th class="text-end">Lain-lain</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">DPP</th>
                    <th class="text-end">PBJT</th>
                    <th class="text-end">Biaya Admin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td>{{ $row['tanggal'] }}</td>
                    <td class="text-end">Rp. {{ number_format($row['member'], 0, ',', '.') }}</td>
                    <td class="text-end">Rp. {{ number_format($row['ticket'], 0, ',', '.') }}</td>
                    <td class="text-end">Rp. {{ number_format($row['lain_lain'], 0, ',', '.') }}</td>
                    <td class="text-end">Rp. {{ number_format($row['total'], 0, ',', '.') }}</td>
                    <td class="text-end">Rp. {{ number_format($row['dpp'], 0, ',', '.') }}</td>
                    <td class="text-end">Rp. {{ number_format($row['ppn'], 0, ',', '.') }}</td>
                    <td class="text-end">Rp. {{ number_format($row['admin_fee'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted">Tidak ada data transaksi pada rentang tanggal ini.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="font-weight: 700; background-color: #f5f5f5;">
                    <th>TOTAL</th>
                    <th class="text-end">Rp. {{ number_format($footer['member'], 0, ',', '.') }}</th>
                    <th class="text-end">Rp. {{ number_format($footer['ticket'], 0, ',', '.') }}</th>
                    <th class="text-end">Rp. {{ number_format($footer['lain_lain'], 0, ',', '.') }}</th>
                    <th class="text-end">Rp. {{ number_format($footer['total'], 0, ',', '.') }}</th>
                    <th class="text-end">Rp. {{ number_format($footer['dpp'], 0, ',', '.') }}</th>
                    <th class="text-end">Rp. {{ number_format($footer['ppn'], 0, ',', '.') }}</th>
                    <th class="text-end">Rp. {{ number_format($footer['admin_fee'] ?? 0, 0, ',', '.') }}</th>
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
    function syncDateRangeFields() {
        const value = $("#daterange").val() || '';
        if (!value.includes(' - ')) return;

        const parts = value.split(' - ');
        const start = moment(parts[0], 'MM/DD/YYYY', true);
        const end = moment(parts[1], 'MM/DD/YYYY', true);
        if (!start.isValid() || !end.isValid()) return;

        $("#from").val(start.format('YYYY-MM-DD'));
        $("#to").val(end.format('YYYY-MM-DD'));
    }

    (function initDateRange() {
        const start = moment("{{ \Carbon\Carbon::parse($from)->format('Y-m-d') }}", 'YYYY-MM-DD', true);
        const end = moment("{{ \Carbon\Carbon::parse($to)->format('Y-m-d') }}", 'YYYY-MM-DD', true);

        $("#daterange").daterangepicker({
            opens: "right",
            autoUpdateInput: true,
            startDate: start,
            endDate: end,
            locale: {
                format: "MM/DD/YYYY",
                separator: " - "
            }
        });
    })();

    $("form").on('submit', function() {
        syncDateRangeFields();
    });
</script>
@endpush
