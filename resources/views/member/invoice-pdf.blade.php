<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>miu ticketing system</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; margin: 0; }
        .page { width: 80mm; max-width: 80mm; margin: 0 auto; border: 1px solid #0f2a3c; padding: 8px; }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .brand { display: flex; align-items: center; gap: 10px; }
        .brand img { height: 36px; }
        .brand-name { font-weight: bold; font-size: 12px; line-height: 1.15; word-break: break-word; max-width: 42mm; }
        .title { text-transform: uppercase; font-weight: bold; font-size: 14px; text-align: right; color: #0f2a3c; }
        .muted { color: #444; font-size: 11px; }
        .section-title { font-weight: bold; text-transform: uppercase; margin: 10px 0 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 0; vertical-align: top; }
        .label { width: 35%; color: #333; }
        .value { width: 65%; }
        .divider { border-top: 1px dashed #0f2a3c; margin: 8px 0; }
        .subtable th, .subtable td { border: 1px solid #ccc; padding: 4px; font-size: 11px; }
        .subtable th { background: #eef3f6; color: #0f2a3c; text-align: left; }
        .footer-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
        .footer-left { text-align: left; vertical-align: top; }
        .footer-right { text-align: right; vertical-align: top; }
        @media print {
            body { margin: 0; }
            .page { border: 1px solid #0f2a3c; }
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
    <div class="page">
        <div class="header">
            <div class="brand">
                <table style="border-collapse: collapse; width: auto; display: inline-table;">
                    <tr>
                        @if(!empty($logo_data))
                        <td style="vertical-align: middle; padding-right: 8px;">
                            <img src="{{ $logo_data }}" alt="Logo" style="height:36px;">
                        </td>
                        @endif
                        <td style="vertical-align: middle;">
                            <div class="brand-name">{{ $app_name ?? 'Ticketing App' }}</div>
                            <div class="muted">Invoice Membership</div>
                        </td>
                    </tr>
                </table>
            </div>
            <div>
                <div class="title">{{ $type }}</div>
                <div class="muted">{{ $date }}</div>
            </div>
        </div>

        <div class="divider"></div>

        <div class="section-title">Detail Member</div>
        <table>
            <tr>
                <td class="label">No Invoice</td>
                <td class="value">{{ $invoice_code }}</td>
            </tr>
            <tr>
                <td class="label">Nama</td>
                <td class="value">{{ filled($member->nama) ? $member->nama : '-' }}</td>
            </tr>
            <tr>
                <td class="label">NIK</td>
                <td class="value">{{ filled($member->no_ktp) ? $member->no_ktp : '-' }}</td>
            </tr>
            <tr>
                <td class="label">No HP</td>
                <td class="value">{{ filled($member->no_hp) ? $member->no_hp : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Kasir</td>
                <td class="value">{{ filled($cashier_name ?? null) ? $cashier_name : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Membership</td>
                <td class="value">{{ filled($member->membership->name ?? null) ? $member->membership->name : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Masa Aktif</td>
                <td class="value">{{ filled($member->tgl_register) ? $member->tgl_register : '-' }} - {{ filled($member->tgl_expired) ? $member->tgl_expired : '-' }}</td>
            </tr>
            @if($member->membership && $member->membership->max_access !== null)
            <tr>
                <td class="label">Max Access</td>
                <td class="value">{{ (int)$member->membership->max_access === 0 ? 'Unlimited' : $member->membership->max_access . ' kali' }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Harga</td>
                <td class="value">{{ $price }}</td>
            </tr>
            <tr>
                <td class="label">Jumlah Jenis</td>
                <td class="value">1</td>
            </tr>
            <tr>
                <td class="label">Jumlah Item</td>
                <td class="value">{{ $qtyItem }}</td>
            </tr>
            <tr>
                <td class="label">Rincian Item</td>
                <td class="value">{{ $membershipName }}</td>
            </tr>
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">Rp. {{ number_format($subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($adminFee > 0)
            <tr>
                <td class="label">Biaya Admin</td>
                <td class="value">Rp. {{ number_format($adminFee, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Metode</td>
                <td class="value">{{ \App\Support\PaymentMethod::displayLabelUpper($transaction->metode ?? null) }}</td>
            </tr>
            <tr>
                <td class="label">Total Bayar</td>
                <td class="value">Rp. {{ number_format($totalBayar, 0, ',', '.') }}</td>
            </tr>
        </table>

        @if($member->childs && $member->childs->count() > 0)
        <div class="section-title">Submember</div>
        <table class="subtable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                </tr>
            </thead>
            <tbody>
                @foreach($member->childs as $i => $child)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $child->nama }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="divider"></div>
        <table class="footer-table">
            <tr>
                <td class="footer-left">{!! nl2br(e($ucapan ?? 'Terima Kasih')) !!}</td>
                <td class="footer-right">{!! nl2br(e($deskripsi ?? '')) !!}</td>
            </tr>
        </table>
    </div>

    @if(!empty($auto_print))
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 250);
        });
    </script>
    @endif
</body>

</html>

