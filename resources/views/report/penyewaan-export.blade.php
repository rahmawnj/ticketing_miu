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
            @if($kasir == 'all')
            <td>{{ $sw->name }}</td>
            <td class="text-center">{{ App\Models\Penyewaan::whereBetween('created_at', [$from, $to])->where('sewa_id', $sw->id)->sum('qty') }}</td>
            <td class="text-center">{{ $sw->harga }}</td>
            <td class="text-end">
                {{ App\Models\Penyewaan::whereBetween('created_at', [$from, $to])->where('sewa_id', $sw->id)->sum('jumlah') ?? 0 }}
            </td>
            @else
            <td>{{ $sw->name }}</td>
            <td class="text-center">{{ App\Models\Penyewaan::where('user_id', $kasir)->whereBetween('created_at', [$from, $to])->where('sewa_id', $sw->id)->sum('qty') }}</td>
            <td class="text-center">{{ $sw->harga }}</td>
            <td class="text-end">
                {{ App\Models\Penyewaan::where('user_id', $kasir)->whereBetween('created_at', [$from, $to])->where('sewa_id', $sw->id)->sum('jumlah') ?? 0 }}
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
                <b>{{ App\Models\Penyewaan::whereBetween('created_at', [$from, $to])->sum('jumlah') }}</b>
            </th>
            @else
            <th class="text-end">
                <b>{{ App\Models\Penyewaan::where('user_id', $kasir)->whereBetween('created_at', [$from, $to])->sum('qty') }}</b>
            </th>
            <th colspan="2" class="text-end">
                <b>{{ App\Models\Penyewaan::where('user_id', $kasir)->whereBetween('created_at', [$from, $to])->sum('jumlah') }}</b>
            </th>
            @endif
        </tr>
    </tbody>
</table>
