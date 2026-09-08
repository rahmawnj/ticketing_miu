<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>miu ticketing system</title>

    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body {
            margin: 0;
            padding: 6px 0;
            font-family: "Times New Roman", serif;
        }
        .container {
            width: 100%;
            max-width: 80mm !important;
            border: 1px solid black;
            text-align: center;
            margin: 0 auto;
        }
        .invoice-title {
            margin: 0;
            font-size: 11px;
            letter-spacing: 0.3px;
        }
        .invoice-table-wrap {
            display: flex;
            justify-content: center;
            font-size: 9px;
            padding: 8px 10px;
        }
        .invoice-meta {
            text-align: center;
            font-size: 9px;
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    @php
        $qtyItem = max((int) ($transaction->amount ?? 1), 1);
        $adminFee = max(0, (float) ($transaction->admin_fee ?? 0));
        $subtotal = (float) ($transaction->bayar ?? 0) + (float) ($transaction->ppn ?? 0);
        $totalBayar = $subtotal + $adminFee;
        $hargaSatuan = $subtotal / $qtyItem;
        $membershipName = filled($member->membership->name ?? null) ? $member->membership->name : 'Membership';
    @endphp
    <div class="container">
        <div style="margin-bottom: 10px; text-transform: uppercase; font-weight: bold;">
            <h4 class="invoice-title">Invoice Membership</h4>
        </div>

        <div class="invoice-table-wrap">
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="text-align: left;">
                    <td width="40%">Jenis</td>
                    <td width="60%">
                        @if($adminFee > 0)
                            Perpanjangan Baru
                        @elseif(($transaction->transaction_type ?? 'registration') === 'renewal')
                            Perpanjangan
                        @else
                            Registrasi
                        @endif
                    </td>
                </tr>
                <tr style="text-align: left;">
                    <td>No Invoice</td>
                    <td>{{ $transaction->ticket_code ?? $member->qr_code }}</td>
                </tr>
                <tr style="text-align: left;">
                    <td>Tanggal</td>
                    <td>{{ $transaction?->created_at?->format('d/m/Y H:i:s') ?? now('Asia/Jakarta')->format('d/m/Y H:i:s') }}</td>
                </tr>
                <tr style="text-align: left;">
                    <td>Nama</td>
                    <td>{{ filled($member->nama) ? $member->nama : '-' }}</td>
                </tr>
                <tr style="text-align: left;">
                    <td>NIK</td>
                    <td>{{ filled($member->no_ktp) ? $member->no_ktp : '-' }}</td>
                </tr>
                <tr style="text-align: left;">
                    <td>No HP</td>
                    <td>{{ filled($member->no_hp) ? $member->no_hp : '-' }}</td>
                </tr>
                <tr style="text-align: left;">
                    <td>Kasir</td>
                    <td>{{ filled($cashierName ?? null) ? $cashierName : '-' }}</td>
                </tr>
                <tr style="text-align: left;">
                    <td>Membership</td>
                    <td>{{ filled($member->membership->name ?? null) ? $member->membership->name : '-' }}</td>
                </tr>
                <tr style="text-align: left;">
                    <td>Masa Aktif</td>
                    <td>{{ filled($member->tgl_register) ? $member->tgl_register : '-' }} - {{ filled($member->tgl_expired) ? $member->tgl_expired : '-' }}</td>
                </tr>
                @if($member->membership && $member->membership->max_access !== null)
                <tr style="text-align: left;">
                    <td>Max Access</td>
                    <td>{{ (int)$member->membership->max_access === 0 ? 'Unlimited' : $member->membership->max_access . ' kali' }}</td>
                </tr>
                @endif
                @if($member->childs && $member->childs->count() > 0)
                <tr style="text-align: left;">
                    <td>Submember</td>
                    <td>{{ $member->childs->pluck('nama')->filter()->implode(', ') }}</td>
                </tr>
                @endif
                <tr style="text-align: left;">
                    <td>Harga</td>
                    <td>{{ "Rp. " .  number_format(($member->membership->price ?? 0), 0, ',', '.') }}</td>
                </tr>
                <tr style="text-align: left;">
                    <td>Jumlah Jenis</td>
                    <td>1</td>
                </tr>
                <tr style="text-align: left;">
                    <td>Jumlah Item</td>
                    <td>{{ $qtyItem }}</td>
                </tr>
                <tr style="text-align: left;">
                    <td>Rincian Item</td>
                    <td>{{ $membershipName }}</td>
                </tr>
                <tr style="text-align: left;">
                    <td>Subtotal</td>
                    <td>Rp. {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
                @if($adminFee > 0)
                <tr style="text-align: left;">
                    <td>Biaya Admin</td>
                    <td>Rp. {{ number_format($adminFee, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr style="text-align: left;">
                    <td>Metode</td>
                    <td>{{ \App\Support\PaymentMethod::displayLabelUpper($transaction->metode ?? null) }}</td>
                </tr>
                <tr style="text-align: left;">
                    <td>Total Bayar</td>
                    <td>Rp. {{ number_format($totalBayar, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
        <div style="border-top: 1px dashed #000; margin: 4px 12px 0;"></div>
        <div class="invoice-meta" style="margin: 6px 12px 0;">
            {!! nl2br(e($ucapan ?? 'Terima Kasih')) !!}
        </div>
        @if(!empty($deskripsi))
        <div class="invoice-meta" style="margin: 2px 12px 10px;">
            {!! nl2br(e($deskripsi)) !!}
        </div>
        @endif
    </div>


    <script>
        setTimeout(function() {
            window.print();
        }, 1000)
    </script>
</body>

</html>

