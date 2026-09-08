@php
date_default_timezone_set('Asia/Jakarta')
@endphp
<!DOCTYPE html>
<html>

<head>
    <title>miu ticketing system</title>
    <style>
        @media print {
            .ticket-row {
                page-break-after: always;
            }
        }
    </style>
</head>

<body>
    @php
        $transaction = $detail->transaction;
        $paymentLabel = \App\Support\PaymentMethod::displayLabelUpper($transaction->metode ?? null);
        $kasirName = $transaction?->user?->name ?? '-';
        $cardName = trim((string) ($transaction->nama_kartu ?? ''));
        $cardNumber = trim((string) ($transaction->no_kartu ?? ''));
        $bankName = trim((string) ($transaction->bank ?? ''));
    @endphp
    <div class="ticket-row" style="margin-top: 10px;">
        <div class="qr-code" style="max-width:80mm !important;  margin: 0 auto 0 auto; vertical-align: top; border-style: solid;border-width: 1px;">
            <div class="detail" style="font-size: 10pt; line-height: 18px;">
                <span style="display: block; text-align: center; font-weight: 900;">{{ $detail->ticket->name }}</span>
                <span style="display: block; text-align: center;">Rp. {{ number_format($detail->ticket->harga, 0, ',', '.') }}</span>
                <span style="display: block; text-align: center; font-size: 8pt;">Pembayaran: {{ $paymentLabel }}</span>
                <span style="display: block; text-align: center; font-size: 8pt;">Kasir: {{ $kasirName }}</span>
                @if($cardName !== '' || $cardNumber !== '' || $bankName !== '')
                    @if($cardName !== '')
                    <span style="display: block; text-align: center; font-size: 7.5pt;">Nama Kartu: {{ $cardName }}</span>
                    @endif
                    @if($cardNumber !== '')
                    <span style="display: block; text-align: center; font-size: 7.5pt;">No Kartu: {{ $cardNumber }}</span>
                    @endif
                    @if($bankName !== '')
                    <span style="display: block; text-align: center; font-size: 7.5pt;">Bank: {{ $bankName }}</span>
                    @endif
                @endif
            </div>
            <!-- <p style="font-size:8pt;text-align: center;margin-top:5px">RIO WATERPARK " Tiket berlaku satu kali masuk "</p> -->
            <hr style="border-style: dashed;">
            <p style="text-align: center; margin-top: 15px; margin-bottom: 15px">
                @php
                    $qrSvg = QrCode::format('svg')->size(100)->generate($detail->ticket_code);
                    $qrSvgData = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
                @endphp
                <img src="{{ $qrSvgData }}" alt="QR Code" width="100" height="100">
                <br><br>
                <span>{{ $detail->ticket_code }}</span>
            </p>

            <hr style="border-style: dashed;">
            <p style="font-size:9pt;text-align: center;margin-bottom:10px; text-transform: uppercase;">*QRCODE untuk aktifkan dispenser*</p>
        </div>
    </div>
</body>

</html>

