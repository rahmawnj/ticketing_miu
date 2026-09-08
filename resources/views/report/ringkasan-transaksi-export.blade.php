<table>
    <tr>
        <th colspan="8" style="font-weight: bold; text-align: center;">REPORT RINGKASAN TRANSAKSI</th>
    </tr>
    <tr>
        <th colspan="8" style="font-weight: bold; text-align: center;">
            PERIODE {{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}
        </th>
    </tr>
    <tr><td colspan="8"></td></tr>

    <tr>
        <th style="border: 1px solid #000;">Tanggal</th>
        <th style="border: 1px solid #000;">Member</th>
        <th style="border: 1px solid #000;">Ticket</th>
        <th style="border: 1px solid #000;">Lain-lain</th>
        <th style="border: 1px solid #000;">Total</th>
        <th style="border: 1px solid #000;">DPP</th>
        <th style="border: 1px solid #000;">PBJT</th>
        <th style="border: 1px solid #000;">Biaya Admin</th>
    </tr>

    @forelse($rows as $row)
    <tr>
        <td style="border: 1px solid #000;">{{ $row['tanggal'] }}</td>
        <td style="border: 1px solid #000;">{{ number_format($row['member'], 0, ',', '.') }}</td>
        <td style="border: 1px solid #000;">{{ number_format($row['ticket'], 0, ',', '.') }}</td>
        <td style="border: 1px solid #000;">{{ number_format($row['lain_lain'], 0, ',', '.') }}</td>
        <td style="border: 1px solid #000;">{{ number_format($row['total'], 0, ',', '.') }}</td>
        <td style="border: 1px solid #000;">{{ number_format($row['dpp'], 0, ',', '.') }}</td>
        <td style="border: 1px solid #000;">{{ number_format($row['ppn'], 0, ',', '.') }}</td>
        <td style="border: 1px solid #000;">{{ number_format($row['admin_fee'] ?? 0, 0, ',', '.') }}</td>
    </tr>
    @empty
    <tr>
        <td colspan="8" style="border: 1px solid #000;">Tidak ada data transaksi pada rentang tanggal ini.</td>
    </tr>
    @endforelse

    <tr style="font-weight: bold; background: #f2f2f2;">
        <td style="border: 1px solid #000;">TOTAL</td>
        <td style="border: 1px solid #000;">{{ number_format($footer['member'], 0, ',', '.') }}</td>
        <td style="border: 1px solid #000;">{{ number_format($footer['ticket'], 0, ',', '.') }}</td>
        <td style="border: 1px solid #000;">{{ number_format($footer['lain_lain'], 0, ',', '.') }}</td>
        <td style="border: 1px solid #000;">{{ number_format($footer['total'], 0, ',', '.') }}</td>
        <td style="border: 1px solid #000;">{{ number_format($footer['dpp'], 0, ',', '.') }}</td>
        <td style="border: 1px solid #000;">{{ number_format($footer['ppn'], 0, ',', '.') }}</td>
        <td style="border: 1px solid #000;">{{ number_format($footer['admin_fee'] ?? 0, 0, ',', '.') }}</td>
    </tr>
</table>
