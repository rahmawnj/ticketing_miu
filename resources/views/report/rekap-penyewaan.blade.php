@extends('layouts.master', ['title' => $title, 'breadcrumbs' => $breadcrumbs])

@push('style')
<link href="{{ asset('/') }}plugins/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />
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
            <div class="col-md-3">
                <label for="from">From</label>
                <input type="date" name="from" id="from" class="form-control" value="{{ request('from') ?? Carbon\Carbon::now()->format('Y-m-d') }}">
            </div>

            <div class="col-md-3">
                <label for="to">To</label>
                <input type="date" name="to" id="to" class="form-control" value="{{ request('to') ?? Carbon\Carbon::now()->format('Y-m-d') }}">
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
                <a href="{{ route('penyewaan.export') }}?from={{ request('from') }}&to={{ request('to') }}&kasir={{ request('kasir') }}" class="btn btn-success mt-3">Export</a>
                <a href="{{ route('penyewaan.download') }}?from={{ request('from') }}&to={{ request('to') }}&kasir={{ request('kasir') }}" class="btn btn-info mt-3"><i class="fas fa-print me-1"></i>Print</a>
            </div>
        </form>

        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th colspan="5">Report Transaksi Lainnya Tanggal {{ Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ request('to') ? Carbon\Carbon::parse($to)->subDay(1)->format('d/m/Y') : Carbon\Carbon::parse($to)->format('d/m/Y') }}</th>
                </tr>
                <tr>
                    <th>Jenis Lainnya</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-center">Harga Lainnya</th>
                    <th class="text-end">Total Harga Lainnya</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sewa as $sw)
                <tr>
                    @if(request('kasir') == 'all')
                    <td>{{ $sw->name }}</td>
                    <td class="text-center">{{ App\Models\Penyewaan::whereBetween('created_at', [$from, $to])->where('sewa_id', $sw->id)->sum('qty') }}</td>
                    <td class="text-center">{{ number_format($sw->harga,0, ',', '.') }}</td>
                    <td class="text-end">
                        {{ number_format(App\Models\Penyewaan::whereBetween('created_at', [$from, $to])->where('sewa_id', $sw->id)->sum('jumlah'), 0, ',', '.') ?? 0 }}
                    </td>
                    @else
                    <td>{{ $sw->name }}</td>
                    <td class="text-center">{{ App\Models\Penyewaan::where('user_id', request('kasir'))->whereBetween('created_at', [$from, $to])->where('sewa_id', $sw->id)->sum('qty') }}</td>
                    <td class="text-center">{{ number_format($sw->harga,0, ',', '.') }}</td>
                    <td class="text-end">
                        {{ number_format(App\Models\Penyewaan::where('user_id', request('kasir'))->whereBetween('created_at', [$from, $to])->where('sewa_id', $sw->id)->sum('jumlah'), 0, ',', '.') ?? 0 }}
                    </td>
                    @endif
                </tr>
                @endforeach
                <tr>
                    <th>Total Amount :</th>
                    @if(request('kasir') == 'all')
                    <th class="text-center">
                        <b>{{ App\Models\Penyewaan::whereBetween('created_at', [$from, $to])->sum('qty') }}</b>
                    </th>
                    <th colspan="2" class="text-end">
                        <b>{{ number_format(App\Models\Penyewaan::whereBetween('created_at', [$from, $to])->sum('jumlah'), 0, ',', '.') }}</b>
                    </th>
                    @else
                    <th class="text-center">
                        <b>{{ App\Models\Penyewaan::where('user_id', request('kasir'))->whereBetween('created_at', [$from, $to])->sum('qty') }}</b>
                    </th>
                    <th colspan="2" class="text-end">
                        <b>{{ number_format(App\Models\Penyewaan::where('user_id', request('kasir'))->whereBetween('created_at', [$from, $to])->sum('jumlah'), 0, ',', '.') }}</b>
                    </th>
                    @endif
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
@endpush
