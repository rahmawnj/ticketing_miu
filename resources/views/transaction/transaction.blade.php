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
    $receiptDetails = $transaction->detail()->with('ticket')->get();
    $jumlahJenis = $receiptDetails->count();
    $jumlahTicket = (int) $receiptDetails->sum('qty');
    $subtotal = (float) $receiptDetails->sum('total') + (float) $receiptDetails->sum('ppn');
    $discount = ((float) $transaction->discount * $subtotal) / 100;
        $paymentLabel = \App\Support\PaymentMethod::displayLabelUpper($transaction->metode ?? null);
        $kasirName = $transaction->user->name ?? '-';
        $cardName = trim((string) ($transaction->nama_kartu ?? ''));
        $cardNumber = trim((string) ($transaction->no_kartu ?? ''));
        $bankName = trim((string) ($transaction->bank ?? ''));
    @endphp
    <div class="ticket-row" style="margin-top: 10px;">
        <div class="qr-code" style="max-width:80mm !important;  margin: 0 auto 0 auto; vertical-align: top; border-style: solid;border-width: 1px;">
            <div class="detail" style="font-size: 10pt; line-height: 18px; margin-top: 10px; margin-bottom: 10px;">
                <span style="display: flex; text-align: center; font-weight: 900; font-size: 10pt; margin-bottom: 10px; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('/images/rio.png'))) }}" width="65" alt="The Logo" class="brand-image" style="opacity: .8; margin-top: -15px !important;">

                    <span>
                        <span style="display: block;">{{ $transaction->ticket_code }}</span>
                        <span>{{ $transaction->created_at->format('d/m/Y H:i:s') }}</span>
                    </span>
                </span>
                <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <span>Jumlah Ticket : </span>
                    <span>{{ $jumlahTicket }}</span>
                </div>
                <div style="margin: 6px 10px;">
                    <div style="font-weight: 900;">Rincian Pembelian:</div>
                    @forelse($receiptDetails as $item)
                    @php
                    $lineQty = max((int) $item->qty, 1);
                    $lineSubtotal = (float) $item->total + (float) $item->ppn;
                    $lineUnitPrice = $lineSubtotal / $lineQty;
                    @endphp
                    <div style="margin-top: 2px;">
                        <div style="font-weight: 700;">{{ $item->ticket->name ?? '-' }}</div>
                        <div style="display: flex; justify-content: space-between; font-size: 9pt;">
                            <span>{{ $lineQty }} x Rp. {{ number_format($lineUnitPrice, 0, ',', '.') }}</span>
                            <span>Rp. {{ number_format($lineSubtotal, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    @empty
                    <div style="font-size: 9pt;">-</div>
                    @endforelse
                </div>
                <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <span>Total Harga : </span>
                    <span>Rp. {{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <span>Discount : </span>
                    <span>Rp. {{ number_format($discount, 0, ',', '.') }}</span>
                </div>
                <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <span>Bayar : </span>
                    <span>Rp. {{ number_format($transaction->bayar, 0, ',', '.') }}</span>
                </div>
                <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <span>Pembayaran : </span>
                    <span>{{ $paymentLabel }}</span>
                </div>
                @if($cardName !== '' || $cardNumber !== '' || $bankName !== '')
                <div style="font-size: 8.5pt; margin-left: 10px; margin-right: 10px;">
                    @if($cardName !== '')
                    <div style="display: flex; justify-content: space-between;">
                        <span>Nama Kartu</span>
                        <span>{{ $cardName }}</span>
                    </div>
                    @endif
                    @if($cardNumber !== '')
                    <div style="display: flex; justify-content: space-between;">
                        <span>No Kartu</span>
                        <span>{{ $cardNumber }}</span>
                    </div>
                    @endif
                    @if($bankName !== '')
                    <div style="display: flex; justify-content: space-between;">
                        <span>Bank</span>
                        <span>{{ $bankName }}</span>
                    </div>
                    @endif
                </div>
                @endif
                <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <span>Kembali : </span>
                    <span>Rp. {{ number_format($transaction->kembali, 0, ',', '.') }}</span>
                </div>
                <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <span>Kasir : </span>
                    <span>{{ $kasirName }}</span>
                </div>
                <hr style="border-style: dashed;">
                <p style="font-size:9pt;text-align: center;margin-bottom:10px; text-transform: uppercase;">*terima kasih*</p>
            </div>
        </div>
    </div>
</body>

</html>

