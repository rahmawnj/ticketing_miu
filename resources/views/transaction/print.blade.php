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

        html,
        body {
            margin: 0;
            width: 80mm;
        }

        body {
            min-width: 80mm;
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
            display: block;
            text-align: center;
            font-weight: 900;
            font-size: 9pt;
            line-height: 1.15;
            margin: 0 8px 4px;
            word-break: break-word;
        }

        .ticket-row {
            break-inside: avoid;
            page-break-inside: avoid;
            margin-top: 4px;
        }
        .ticket-row:first-child {
            margin-top: 0;
        }

        .ticket-footer {
            margin: 4px 6px 6px;
            text-align: center;
            text-transform: uppercase;
            word-break: break-word;
        }

        .ticket-footer p {
            font-size: 8pt;
            line-height: 1.2;
            margin: 0;
        }

        .ticket-footer p + p {
            margin-top: 2px;
        }

        @if($isPdf ?? false)
        .ticket-row {
            break-after: auto;
            page-break-after: auto;
        }
        @endif

        @media print {
            @if($isPdf ?? false)
            .ticket-row {
                break-after: auto;
                page-break-after: auto;
            }
            @endif
        }
    </style>
</head>

<body>
    @php
        $autoPrint = $autoPrint ?? request()->boolean('auto_print', true);
        $autoRedirect = $autoRedirect ?? request()->boolean('auto_redirect', true);
        $isPdf = $isPdf ?? false;
    @endphp
    @php
        $ticketPrintModeRaw = (string) ($ticketPrintOrientation ?? 'without_summary');
        if ($ticketPrintModeRaw === 'portrait') {
            $ticketPrintModeRaw = 'with_summary';
        } elseif ($ticketPrintModeRaw === 'portrait_with_first_qr') {
            $ticketPrintModeRaw = 'without_summary';
        }
        $ticketPrintMode = in_array($ticketPrintModeRaw, ['with_summary', 'without_summary'], true)
            ? $ticketPrintModeRaw
            : 'without_summary';
        $shouldPrintSummary = $ticketPrintMode === 'with_summary';
        $receiptDetails = $transaction->detail()->with('ticket')->get();
        $groupedSummaryItems = $receiptDetails
            ->groupBy(function ($item) {
                $ticketId = (int) ($item->ticket_id ?? 0);
                $ticketName = trim((string) ($item->ticket->name ?? '-'));
                return $ticketId . '|' . $ticketName;
            })
            ->map(function ($items) {
                $first = $items->first();
                $qty = (int) $items->sum(function ($item) {
                    return max((int) ($item->qty ?? 1), 1);
                });
                $subtotalLine = (float) $items->sum(function ($item) {
                    return ((float) ($item->total ?? 0)) + ((float) ($item->ppn ?? 0));
                });
                $unitPrice = $qty > 0 ? ($subtotalLine / $qty) : $subtotalLine;

                return [
                    'name' => (string) ($first->ticket->name ?? '-'),
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotalLine,
                ];
            })
            ->values();

        $jumlahJenis = (int) $groupedSummaryItems->count();
        $jumlahTicket = (int) $groupedSummaryItems->sum('qty');
        $subtotal = (float) $receiptDetails->sum('total') + (float) $receiptDetails->sum('ppn');
        $discount = ((float) $transaction->discount * $subtotal) / 100;
        $subtotalAfterDiscount = max(0, $subtotal - $discount);
        $paidGross = (float) $transaction->bayar + (float) $transaction->ppn;
        $nonCashMethods = ['debit', 'kredit', 'qris', 'transfer'];
        $metodeLower = strtolower((string) ($transaction->metode ?? ''));
        $displayPaid = in_array($metodeLower, $nonCashMethods, true) ? $subtotalAfterDiscount : $paidGross;
        if ($displayPaid <= 0) {
            $displayPaid = $subtotalAfterDiscount;
        }
        $transactionDateLabel = $transaction->created_at->format('d/m/Y');
        $transactionDateTimeLabel = $transaction->created_at->format('d/m/Y H:i:s');
        $paymentLabel = \App\Support\PaymentMethod::displayLabelUpper($transaction->metode ?? null);
        $kasirName = $transaction->user->name ?? '-';
        $cardName = trim((string) ($transaction->nama_kartu ?? ''));
        $cardNumber = trim((string) ($transaction->no_kartu ?? ''));
        $bankName = trim((string) ($transaction->bank ?? ''));
        $printTickets = $tickets;
        $ticketScanLimit = (int) \App\Models\Setting::valueOf('ticket_scan_limit', 0);
        $ticketScanLimitLabel = $ticketScanLimit > 0 ? ($ticketScanLimit . ' kali') : 'Tidak dibatasi';
        $footerGreeting = trim((string) ($ucapan ?? ''));
        $footerDescription = trim((string) ($deskripsi ?? ''));
        $footerPlaceholders = ['', '-', '--'];
        if (in_array($footerGreeting, $footerPlaceholders, true)) {
            $footerGreeting = '';
        }
        if (in_array($footerDescription, $footerPlaceholders, true)) {
            $footerDescription = '';
        }
    @endphp

    @if($shouldPrintSummary)
    <div class="ticket-row">
        <div class="qr-code ticket-card ticket-portrait" style="max-width:80mm !important; margin: 0 auto 0 auto;">
            <div class="detail" style="font-size: 10pt; line-height: 18px; margin-top: 10px; margin-bottom: 10px;">
                <div style="text-align:center; margin-bottom: 10px;">
                    <div class="brand-title">{{ $name }}</div>
                    @if(!empty($logo))
                    <img src="{{ $logo }}" width="90" alt="The Logo" class="brand-image" style="opacity: .9; margin-bottom: 6px;">
                    @endif
                    <div style="margin: 6px 10px;"><hr style="border-style: dashed;"></div>
                    <div style="font-weight: 900; font-size: 10pt;">{{ $transaction->ticket_code }}</div>
                    <div style="font-size: 9pt;">{{ $transactionDateTimeLabel }}</div>
                </div>
                <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <span>Jumlah Jenis : </span>
                    <span>{{ $jumlahJenis }}</span>
                </div>
                <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <span>Jumlah Ticket : </span>
                    <span>{{ $jumlahTicket }}</span>
                </div>
                <div style="margin: 6px 10px;">
                    <div style="font-weight: 900;">Rincian Pembelian:</div>
                    @forelse($groupedSummaryItems as $item)
                    <div style="margin-top: 2px;">
                        <div style="font-weight: 700;">{{ $item['name'] }} x {{ $item['qty'] }}</div>
                        <div style="display: flex; justify-content: space-between; font-size: 9pt;">
                            <span>{{ $item['qty'] }} x Rp. {{ number_format($item['unit_price'], 0, ',', '.') }}</span>
                            <span>Rp. {{ number_format($item['subtotal'], 0, ',', '.') }}</span>
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
                {{-- <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                    <span>PBJT {{ $ppn . '%' }} : </span>
                <span>Rp. {{ number_format($transaction->ppn, 0, ',', '.') }}</span>
            </div> --}}
            <div style="display: flex;font-weight: 900; justify-content: space-between; margin-left: 10px; margin-right: 10px;">
                <span>Bayar : </span>
                <span>Rp. {{ number_format($displayPaid, 0, ',', '.') }}</span>
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
                <span>Kasir : </span>
                <span>{{ $kasirName }}</span>
            </div>
            <hr style="border-style: dashed;">
            @if($footerGreeting !== '' || $footerDescription !== '')
            <div class="ticket-footer">
                @if($footerGreeting !== '')
                <p>{!! nl2br(e($footerGreeting)) !!}</p>
                @endif
                @if($footerDescription !== '')
                <p>{!! nl2br(e($footerDescription)) !!}</p>
                @endif
            </div>
            @endif
        </div>
    </div>
    </div>
    @endif

    @foreach($printTickets as $ticketIndex => $detail)
    <div class="ticket-row">
        <div class="qr-code ticket-card ticket-portrait" style="margin: 0 auto 0 auto;">
            <div class="detail" style="font-size: 10pt; line-height: 18px;">
                <span class="item-title">{{ $detail["name"] }}</span>
                @if($print == 0)
                <span style="display: block; text-align: center;">Rp. {{ $detail["harga"] }}</span>
                @endif
                <span style="display: block; text-align: center; font-size: 8pt;">Tanggal: {{ $transactionDateLabel }}</span>
                <span style="display: block; text-align: center; font-size: 8pt;">Pembayaran: {{ $paymentLabel }}</span>
                <span style="display: block; text-align: center; font-size: 8pt;">Kasir: {{ $kasirName }}</span>
                <span style="display: block; text-align: center; font-size: 8pt;">Batas Scan: {{ $ticketScanLimitLabel }}</span>
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
            <hr style="border-style: dashed;">
            <p style="text-align: center; margin-top: 8px; margin-bottom: 8px">
                @php
                    $qrSvg = QrCode::format('svg')->size(110)->generate($detail["ticket_code"]);
                    $qrSvgData = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
                @endphp
                <img src="{{ $qrSvgData }}" alt="QR Code" width="110" height="110">
                <br>
                <span>{{ $detail["ticket_code"] }}</span>
            </p>

            <hr style="border-style: dashed;">
            @if($footerGreeting !== '' || $footerDescription !== '')
            <div class="ticket-footer">
                @if($footerGreeting !== '')
                <p>{!! nl2br(e($footerGreeting)) !!}</p>
                @endif
                @if($footerDescription !== '')
                <p>{!! nl2br(e($footerDescription)) !!}</p>
                @endif
            </div>
            @endif
        </div>
    </div>
    @endforeach

    @if(!$isPdf && $autoPrint)
        <script src="{{ asset('/js/jquery.min.js') }}"></script>

        <script>
            $(document).ready(function() {
                let hasRedirected = false;
                const shouldRedirect = {{ $autoRedirect ? 'true' : 'false' }};
                const backToTransaction = function() {
                    if (!shouldRedirect || hasRedirected) return;
                    hasRedirected = true;
                    document.location.href = "{{ route('transactions.create') }}";
                };

                if (shouldRedirect) {
                    window.onafterprint = backToTransaction;
                    setTimeout(backToTransaction, 10000);
                }

                window.print();
            })
        </script>
    @endif
</body>

</html>

