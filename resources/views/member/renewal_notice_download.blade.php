<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>miu ticketing system</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111;
            margin: 24px;
            line-height: 1.55;
        }
        .notice {
            max-width: 760px;
            margin: 0 auto 28px auto;
        }
        .notice + .notice {
            page-break-before: always;
        }
        .brand {
            text-align: center;
            margin-bottom: 20px;
        }
        .brand img {
            max-height: 72px;
            margin-bottom: 8px;
        }
        .brand h2 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 0.3px;
        }
        .member-name {
            margin: 18px 0 14px 0;
            font-size: 18px;
        }
        .section {
            margin: 10px 0;
        }
        .meta {
            margin: 12px 0 14px 0;
        }
        .meta .row {
            display: flex;
            gap: 8px;
            margin: 4px 0;
        }
        .meta .label {
            min-width: 145px;
            font-weight: 700;
        }
        .emphasis {
            font-weight: 700;
        }
        .bank {
            margin-top: 14px;
            font-weight: 700;
        }
        .footer {
            margin-top: 22px;
        }
    </style>
</head>
<body>
@foreach($notices as $notice)
    @php
        $noteBlock = '';
        if (!empty($notice['is_renewal_baru']) && (float) ($notice['admin_fee'] ?? 0) > 0) {
            $noteBlock = 'Catatan: Perpanjangan baru (termasuk biaya admin Rp ' . number_format((float) ($notice['admin_fee'] ?? 0), 0, ',', '.') . ')';
        }

        $template = (string) ($noticeContent['body_template'] ?? '');
        $renderedBody = strtr($template, [
            ':member_name' => (string) ($notice['member_name'] ?? '-'),
            ':membership_name' => (string) ($notice['membership_name'] ?? '-'),
            ':expired_date' => (string) ($notice['expired_date'] ?? '-'),
            ':due_date' => (string) ($notice['due_date'] ?? '-'),
            ':base_price' => 'Rp ' . number_format((float) ($notice['base_price'] ?? 0), 0, ',', '.'),
            ':ppn_amount' => 'Rp ' . number_format((float) ($notice['ppn_amount'] ?? 0), 0, ',', '.'),
            ':total_price' => 'Rp ' . number_format((float) ($notice['total_price'] ?? 0), 0, ',', '.'),
            ':admin_fee' => 'Rp ' . number_format((float) ($notice['admin_fee'] ?? 0), 0, ',', '.'),
            ':club_name' => (string) ($noticeContent['club_name'] ?? '-'),
            ':bank_account' => (string) ($noticeContent['bank_account'] ?? '-'),
            ':admin_phone' => (string) ($noticeContent['admin_phone'] ?? '-'),
            ':note_block' => $noteBlock,
        ]);
    @endphp
    <div class="notice">
        <div class="brand">
            @if(!empty($logoData))
                <img src="{{ $logoData }}" alt="Logo">
            @endif
            <h2>{{ $noticeContent['club_name'] }}</h2>
        </div>

        <div class="section">{!! nl2br(e($renderedBody)) !!}</div>
    </div>
@endforeach
@if(!empty($autoPrint))
    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
@endif
</body>
</html>

