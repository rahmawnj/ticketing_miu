<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>miu ticketing system</title>
    <style>
        :root {
            color-scheme: light;
        }
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: linear-gradient(160deg, #f7f9fc 0%, #eef2f7 100%);
            color: #1f2937;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            max-width: 640px;
            width: 100%;
            background: #fff;
            border-radius: 26px;
            padding: 32px 34px;
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.12);
            text-align: center;
        }
        h2 {
            margin: 0 0 8px;
            font-size: 24px;
            font-weight: 700;
        }
        .subtitle {
            margin: 0 0 26px;
            color: #6b7280;
            font-size: 15px;
            line-height: 1.6;
        }
        .toggle-row {
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .toggle-label {
            font-weight: 600;
            font-size: 15px;
        }
        .toggle-label.active {
            color: #0f9d58;
        }
        .toggle-label.inactive {
            color: #dc2626;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 68px;
            height: 34px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background-color: #d1d5db;
            transition: 0.3s ease;
            border-radius: 999px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            top: 4px;
            background-color: #fff;
            transition: 0.3s ease;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25);
        }
        input:checked + .slider {
            background-color: #22c55e;
        }
        input:checked + .slider:before {
            transform: translateX(34px);
        }
        .status {
            margin-top: 18px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            align-items: center;
        }
        .status-title {
            font-size: 16px;
            font-weight: 700;
        }
        .status-title.active {
            color: #15803d;
        }
        .status-title.inactive {
            color: #dc2626;
        }
        .status-subtitle {
            color: #6b7280;
            font-size: 14px;
        }
        .status-note {
            margin-top: 10px;
            color: #94a3b8;
            font-size: 13px;
        }
        .status-error {
            margin-top: 12px;
            font-size: 13px;
            color: #b91c1c;
            background: #fee2e2;
            padding: 8px 10px;
            border-radius: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Setting Rahasia</h2>
        <p class="subtitle">Halaman ini mengatur status website. Pilih dengan jelas: aktif atau nonaktif.</p>

        <div class="toggle-row">
            <span class="toggle-label inactive">Nonaktif</span>
            <label class="switch">
                <input type="checkbox" id="website-status-toggle" {{ $isActive ? 'checked' : '' }}>
                <span class="slider"></span>
            </label>
            <span class="toggle-label active">Aktif</span>
        </div>

        <div class="status">
            <div id="status-text" class="status-title {{ $isActive ? 'active' : 'inactive' }}">
                Website sedang {{ $isActive ? 'Aktif' : 'Nonaktif' }}
            </div>
            <div class="status-subtitle">Sentuh toggle untuk mengubah. Tersimpan otomatis.</div>
            <div id="status-error" class="status-error"></div>
        </div>
    </div>

    <script src="{{ asset('/') }}plugins/sweetalert/dist/sweetalert.min.js"></script>
    <script>
        (function () {
            const toggle = document.getElementById('website-status-toggle');
            const statusText = document.getElementById('status-text');
            const statusError = document.getElementById('status-error');
            const endpoint = @json(route('secret-setting.update'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            function setStatusText(isActive) {
                statusText.textContent = 'Website sedang ' + (isActive ? 'Aktif' : 'Nonaktif');
                statusText.classList.toggle('active', isActive);
                statusText.classList.toggle('inactive', !isActive);
            }

            function confirmChange(nextValue) {
                const title = nextValue ? 'Aktifkan website?' : 'Nonaktifkan website?';
                const text = nextValue
                    ? 'Pengunjung akan dapat mengakses website lagi.'
                    : 'Semua pengunjung akan melihat halaman nonaktif.';

                if (window.swal) {
                    return swal({
                        title: title,
                        text: text,
                        icon: 'warning',
                        buttons: ['Batal', 'Ya, lanjut'],
                        dangerMode: !nextValue
                    }).then(function (confirmed) {
                        return Boolean(confirmed);
                    });
                }

                return Promise.resolve(window.confirm(title + '\n' + text));
            }

            async function updateStatus(nextValue) {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        website_status: nextValue ? 1 : 0
                    })
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const message = data.message || 'Gagal menyimpan status website.';
                    throw new Error(message);
                }

                return data;
            }

            toggle.addEventListener('change', async function () {
                const nextValue = toggle.checked;
                statusError.style.display = 'none';

                const confirmed = await confirmChange(nextValue);
                if (!confirmed) {
                    toggle.checked = !nextValue;
                    return;
                }

                toggle.disabled = true;

                try {
                    await updateStatus(nextValue);
                    setStatusText(nextValue);
                } catch (err) {
                    toggle.checked = !nextValue;
                    setStatusText(!nextValue);
                    statusError.textContent = err.message || 'Terjadi kesalahan.';
                    statusError.style.display = 'block';
                } finally {
                    toggle.disabled = false;
                }
            });
        })();
    </script>
</body>
</html>

