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
            <th colspan="5">History Karyawan <br> Tanggal {{ Carbon\Carbon::parse($start)->format('d/m/Y') }} - {{ request('end') ? Carbon\Carbon::parse($end)->subDay(1)->format('d/m/Y') : Carbon\Carbon::parse($end)->format('d/m/Y') }}</th>
        </tr>
        <tr>
            <th class="text-center">Nama Karyawan</th>
            <th class="text-center">Jumlah Scan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $karyawan)
        <tr>
            <td style="text-align: center;">{{ $karyawan->nama }}</td>
            <td style="text-align: center;">{{ $karyawan->histories_count }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<script src="{{ asset('/js/jquery.min.js') }}"></script>

<script>
    $(document).ready(function() {
        window.print();
    })
</script>