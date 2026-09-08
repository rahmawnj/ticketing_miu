@extends('layouts.master', ['title' => $title, 'breadcrumbs' => $breadcrumbs])

@push('style')
{{-- Sertakan style yang dibutuhkan, misalnya select2 jika ada --}}
@endpush

@section('content')
<div class="panel panel-inverse">
    <div class="panel-heading">
        <h4 class="panel-title">{{ $title }}</h4>
        <div class="panel-heading-btn">
            <a href="{{ route('penyewaan.index') }}" class="btn btn-xs btn-icon btn-default"><i class="fa fa-times"></i> Back</a>
        </div>
    </div>

    <div class="panel-body">
        <form action="{{ route('penyewaan.store') }}" method="post" id="form-penyewaan">
            @csrf

            <div class="row">
                {{-- Kiri --}}
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="ticket">Jenis Lainnya</label>
                        <select name="ticket" id="ticket" class="form-control">
                            <option disabled selected>-- Select Transaksi Lainnya --</option>
                            @foreach($tickets as $ticket)
                            {{-- MENAMBAH DATA-ATTRIBUTES UNTUK PBJT --}}
                            <option
                                value="{{ $ticket->id }}"
                                data-harga="{{ $ticket->harga }}"
                                data-use-ppn="{{ $ticket->use_ppn }}"
                                data-ppn-rate="{{ $ticket->ppn }}"
                                data-is-nominal-flexible="{{ (int) ($ticket->is_nominal_flexible ?? 0) }}"
                                data-use-time="{{ $ticket->use_time ?? 0 }}">
                                {{ $ticket->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('ticket')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="harga_ticket">Harga Lainnya / DPP (Per Item, sebelum PBJT)</label>
                        <div class="input-group">
                            <input type="text" name="harga_ticket" id="harga_ticket" class="form-control bg-light" value="0" readonly>
                            <span class="input-group-text">Qty</span>
                            <input type="number" name="qty" id="qty" class="form-control" value="1" min="1" style="max-width: 110px;">
                        </div>
                        <small class="text-muted d-block mt-1" id="dynamic-price-note">Pilih jenis transaksi lainnya terlebih dahulu.</small>
                        @error('harga_ticket')
                        <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                        @error('qty')
                        <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Input Bayar (hanya untuk cash) --}}
                    <div class="form-group mb-3 bayar d-none">
                        <label for="bayar">Bayar</label>
                        <input type="text" name="bayar" id="bayar" class="form-control" value="0">
                        @error('bayar')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- Kanan --}}
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="metode">Metode</label>
                        <select name="metode" id="metode" class="form-control">
                            <option disabled selected>-- Pilih Metode --</option>
                            @foreach(\App\Support\PaymentMethod::options() as $methodValue => $methodLabel)
                            <option value="{{ $methodValue }}">{{ $methodLabel }}</option>
                            @endforeach
                        </select>
                        @error('metode')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3 card-fields d-none">
                        <label for="nama_kartu"><sup class="text-danger">*</sup>Nama Rekening / Pemilik Kartu</label>
                        <input type="text" name="nama_kartu" id="nama_kartu" class="form-control" value="{{ old('nama_kartu') }}" placeholder="Nama pemilik rekening">
                        @error('nama_kartu')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3 card-fields d-none">
                        <label for="no_kartu"><sup class="text-danger">*</sup>No Kartu / No Rekening</label>
                        <input type="text" name="no_kartu" id="no_kartu" class="form-control" value="{{ old('no_kartu') }}" placeholder="No kartu atau rekening">
                        @error('no_kartu')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3 card-fields d-none">
                        <label for="bank"><sup class="text-danger">*</sup>Bank</label>
                        <input type="text" name="bank" id="bank" class="form-control" value="{{ old('bank') }}" placeholder="Contoh: BCA">
                        @error('bank')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3 rfid-fields d-none">
                        <label for="name">RFID (Jika Emoney)</label>
                        <input type="text" name="name" id="name" class="form-control" value="" readonly>
                        @error('name')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3 rfid-fields d-none">
                        <label for="sisa">Saldo (Jika Emoney)</label>
                        <input type="text" name="sisa" id="sisa" class="form-control" value="0" readonly>
                        @error('sisa')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="jumlah">Total Jumlah Bayar (sudah termasuk PBJT)</label>
                        <input type="text" name="jumlah" id="jumlah" class="form-control" value="0" readonly>
                        @error('jumlah')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="pbjt_total">PBJT Otomatis</label>
                        <input type="text" id="pbjt_total" class="form-control" value="0" readonly>
                    </div>

                    {{-- Input Kembali (hanya untuk cash) --}}
                    <div class="form-group mb-3 kembali d-none">
                        <label for="kembali">Kembali</label>
                        <input type="text" name="kembali" id="kembali" class="form-control" value="0" readonly>
                        @error('kembali')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- Bawah (Total + Description + End Time) --}}
                <div class="col-md-12">
                    <div class="row g-3 align-items-start">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="keterangan">Description</label>
                                <textarea name="keterangan" id="keterangan" class="form-control" rows="3" placeholder="Keterangan penyewaan"></textarea>
                                @error('keterangan')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3 time-fields d-none">
                                <label for="jam">Jam Sewa</label>
                                <div class="input-group">
                                    <input type="number" name="jam" id="jam" class="form-control" value="1" min="1" step="1">
                                    <span class="input-group-text">Jam</span>
                                </div>
                                <small class="text-muted">End Time tersimpan otomatis.</small>
                                <input type="hidden" name="end_time" id="end_time" value="">
                                @error('jam')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                                <div class="mt-2">
                                    <label for="end_time_preview" class="form-label mb-1">End Time (Otomatis)</label>
                                    <input type="text" id="end_time_preview" class="form-control" value="" readonly placeholder="--:--">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Submit Transaksi Lainnya</button>
                <a href="{{ route('penyewaan.index') }}" class="btn btn-white">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('script')
    <script>
    $(document).ready(function() {

        // --- Fungsi Format Rupiah Universal ---
        /* Fungsi formatRupiah */
        function formatRupiah(angka) {
            var number_string = angka.toString().replace(/[^,\d]/g, ''),
                split = number_string.split(','),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            // tambahkan titik jika yang di input sudah menjadi angka ribuan
            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            // Memastikan tidak ada format yang aneh, hanya menggunakan titik sebagai pemisah ribuan
            return rupiah;
        }

        // --- Fungsi untuk Membersihkan Format Rupiah menjadi Angka Murni (Integer) ---
        function cleanRupiah(angka) {
            // Hapus titik sebagai pemisah ribuan, lalu ubah ke integer
            let cleaned = angka.toString().replace(/\./g, '');
            return parseInt(cleaned) || 0; // Pastikan hasilnya integer atau 0 jika gagal
        }

        function getSelectedPpnRate() {
            let selectedOption = $("#ticket").find('option:selected');
            let masterHarga = parseInt(selectedOption.attr("data-harga")) || 0;
            let usePpn = selectedOption.attr("data-use-ppn") == '1' || selectedOption.attr("data-use-ppn") == 'true';
            let masterPpnNominal = parseFloat(selectedOption.attr("data-ppn-rate")) || 0;

            if (!usePpn || masterHarga <= 0 || masterPpnNominal <= 0) {
                return 0;
            }

            return masterPpnNominal / masterHarga;
        }

        // Set default harga per item dari master (DPP)
        function setDefaultHargaPerItem() {
            let selectedOption = $("#ticket").find('option:selected');
            let masterHarga = parseInt(selectedOption.attr("data-harga")) || 0;
            let hargaPerItem = masterHarga;

            $("#harga_ticket").val(formatRupiah(hargaPerItem.toString()));
        }

        function toggleHargaTicketEditability() {
            let selectedOption = $("#ticket").find('option:selected');
            let isFlexible = selectedOption.attr("data-is-nominal-flexible") == '1';
            let hasSelectedTicket = !!selectedOption.val();

            if (!hasSelectedTicket) {
                $("#harga_ticket").prop('readonly', true).addClass('bg-light');
                $("#dynamic-price-note").text('Pilih jenis transaksi lainnya terlebih dahulu.');
                return;
            }

            if (isFlexible) {
                $("#harga_ticket").prop('readonly', false).removeClass('bg-light');
                $("#dynamic-price-note").text('Dynamic Price aktif: isi DPP per item, PBJT dihitung otomatis.');
            } else {
                $("#harga_ticket").prop('readonly', true).addClass('bg-light');
                $("#dynamic-price-note").text('Dynamic Price nonaktif: DPP mengikuti master data sewa, PBJT dihitung otomatis.');
            }
        }

        // Kalkulasi total: (DPP x qty) + PBJT otomatis.
        function calculatePrice() {
            let basePerItem = cleanRupiah($("#harga_ticket").val() || '0');
            let qty = parseInt($("#qty").val()) || 1;
            let ppnRate = getSelectedPpnRate();
            let ppnPerItem = Math.round(basePerItem * ppnRate);
            let pbjtTotal = ppnPerItem * qty;
            let jumlahTotal = (basePerItem * qty) + pbjtTotal;

            $("#pbjt_total").val(formatRupiah(pbjtTotal.toString()));
            $("#jumlah").val(formatRupiah(jumlahTotal.toString()));

            let metode = $("#metode").val();
            if (metode == 'cash') {
                $("#bayar").val(formatRupiah(jumlahTotal.toString()));
                $("#kembali").val(formatRupiah('0'));
            } else if (metode === 'tap') {
            }
        }


        function toggleTimeFields() {
            let selectedOption = $("#ticket").find('option:selected');
            let useTime = selectedOption.attr("data-use-time") == '1';
            if (useTime) {
                $(".time-fields").removeClass('d-none');
                calculateEndTimeFromJam();
            } else {
                $(".time-fields").addClass('d-none');
                $("#end_time").val('');
                $("#jam").val(1);
                $("#end_time_preview").val('');
            }
        }

        function calculateEndTimeFromJam() {
            let selectedOption = $("#ticket").find('option:selected');
            let useTime = selectedOption.attr("data-use-time") == '1';
            if (!useTime) {
                $("#end_time").val('');
                $("#end_time_preview").val('');
                return;
            }

            let jam = parseFloat($("#jam").val()) || 0;
            if (jam <= 0) {
                $("#end_time").val('');
                $("#end_time_preview").val('');
                return;
            }

            let now = new Date();
            let minutesToAdd = Math.round(jam * 60);
            let endDate = new Date(now.getTime() + (minutesToAdd * 60000));
            let hh = String(endDate.getHours()).padStart(2, '0');
            let mm = String(endDate.getMinutes()).padStart(2, '0');
            let endTimeValue = `${hh}:${mm}`;

            $("#end_time").val(endTimeValue);
            $("#end_time_preview").val(endTimeValue);
        }

        $("#ticket").on('change', function() {
            setDefaultHargaPerItem();
            toggleHargaTicketEditability();
            calculatePrice();
            toggleTimeFields();
        });
        $("#qty").on('change', calculatePrice);
        $("#harga_ticket").on('keyup change', function() {
            this.value = formatRupiah(this.value);
            calculatePrice();
        });
        $("#jam").on('keyup change', calculateEndTimeFromJam);


        function togglePaymentFields() {
            let metode = $("#metode").val();
            let isCardMethod = metode === 'debit' || metode === 'kredit';

            if (isCardMethod) {
                $(".card-fields").removeClass('d-none');
                $("#nama_kartu, #no_kartu, #bank").attr('required', 'required');
            } else {
                $(".card-fields").addClass('d-none');
                $("#nama_kartu, #no_kartu, #bank").removeAttr('required').val('');
            }

            if (metode == 'tap') {
                $(".rfid-fields").removeClass('d-none');
                $("#name").removeAttr('readonly').val('');
                $("#sisa").val('0'); // Kosongkan Saldo
                $(".bayar").addClass('d-none');
                $(".kembali").addClass('d-none');
            } else {
                $(".rfid-fields").addClass('d-none');
                $("#name").attr("readonly", "readonly").val('');
                $("#sisa").val('0');
                $(".bayar").removeClass('d-none');
                $(".kembali").removeClass('d-none');

                // Set Bayar kembali ke Jumlah saat ganti ke non-tap
                let jumlah = $("#jumlah").val();
                $("#bayar").val(jumlah);
                $("#kembali").val(formatRupiah('0'));
            }
        }

        $("#metode").on('change', function() {
            togglePaymentFields();
        })

        // --- Logika Perhitungan Kembalian (Cash) ---
        $("#bayar").on('keyup', function() {
            // Hanya jalankan jika metode cash
            if ($("#metode").val() !== 'cash') return;

            // Bersihkan input bayar saat ini dari format Rupiah (titik)
            let bayar = cleanRupiah($(this).val());

            // Bersihkan input jumlah dari format Rupiah (titik)
            let price = cleanRupiah($("#jumlah").val());

            // Lakukan perhitungan integer
            let kembali = bayar - price;

            // Tampilkan kembali hasil kembalian dalam format Rupiah
            $("#kembali").val(formatRupiah(kembali.toString()));
        })

        // --- Format Rupiah Live untuk Input Bayar ---
        var rupiahInput = document.getElementById('bayar');
        if (rupiahInput) {
            rupiahInput.addEventListener('keyup', function(e) {
                // Perhatian: Ini hanya memformat input, kalkulasi kembalian ada di event keyup sebelumnya
                rupiahInput.value = formatRupiah(this.value);
            });
        }

        // --- Logika RFID (Tetap) ---
        $('#form-penyewaan').on('keyup keypress', function(e) {
            var keyCode = e.keyCode || e.which;
            if (keyCode === 13 && e.target && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                return false;
            }
        });

        $("#name").on('keypress', function(e) {
            if (e.which == 13) {
                e.preventDefault();
                let rfid = $(this).val();

                $.ajax({
                    url: "/api/members",
                    type: "GET",
                    method: "GET",
                    data: {
                        rfid: rfid
                    },
                    success: function(response) {
                        if (response.status == 'success') {
                            let member = response.member;
                            // Asumsi saldo adalah integer
                            $("#sisa").val(formatRupiah(member.saldo.toString()))
                        } else {
                            // Gunakan sweetalert (asumsi library sudah dimuat)
                            swal("Error", "Member tidak ditemukan atau Expired.", "error");
                            $("#name").val("")
                            $("#sisa").val(formatRupiah('0'))

                        }
                    },
                    error: function(response) {
                        swal("Error", "Gagal memproses RFID.", "error");
                        $("#name").val("")
                        $("#sisa").val(formatRupiah('0'))
                    }
                })
            }
        })

        // Pastikan nilai total pada posisi lama (#jumlah) selalu ter-update saat submit
        $('#form-penyewaan').on('submit', function() {
            calculatePrice();
            calculateEndTimeFromJam();
        });

        // Inisialisasi awal
        setDefaultHargaPerItem();
        toggleHargaTicketEditability();
        calculatePrice();
        togglePaymentFields();
    })
</script>
@endpush
