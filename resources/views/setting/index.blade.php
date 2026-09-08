@extends('layouts.master', ['title' => $title, 'breadcrumbs' => $breadcrumbs])

@push('style')
<link href="{{ asset('/') }}plugins/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/select2/dist/css/select2.min.css" rel="stylesheet" />
<style>
    #preview-container img {
        max-height: 150px;
        border: 1px solid #ddd;
        padding: 5px;
        border-radius: 4px;
    }
</style>
@endpush

@section('content')
<div class="panel panel-inverse">
    <div class="panel-heading">
        <h4 class="panel-title">{{ $title }}</h4>
        <div class="panel-heading-btn">
            <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
            <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
            <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
            <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
    </div>

    <div class="panel-body">
        <form action="{{ route('setting.store') }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="tab" value="{{ $activeTab ?? 'general' }}">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <a href="{{ route('setting.index', ['tab' => 'general']) }}" class="nav-link {{ ($activeTab ?? 'general') === 'general' ? 'active' : '' }}" role="tab" aria-selected="{{ ($activeTab ?? 'general') === 'general' ? 'true' : 'false' }}">Umum</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('setting.index', ['tab' => 'renewal_notice']) }}" class="nav-link {{ ($activeTab ?? 'general') === 'renewal_notice' ? 'active' : '' }}" role="tab" aria-selected="{{ ($activeTab ?? 'general') === 'renewal_notice' ? 'true' : 'false' }}">Reminder Renewal</a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade {{ ($activeTab ?? 'general') === 'general' ? 'show active' : '' }}" id="tab-umum" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group mb-3">
                                <label for="name">Name</label>
                                <input type="text" name="name" id="name" class="form-control" value="{{ $setting->name ?? old('name') }}">
                                @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="ucapan">Ucapan</label>
                                <input type="text" name="ucapan" id="ucapan" class="form-control" value="{{ $setting->ucapan ?? old('ucapan') }}">
                                @error('ucapan') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="deskripsi">Deskripsi Singkat</label>
                                <textarea name="deskripsi" id="deskripsi" class="form-control" rows="3">{{ $setting->deskripsi ?? old('deskripsi') }}</textarea>
                                @error('deskripsi') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="form-check mb-3 d-none">
                                <input class="form-check-input" type="checkbox" id="show_logo_toggle" name="use_logo" value="1" {{ (isset($setting) && $setting->use_logo == 1) ? 'checked' : '' }} />
                                <label class="form-check-label" for="show_logo_toggle">Show Logo</label>
                            </div>

                            <div class="form-group mb-3">
                                <label for="logo">Logo</label>
                                <input type="file" name="logo" id="logo_input" class="form-control">
                                @error('logo') <small class="text-danger">{{ $message }}</small> @enderror

                                <div id="preview-container" class="mt-3" style="{{ (isset($setting) && $setting->logo && $setting->use_logo == 1) ? '' : 'display:none;' }}">
                                    <label class="d-block">Current/Preview Logo:</label>
                                    @if(isset($setting) && $setting->logo)
                                        <img src="{{ asset('storage/' . $setting->logo) }}" id="logo_preview" alt="Logo">
                                    @else
                                        <img src="" id="logo_preview" alt="Logo Preview" style="display:none;">
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="form-group mb-3">
                                <label for="ppn">PBJT <sup class="text-danger">(%)</sup></label>
                                <input type="number" name="ppn" id="ppn" class="form-control" value="{{ $setting->ppn ?? old('ppn') }}">
                                @error('ppn') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="ticket_code_mode">Mode Kode Tiket</label>
                                <select name="ticket_code_mode" id="ticket_code_mode" class="form-control">
                                    @php
                                        $ticketCodeModeRaw = old('ticket_code_mode', $setting->ticket_code_mode ?? 'unique');
                                        $ticketCodeMode = in_array($ticketCodeModeRaw, ['shared', 'unique'], true)
                                            ? $ticketCodeModeRaw
                                            : 'unique';
                                    @endphp
                                    <option value="unique" {{ $ticketCodeMode === 'unique' ? 'selected' : '' }}>
                                        Kode Berbeda Semua (Unik)
                                    </option>
                                    <option value="shared" {{ $ticketCodeMode === 'shared' ? 'selected' : '' }}>
                                        Kode Sama per Jenis Ticket
                                    </option>
                                </select>
                                <small class="text-muted">Atur apakah 1 jenis ticket qty banyak dicetak dengan barcode unik semua atau barcode sama.</small>
                                @error('ticket_code_mode') <br><small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="ticket_print_orientation">Cetak Nota Kalkulasi (Halaman Pertama)</label>
                                <select name="ticket_print_orientation" id="ticket_print_orientation" class="form-control">
                                    @php
                                        $ticketPrintOrientationRaw = old('ticket_print_orientation', $setting->ticket_print_orientation ?? 'without_summary');
                                        if ($ticketPrintOrientationRaw === 'portrait') {
                                            $ticketPrintOrientationRaw = 'with_summary';
                                        } elseif ($ticketPrintOrientationRaw === 'portrait_with_first_qr') {
                                            $ticketPrintOrientationRaw = 'without_summary';
                                        }
                                        $ticketPrintOrientation = in_array($ticketPrintOrientationRaw, ['with_summary', 'without_summary'], true)
                                            ? $ticketPrintOrientationRaw
                                            : 'without_summary';
                                    @endphp
                                    <option value="without_summary" {{ $ticketPrintOrientation === 'without_summary' ? 'selected' : '' }}>
                                        Tidak Dicetak (Default)
                                    </option>
                                    <option value="with_summary" {{ $ticketPrintOrientation === 'with_summary' ? 'selected' : '' }}>
                                        Dicetak
                                    </option>
                                </select>
                                <small class="text-muted">Atur apakah halaman nota kalkulasi transaksi dicetak di halaman pertama.</small>
                                @error('ticket_print_orientation') <br><small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="dashboard_metric_mode">Mode Nilai Kartu Dashboard</label>
                                <select name="dashboard_metric_mode" id="dashboard_metric_mode" class="form-control">
                                    @php
                                        $dashboardMetricMode = $setting->dashboard_metric_mode ?? old('dashboard_metric_mode', 'amount');
                                    @endphp
                                    <option value="amount" {{ $dashboardMetricMode === 'amount' ? 'selected' : '' }}>
                                        Tampilkan Jumlah Uang
                                    </option>
                                    <option value="count" {{ $dashboardMetricMode === 'count' ? 'selected' : '' }}>
                                        Tampilkan Jumlah Transaksi
                                    </option>
                                </select>
                                <small class="text-muted">Mengatur nilai utama pada kartu Renewal, New Member, Rental, dan Ticket di dashboard.</small>
                                @error('dashboard_metric_mode') <br><small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="member_suspend_before_days">Rentang Perpanjangan Member <sup class="text-danger">(Hari)</sup></label>
                                <div class="input-group">
                                    <span class="input-group-text">Mulai H-</span>
                                    <input type="number" name="member_suspend_before_days" id="member_suspend_before_days" class="form-control" value="{{ $setting->member_suspend_before_days ?? old('member_suspend_before_days', 7) }}">
                                    <span class="input-group-text">sampai H+</span>
                                    <input type="number" name="member_suspend_after_days" id="member_suspend_after_days" class="form-control" value="{{ $setting->member_suspend_after_days ?? old('member_suspend_after_days', 0) }}">
                                    <span class="input-group-text">Hari</span>
                                </div>
                                <small class="text-muted">Contoh: H-7 sampai H+0. H-7 tetap Active, mulai H+1 status Expired.</small>
                                @error('member_suspend_before_days') <br><small class="text-danger">{{ $message }}</small> @enderror
                                @error('member_suspend_after_days') <br><small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="form-check mb-3 d-none">
                                <input class="form-check-input" type="checkbox" id="whatsapp_enabled" name="whatsapp_enabled" value="1" {{ old('whatsapp_enabled', isset($setting) ? (int) $setting->whatsapp_enabled : 0) ? 'checked' : '' }}>
                                <label class="form-check-label" for="whatsapp_enabled">Aktifkan Pengiriman WhatsApp</label>
                                <div><small class="text-muted">Default nonaktif. Centang jika notifikasi WhatsApp sudah siap digunakan.</small></div>
                                @error('whatsapp_enabled') <small class="text-danger d-block">{{ $message }}</small> @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="ticket_valid_days">Masa Berlaku Tiket <sup class="text-danger">(Hari)</sup></label>
                                <div class="input-group">
                                    <input type="number" name="ticket_valid_days" id="ticket_valid_days" class="form-control" value="{{ $setting->ticket_valid_days ?? old('ticket_valid_days', 1) }}" min="0">
                                    <span class="input-group-text">Hari</span>
                                </div>
                                <small class="text-muted">Isi 0 jika tiket bisa digunakan kapan saja.</small>
                                @error('ticket_valid_days') <br><small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="ticket_scan_limit">Batas Scan Tiket (Selama Masa Aktif)</label>
                                <div class="input-group">
                                    <input type="number" name="ticket_scan_limit" id="ticket_scan_limit" class="form-control" value="{{ $setting->ticket_scan_limit ?? old('ticket_scan_limit', 0) }}" min="0">
                                    <span class="input-group-text">Kali</span>
                                </div>
                                <small class="text-muted">Contoh: isi 5 untuk maksimal 5x scan. Isi 0 jika tidak dibatasi.</small>
                                @error('ticket_scan_limit') <br><small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade {{ ($activeTab ?? 'general') === 'renewal_notice' ? 'show active' : '' }}" id="tab-renewal-notice" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-10">
                            <div class="form-group mb-3">
                                <label for="renewal_notice_club_name">Nama Club di Surat Reminder</label>
                                <input type="text" name="renewal_notice_club_name" id="renewal_notice_club_name" class="form-control"
                                       value="{{ old('renewal_notice_club_name', $setting->renewal_notice_club_name ?? 'MIU') }}">
                                @error('renewal_notice_club_name') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="renewal_notice_bank_account">Informasi Transfer / Bank</label>
                                <textarea name="renewal_notice_bank_account" id="renewal_notice_bank_account" class="form-control" rows="3">{{ old('renewal_notice_bank_account', $setting->renewal_notice_bank_account ?? 'TRANSFER BANK: BCA 0289011155 A/N PT KARTUNINDO PERKASA ABADI') }}</textarea>
                                @error('renewal_notice_bank_account') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="renewal_notice_admin_phone">No HP Admin Reminder</label>
                                <input type="text" name="renewal_notice_admin_phone" id="renewal_notice_admin_phone" class="form-control"
                                       value="{{ old('renewal_notice_admin_phone', $setting->renewal_notice_admin_phone ?? '0821 2222 9358') }}">
                                @error('renewal_notice_admin_phone') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="renewal_notice_body_template">Template Isi Surat (Textarea)</label>
                                <textarea name="renewal_notice_body_template" id="renewal_notice_body_template" class="form-control" rows="14">{{ old('renewal_notice_body_template', $setting->renewal_notice_body_template ?? "Yth. Bapak/Ibu :member_name,\n\nKami informasikan bahwa masa aktif membership Anda akan berakhir pada :expired_date.\n\nAgar tetap dapat menikmati seluruh fasilitas, mohon melakukan perpanjangan dengan rincian:\n\nTipe Member: :membership_name\nBiaya: :total_price\nJatuh tempo: :due_date\n:note_block\nSilakan melakukan pembayaran sebelum jatuh tempo agar membership tetap aktif.\n\n:bank_account\n\nJika sudah melakukan pembayaran, harap informasi dan kirim bukti pembayaran ke nomor Admin.\nTerima kasih.\n\nAdmin\n:club_name\nNo.Hp: :admin_phone") }}</textarea>
                                @error('renewal_notice_body_template') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="alert alert-info mb-0">
                                Placeholder tersedia: <code>:member_name</code>, <code>:membership_name</code>, <code>:expired_date</code>, <code>:due_date</code>, <code>:base_price</code>, <code>:ppn_amount</code>, <code>:total_price</code>, <code>:admin_fee</code>, <code>:note_block</code>, <code>:club_name</code>, <code>:bank_account</code>, <code>:admin_phone</code>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('script')
<script src="{{ asset('/') }}plugins/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
<script src="{{ asset('/') }}plugins/sweetalert/dist/sweetalert.min.js"></script>
<script src="{{ asset('/') }}plugins/select2/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Fungsi untuk mengatur visibilitas container preview
        function togglePreviewVisibility() {
            const isChecked = $('#show_logo_toggle').is(':checked');
            const hasImage = $('#logo_preview').attr('src') !== "";

            if (isChecked && hasImage) {
                $('#preview-container').fadeIn();
            } else {
                $('#preview-container').fadeOut();
            }
        }

        // Jalankan saat checkbox berubah
        $('#show_logo_toggle').on('change', function() {
            togglePreviewVisibility();
        });

        // Jalankan saat file dipilih (Preview Upload)
        $('#logo_input').on('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#logo_preview').attr('src', e.target.result).show();
                    togglePreviewVisibility();
                }
                reader.readAsDataURL(file);
            }
        });
    });
</script>
@endpush


