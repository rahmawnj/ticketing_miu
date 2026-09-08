<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>miu ticketing system</title>

    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; width: 80mm; }
        body { font-family: Arial, sans-serif; color: #0f2a3c; }
        .card {
            width: 79mm;
            min-height: 45.5mm;
            border: 1px solid #0f2a3c;
            padding: 3.1mm 2.8mm 1mm 2.8mm;
            margin: 0.5mm auto 0;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.6mm;
        }
        .brand-wrap {
            display: flex;
            align-items: center;
            gap: 2mm;
            min-width: 0;
        }
        .brand {
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
        }
        .brand-logo {
            max-height: 8mm;
            max-width: 28mm;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }
        .member-id {
            font-size: 10px;
            color: #2a4b5f;
            text-align: right;
        }
        .content {
            display: grid;
            grid-template-columns: 1fr 22mm;
            gap: 2.2mm;
            align-items: center;
        }
        .label { font-size: 9px; color: #4b5b66; }
        .value { font-size: 10px; font-weight: 600; margin-bottom: 1.2mm; }
        .qr {
            width: 22mm;
            height: 22mm;
            border: 1px solid #c9d6df;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .footer {
            font-size: 8px;
            color: #4b5b66;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-top: 0.5mm;
        }
        .member-code-big {
            font-size: 12px;
            line-height: 1;
            font-weight: 700;
            color: #0f2a3c;
            letter-spacing: 0.3px;
        }
        .separator {
            margin-top: 0mm;
            width: 100%;
            border-top: 1px dashed #4b5b66;
            height: 0;
        }
        .short-desc {
            margin-top: 0.45mm;
            font-size: 8px;
            color: #4b5b66;
            line-height: 1.1;
            text-align: center;
            text-transform: uppercase;
            word-break: break-word;
            min-height: 3.2mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .app-name-bottom {
            margin-top: 0.4mm;
            font-size: 8.5px;
            font-weight: 700;
            color: #2a4b5f;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        @media print {
            html, body {
                width: 80mm !important;
                margin: 0 !important;
                overflow: visible !important;
            }
            .card {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    @php
        $membershipCode = $member->membership->code ?? '-';
        $showLogo = !empty($logoData ?? null);
        $logoUrl = $logoData ?? null;
        $shortDesc = trim((string) ($setting->deskripsi ?? ''));
    @endphp

    <div class="card">
        <div class="header">
            <div class="brand-wrap">
                @if ($showLogo)
                    <img src="{{ $logoUrl }}" alt="Logo" class="brand-logo">
                @endif
            </div>
            <div class="member-id">{{ $member->display_member_code ?? '-' }}</div>
        </div>

        <div class="content">
            <div>
                <div class="label">Nama Member</div>
                <div class="value">{{ $member->nama }}</div>

                <div class="label">Masa Aktif</div>
                <div class="value">
                    {{ \Carbon\Carbon::parse($member->tgl_register)->format('d/m/Y') }}
                    -
                    {{ \Carbon\Carbon::parse($member->tgl_expired)->format('d/m/Y') }}
                </div>

                <div class="label">Membership</div>
                <div class="value">{{ $member->membership->name ?? '-' }}</div>
            </div>

            <div class="qr">
                @php
                    $qrSvg = QrCode::format('svg')->size(100)->margin(0)->errorCorrection('H')->generate($member->qr_code);
                    $qrSvgData = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
                @endphp
                <img src="{{ $qrSvgData }}" alt="QR Code" style="width:100%; height:100%;">
            </div>
        </div>

        <div class="footer">
            <span class="member-code-big">{{ $membershipCode }}</span>
        </div>

        <div class="separator"></div>
        @if ($shortDesc !== '')
            <div class="short-desc">{!! nl2br(e($shortDesc)) !!}</div>
        @endif
        <div class="app-name-bottom">{{ $setting->name ?? 'MIU' }}</div>
    </div>


    <script>
        setTimeout(function() {
            window.focus();
            window.print();
        }, 500);

        window.onafterprint = function() {
            try {
                window.close();
            } catch (e) {}
        };
    </script>
</body>

</html>


