<table>
    <tr>
        <td colspan="13" style="text-align: center; font-weight: bold; font-size: 16px;">{{ $reportTitle }}</td>
    </tr>
    <tr>
        <td colspan="13" style="text-align: center; font-weight: bold;">
            PER TANGGAL : {{ $startDate->format('d-m-Y') }}{{ $startDate->toDateString() !== $endDate->toDateString() ? ' s/d ' . $endDate->format('d-m-Y') : '' }}
        </td>
    </tr>
    <tr>
        <td colspan="13" style="text-align: right;">Generated: {{ $generatedAt->format('d-m-Y H:i:s') }}</td>
    </tr>
    <tr><td colspan="13"></td></tr>
    <tr style="font-weight: bold; background: #e9ecef;">
        <td style="border: 1px solid #000;">NO TRANSAKSI</td>
        <td style="border: 1px solid #000;">NAMA KASIR</td>
        <td style="border: 1px solid #000;">TANGGAL</td>
        <td style="border: 1px solid #000;">HARGA</td>
        <td style="border: 1px solid #000;">QTY</td>
        <td style="border: 1px solid #000;">CARA BAYAR</td>
        <td style="border: 1px solid #000;">TUNAI</td>
        <td style="border: 1px solid #000;">DEBIT</td>
        <td style="border: 1px solid #000;">QR</td>
        <td style="border: 1px solid #000;">CREDIT CARD</td>
        <td style="border: 1px solid #000;">TRANSFER</td>
        <td style="border: 1px solid #000;">PEMBAYARAN LAINNYA</td>
        <td style="border: 1px solid #000;">TOTAL BAYAR</td>
    </tr>

    @php
        $totalData = 0;
        $totalQty = 0;
        $totalTunai = 0.0;
        $totalDebit = 0.0;
        $totalQr = 0.0;
        $totalCreditCard = 0.0;
        $totalTransfer = 0.0;
        $totalPembayaranLainnya = 0.0;
        $totalBayar = 0.0;
    @endphp

    @forelse($groups as $row)
        <tr>
            <td style="border: 1px solid #000;">{{ $row['no_transaksi'] }}</td>
            <td style="border: 1px solid #000;">{{ $row['nama_kasir'] }}</td>
            <td style="border: 1px solid #000;">{{ $row['tanggal'] }}</td>
            <td style="border: 1px solid #000;">{{ number_format($row['harga'], 2, ',', '.') }}</td>
            <td style="border: 1px solid #000;">{{ $row['qty'] }}</td>
            <td style="border: 1px solid #000;">{{ $row['cara_bayar'] }}</td>
            <td style="border: 1px solid #000;">{{ number_format($row['tunai'], 2, ',', '.') }}</td>
            <td style="border: 1px solid #000;">{{ number_format($row['debit'], 2, ',', '.') }}</td>
            <td style="border: 1px solid #000;">{{ number_format($row['qr'], 2, ',', '.') }}</td>
            <td style="border: 1px solid #000;">{{ number_format($row['credit_card'], 2, ',', '.') }}</td>
            <td style="border: 1px solid #000;">{{ number_format($row['transfer'], 2, ',', '.') }}</td>
            <td style="border: 1px solid #000;">{{ number_format($row['pembayaran_lainnya'], 2, ',', '.') }}</td>
            <td style="border: 1px solid #000;">{{ number_format($row['total_bayar'], 2, ',', '.') }}</td>
        </tr>
        @php
            $totalData++;
            $totalQty += (int) $row['qty'];
            $totalTunai += (float) $row['tunai'];
            $totalDebit += (float) $row['debit'];
            $totalQr += (float) $row['qr'];
            $totalCreditCard += (float) $row['credit_card'];
            $totalTransfer += (float) $row['transfer'];
            $totalPembayaranLainnya += (float) $row['pembayaran_lainnya'];
            $totalBayar += (float) $row['total_bayar'];
        @endphp
    @empty
        <tr>
            <td colspan="13">Tidak ada data transaksi pada periode ini.</td>
        </tr>
    @endforelse

    @if($totalData > 0)
        <tr style="font-weight: bold; background: #f8f9fa;">
            <td colspan="4" style="border: 1px solid #000;">TOTAL DATA</td>
            <td style="border: 1px solid #000;">{{ $totalData }}</td>
            <td style="border: 1px solid #000;">{{ $totalQty }}</td>
            <td style="border: 1px solid #000;"></td>
            <td style="border: 1px solid #000;">{{ number_format($totalTunai, 2, ',', '.') }}</td>
            <td style="border: 1px solid #000;">{{ number_format($totalDebit, 2, ',', '.') }}</td>
            <td style="border: 1px solid #000;">{{ number_format($totalQr, 2, ',', '.') }}</td>
            <td style="border: 1px solid #000;">{{ number_format($totalCreditCard, 2, ',', '.') }}</td>
            <td style="border: 1px solid #000;">{{ number_format($totalTransfer, 2, ',', '.') }}</td>
            <td style="border: 1px solid #000;">{{ number_format($totalPembayaranLainnya, 2, ',', '.') }}</td>
        </tr>
        <tr style="font-weight: bold; background: #dee2e6;">
            <td colspan="12" style="border: 1px solid #000; text-align: right;">TOTAL BAYAR</td>
            <td style="border: 1px solid #000;">{{ number_format($totalBayar, 2, ',', '.') }}</td>
        </tr>
    @endif
</table>
