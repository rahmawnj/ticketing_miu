@php
date_default_timezone_set('Asia/Jakarta')
@endphp
<!DOCTYPE html>
<html>

<head>
    <title>miu ticketing system</title>
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            size: 80mm auto;
            margin: 0;
        }

        body {
            margin: 0;
        }

        .ticket-card {
            margin: 0 auto;
            vertical-align: top;
            border: 0;
            background: #fff;
        }

        .ticket-card.ticket-portrait {
            max-width: 80mm !important;
        }

        .brand-title {
            font-weight: 900;
            font-size: 10pt;
            line-height: 1.15;
            text-transform: uppercase;
            margin: 0 8px 6px;
            text-align: center;
            word-break: break-word;
        }
        .item-title {
            font-weight: 900;
            font-size: 9pt;
            line-height: 1.15;
            text-align: center;
            margin: 0 8px 4px;
            word-break: break-word;
        }

        @media print {
            .ticket-row {
                break-after: page;
                page-break-after: always;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .ticket-row:last-child {
                break-after: auto;
                page-break-after: auto;
            }
        }
    </style>
</head>

<body>
    @php
        $autoPrint = $autoPrint ?? request()->boolean('auto_print', true);
        $isPdf = $isPdf ?? false;
    @endphp
    @php
        $qty = max((int) ($penyewaan->qty ?? 0), 1);
        $lineSubtotal = (float) ($penyewaan->jumlah ?? 0);
        $lineUnitPrice = $lineSubtotal / $qty;
        $paymentLabel = \App\Support\PaymentMethod::displayLabelUpper($penyewaan->metode ?? null);
        $methodNormalized = \App\Support\PaymentMethod::normalize($penyewaan->metode ?? null);
        $kasirName = $penyewaan->user->name ?? ($transaction->user->name ?? '-');
        $cardName = trim((string) ($transaction->nama_kartu ?? $penyewaan->nama_kartu ?? ''));
        $cardNumber = trim((string) ($transaction->no_kartu ?? $penyewaan->no_kartu ?? ''));
        $bankName = trim((string) ($transaction->bank ?? $penyewaan->bank ?? ''));
        $isCardMethod = in_array($methodNormalized, ['debit', 'kredit'], true);
        $cardNameDisplay = $isCardMethod ? ($cardName !== '' ? $cardName : '-') : '';
        $cardNumberDisplay = $isCardMethod ? ($cardNumber !== '' ? $cardNumber : '-') : '';
        $bankDisplay = $isCardMethod ? ($bankName !== '' ? $bankName : '-') : '';
    @endphp
    <div class="ticket-row" style="margin-top: 4px;">
        <div class="qr-code ticket-card ticket-portrait" style="margin: 0 auto 0 auto;">
            <div style="font-size: 9.2pt; line-height: 16.5px; margin-top: 10px; margin-bottom: 10px;">
                <div style="text-align:center; margin-bottom: 10px;">
                    <div class="brand-title">{{ $name }}</div>
                    @if($use == 1)
                    <img src="{{ $logo }}" width="90" alt="The Logo" class="brand-image" style="opacity: .9; margin-bottom: 6px;">
                    @endif
                    <div style="margin: 6px 10px;"><hr style="border-style: dashed;"></div>
                    <div class="item-title">{{ $penyewaan->sewa->name }}</div>
                    <div style="font-size: 8.2pt;">{{ date('d/m/Y H:i:s', strtotime($penyewaan->created_at)) }}</div>
                </div>

                <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <span>Jumlah Jenis : </span>
                    <span>1</span>
                </div>
                <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <span>Jumlah Item : </span>
                    <span>{{ $qty }}</span>
                </div>
                <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <span>No Transaksi : </span>
                    <span>{{ $transaction->ticket_code ?? '-' }}</span>
                </div>
                <div style="margin: 6px 10px;">
                    <div style="font-weight: 900;">Rincian Pembelian:</div>
                    <div style="margin-top: 2px;">
                        <div style="font-weight: 700;">{{ $penyewaan->sewa->name ?? '-' }}</div>
                        <div style="display: flex; justify-content: space-between; font-size: 8.2pt;">
                            <span>{{ $qty }} x Rp. {{ number_format($lineUnitPrice, 0, ',', '.') }}</span>
                            <span>Rp. {{ number_format($lineSubtotal, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
                <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <span>Subtotal : </span>
                    <span>Rp. {{ number_format($lineSubtotal, 0 , ',', '.') }}</span>
                </div>
                <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <span>Total Bayar : </span>
                    <span>Rp. {{ number_format($penyewaan->jumlah, 0 , ',', '.') }}</span>
                </div>
                <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <span>Pembayaran : </span>
                    <span>{{ $paymentLabel }}</span>
                </div>
                @if($isCardMethod)
                <div style="font-size: 8.5pt; margin-left: 10px; margin-right: 10px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Nama Kartu</span>
                        <span>{{ $cardNameDisplay }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>No Kartu</span>
                        <span>{{ $cardNumberDisplay }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Bank</span>
                        <span>{{ $bankDisplay }}</span>
                    </div>
                </div>
                @endif
                <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <span>Kasir : </span>
                    <span>{{ $kasirName }}</span>
                </div>
                <br>
                @if(!empty($penyewaan->keterangan))
                <p style="font-size:9.2pt;margin-left:10px;margin-top:5px;margin-bottom:0px; font-weight: bold;">Keterangan</p>
                <p style="font-size:9.2pt;margin-left:10px;margin-top:2px;margin-bottom:0px">{{ $penyewaan->keterangan }}</p>
                @endif
                <div style="margin: 24px 10px 4px 10px;">
                    <hr style="border-style: dashed;">
                </div>
                <br>
                <p style="font-size:8.2pt;text-align: center;margin-top:5px; text-transform: uppercase;">{!! nl2br(e($ucapan)) !!}</p>
                <p style="font-size:8.2pt;text-align: center;margin-bottom:10px; text-transform: uppercase;">{!! nl2br(e($deskripsi)) !!}</p>
            </div>
        </div>
    </div>

    @if(!$isPdf && $autoPrint)
        <script src="{{ asset('js/jquery.min.js') }}"></script>
        <script>
            $(document).ready(function() {
                window.print()
            })
        </script>
    @endif
</body>

</html>

