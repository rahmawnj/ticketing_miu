<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>miu ticketing system</title>
    <style>
        :root {
            color-scheme: light;
        }
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: radial-gradient(circle at top, #f8fafc, #eef2f7);
            color: #1f2937;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            max-width: 520px;
            width: 100%;
            background: #fff;
            border-radius: 20px;
            padding: 28px 30px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.12);
            text-align: center;
        }
        h2 {
            margin: 0 0 8px;
            font-size: 22px;
            font-weight: 700;
        }
        p {
            margin: 0 0 16px;
            color: #6b7280;
            font-size: 15px;
            line-height: 1.6;
        }
        .error {
            background: #fee2e2;
            color: #b91c1c;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
            margin-bottom: 16px;
        }
        .pin-inputs {
            display: grid;
            grid-template-columns: repeat(4, 64px);
            gap: 12px;
            justify-content: center;
        }
        .pin-box {
            width: 64px;
            height: 60px;
            border: 2px solid #111827;
            border-radius: 14px;
            text-align: center;
            font-size: 22px;
            font-weight: 600;
            letter-spacing: 2px;
            outline: none;
        }
        .pin-box:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
        }
        .pin-box:disabled {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #94a3b8;
        }
        .hint {
            margin-top: 12px;
            font-size: 13px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Setting Rahasia</h2>
        <p>Masukkan PIN untuk mengakses halaman ini.</p>

        @if (!empty($error))
            <div class="error">{{ $error }}</div>
        @endif

        <form id="pin-form" method="post" action="{{ route('secret-setting.update') }}">
            @csrf
            <input type="hidden" name="key" id="pin-value">
            <div class="pin-inputs">
                <input type="password" inputmode="numeric" maxlength="1" class="pin-box" {{ empty($hasKey) ? 'disabled' : '' }}>
                <input type="password" inputmode="numeric" maxlength="1" class="pin-box" {{ empty($hasKey) ? 'disabled' : '' }}>
                <input type="password" inputmode="numeric" maxlength="1" class="pin-box" {{ empty($hasKey) ? 'disabled' : '' }}>
                <input type="password" inputmode="numeric" maxlength="1" class="pin-box" {{ empty($hasKey) ? 'disabled' : '' }}>
            </div>
        </form>

    </div>

    <script>
        (function () {
            const form = document.getElementById('pin-form');
            const boxes = Array.from(document.querySelectorAll('.pin-box'));
            const hidden = document.getElementById('pin-value');

            function buildPin() {
                return boxes.map((box) => box.value.trim()).join('');
            }

            function submitIfComplete() {
                const pin = buildPin();
                if (pin.length === boxes.length) {
                    hidden.value = pin;
                    form.submit();
                }
            }

            boxes.forEach((box, index) => {
                box.addEventListener('input', (event) => {
                    const value = event.target.value.replace(/[^0-9]/g, '');
                    event.target.value = value.slice(0, 1);

                    if (value && index < boxes.length - 1) {
                        boxes[index + 1].focus();
                    }

                    submitIfComplete();
                });

                box.addEventListener('keydown', (event) => {
                    if (event.key === 'Backspace' && !box.value && index > 0) {
                        boxes[index - 1].focus();
                    }
                });

                box.addEventListener('paste', (event) => {
                    const paste = (event.clipboardData || window.clipboardData).getData('text');
                    if (!paste) {
                        return;
                    }

                    event.preventDefault();
                    const digits = paste.replace(/[^0-9]/g, '').slice(0, boxes.length);
                    digits.split('').forEach((digit, i) => {
                        boxes[i].value = digit;
                    });

                    const nextIndex = Math.min(digits.length, boxes.length - 1);
                    boxes[nextIndex].focus();
                    submitIfComplete();
                });
            });

            if (boxes.length) {
                boxes[0].focus();
            }
        })();
    </script>
</body>
</html>

