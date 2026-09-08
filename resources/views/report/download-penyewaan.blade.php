<style>
    .table {
        max-width: 80mm !important;
        margin: 0 auto 0 auto;
        vertical-align: top;
        border-style: solid;
        border-width: 1px;
    }

    @media print {
        .table {
            max-width: 72mm !important;
            margin-left: -10px !important;
        }
    }
</style>

<table class="table table-bordered table-hover" border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th colspan="5">Report Transaksi Lainnya <br> Tanggal {{ Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ request('to') ? Carbon\Carbon::parse($to)->subDay(1)->format('d/m/Y') : Carbon\Carbon::parse($to)->format('d/m/Y') }}</th>
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
            @if($kasir == 'all')
            <td>{{ $sw->name }}</td>
            <td class="text-center">{{ App\Models\Penyewaan::whereBetween('created_at', [$from, $to])->where('sewa_id', $sw->id)->sum('qty') }}</td>
            <td class="text-center">{{ number_format($sw->harga, 0, ',', '.') }}</td>
            <td class="text-end">
                {{ number_format(App\Models\Penyewaan::whereBetween('created_at', [$from, $to])->where('sewa_id', $sw->id)->sum('jumlah'), 0, ',', '.') ?? 0 }}
            </td>
            @else
            <td>{{ $sw->name }}</td>
            <td class="text-center">{{ App\Models\Penyewaan::where('user_id', $kasir)->whereBetween('created_at', [$from, $to])->where('sewa_id', $sw->id)->sum('qty') }}</td>
            <td class="text-center">{{ number_format($sw->harga, 0, ',', '.') }}</td>
            <td class="text-end">
                {{ number_format(App\Models\Penyewaan::where('user_id', $kasir)->whereBetween('created_at', [$from, $to])->where('sewa_id', $sw->id)->sum('jumlah'), 0, ',', '.') ?? 0 }}
            </td>
            @endif
        </tr>
        @endforeach
        <tr>
            <th>Total Amount :</th>
            @if($kasir == 'all')
            <th class="text-end">
                <b>{{ App\Models\Penyewaan::whereBetween('created_at', [$from, $to])->sum('qty') }}</b>
            </th>
            <th colspan="2" class="text-end">
                <b>{{ number_format(App\Models\Penyewaan::whereBetween('created_at', [$from, $to])->sum('jumlah'), 0, ',', '.') }}</b>
            </th>
            @else
            <th class="text-end">
                <b>{{ App\Models\Penyewaan::where('user_id', $kasir)->whereBetween('created_at', [$from, $to])->sum('qty') }}</b>
            </th>
            <th colspan="2" class="text-end">
                <b>{{ number_format(App\Models\Penyewaan::where('user_id', $kasir)->whereBetween('created_at', [$from, $to])->sum('jumlah'), 0, ',', '.') }}</b>
            </th>
            @endif
        </tr>
    </tbody>
</table>

<script src="{{ asset('/js/jquery.min.js') }}"></script>

<script>
    $(document).ready(function() {
        window.print();
    })
</script>
