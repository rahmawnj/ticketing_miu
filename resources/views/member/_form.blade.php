<!-- Form input member -->
<div class="form-group row mb-3">
    <div class="col-md-12 border-bottom">
        <h5>Member</h5>
    </div>
</div>

<div class="form-group row mb-3">
    <div class="col-md-4">
        <label for="membership" class="form-label"><sup class="text-danger">*</sup>Jenis Member</label>
        <select name="membership" id="membership" class="form-control">
            <option value="" {{ old('membership') ? '' : 'selected' }}>-- Pilih Jenis Member --</option>
            @foreach ($memberships as $membership)
            <option {{ old('membership') == $membership->id ? 'selected' : '' }} data-price="{{ $membership->price }}" data-duration="{{ $membership->duration_days }}" data-max-person="{{ $membership->max_person }}" value="{{ $membership->id }}">{{ $membership->name }}</option>
            @endforeach
        </select>

        @error('membership')
        <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="duration" class="form-label">Durasi (Hari)</label>
        <input type="text" name="" id="duration" class="form-control" disabled>
    </div>

    <div class="col-md-4">
        <label for="price" class="form-label">Harga (Rp.)</label>
        <input type="text" name="" id="price" class="form-control" disabled>
    </div>
</div>

<div class="form-group row mb-3">
    <div class="col-md-4">
        <label for="rfid" class="form-label">RFID</label>
        <input type="text" name="rfid" id="rfid" class="form-control" value="{{ old('rfid') }}" placeholder="0192029300">

        @error('rfid')
        <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-4">
        <div class="form-group mb-3">
            <label for="nama" class="form-label"><sup class="text-danger">*</sup>Nama</label>
            <input type="text" name="nama" id="nama" class="form-control" value="{{ old('nama') }}" placeholder="John Doe">

            @error('nama')
            <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group mb-3">
            <label for="no_ktp" class="form-label">No Identitas</label>
            <input type="number" name="no_ktp" id="no_ktp" class="form-control" value="{{ old('no_ktp') }}" placeholder="xxxxxxxxx" max="16">

            @error('no_ktp')
            <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>
    </div>
</div>

<div class="form-group row mb-3">
    <div class="col-md-4">
        <label for="no_hp" class="form-label"><sup class="text-danger">*</sup>No Hp</label>
        <input type="number" name="no_hp" id="no_hp" class="form-control" value="{{ old('no_hp') }}" placeholder="08xxxxx">

        @error('no_hp')
        <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="tanggal_lahir" class="form-label"><sup class="text-danger">*</sup>Tanggal Lahir</label>
        <input type="date" name="tanggal_lahir" id="tanggal_lahir" class="form-control" value="{{ old('tanggal_lahir') }}">

        @error('tanggal_lahir')
        <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="jenis_kelamin" class="form-label"><sup class="text-danger">*</sup>Jenis Kelamin</label>
        <select name="jenis_kelamin" id="jenis_kelamin" class="form-control">
            <option value="L" {{ old('jenis_kelamin') == 'L' ? 'selected' : '' }}>Laki-Laki</option>
            <option value="P" {{ old('jenis_kelamin') == 'P' ? 'selected' : '' }}>Perempuan</option>
        </select>

        @error('jenis_kelamin')
        <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
</div>

<div class="form-group mb-3">
    <label for="alamat" class="form-label"><sup class="text-danger">*</sup>Alamat</label>
    <textarea name="alamat" id="alamat" cols="30" rows="3" class="form-control" placeholder="Jakarta">{{ old('alamat') }}</textarea>

    @error('alamat')
    <small class="text-danger">{{ $message }}</small>
    @enderror
</div>

<div class="form-group mb-3">
            <label for="image_profile" class="form-label">Foto</label>
    <input type="file" name="image_profile" id="image_profile" class="form-control" accept="image/*">

    @error('image_profile')
    <small class="text-danger">{{ $message }}</small>
    @enderror
</div>

<div class="form-group mb-3">
    <img src="" alt="" id="image-preview" width="100">
</div>

<div class="form-group mb-3">
    <label for="tgl_expired" class="form-label">Tanggal Expired</label>
    <input type="date" name="tgl_expired" id="tgl_expired" class="form-control" value="" readonly>

    @error('tgl_expired')
    <small class="text-danger">{{ $message }}</small>
    @enderror
</div>

<div id="#target"></div>
<script>
    $(document).ready(function() {

        // --- Event Listener untuk Membership ---
        $('#membership').on('change', function() {
            // Hapus alert debugging
            // alert("change")

            // 1. KOSONGKAN TARGET SETIAP KALI BERUBAH
            // Ini adalah perbaikan untuk bug #1
            $('#target').empty();

            var selectedOption = $(this).find('option:selected');
            var durationStr = selectedOption.data('duration');
            var price = selectedOption.data('price');
            var max_person = parseInt(selectedOption.data('max-person')) || 0; // Pastikan ini angka

            if (this.value) { // Jika memilih opsi yang valid
                var duration = parseInt(durationStr);
                if (isNaN(duration)) {
                    duration = 0;
                }

                $('#duration').val(duration);
                $('#price').val(price);

                // --- Kalkulasi Tanggal Expired ---
                var today = new Date();
                today.setDate(today.getDate() + duration);

                var year = today.getFullYear();
                var month = (today.getMonth() + 1).toString().padStart(2, '0');
                var day = today.getDate().toString().padStart(2, '0');
                var formattedDate = year + '-' + month + '-' + day;

                $('#tgl_expired').val(formattedDate);

                // --- Loop untuk Anggota Grup ---
                // Loop dimulai dari 1 karena anggota ke-0 adalah form utama
                for (var i = 1; i < max_person; i++) {

                    // 2. PERBAIKAN DUPLIKAT ID
                    // Tambahkan `i` untuk membuat ID unik
                    var rfidGroupField = `
                        <div class="form-group row mb-3">
                            <div class="col-md-12 border-bottom">
                                <h5>Member Group ${i + 1}</h5>
                            </div>
                        </div>
                        <div class="form-group row mb-3">
                            <div class="col-md-4">
                                <label for="rfid_group_${i}" class="form-label">RFID</label>
                                <input type="number" name="rfid_group[]" id="rfid_group_${i}" class="form-control" placeholder="xxxxxxxxx">
                            </div>
                            <div class="col-md-4">
                                <label for="name_group_${i}" class="form-label">Nama</label>
                                <input type="text" name="name_group[]" id="name_group_${i}" class="form-control" placeholder="John Doe ${i + 1}">
                            </div>
                            <div class="col-md-4">
                                <label for="image_group_${i}" class="form-label">Foto</label>
                                <input type="file" name="image_group[]" id="image_group_${i}" class="form-control" accept="image/*">
                            </div>
                        </div>`;

                    $("#target").append(rfidGroupField);
                }
            } else {
                // Jika user memilih "-- Pilih --", kosongkan field
                $('#duration').val('');
                $('#price').val('');
                $('#tgl_expired').val('');
                // $('#target').empty(); // Ini sudah ditangani di baris paling atas
            }
        });

        if ($('#membership').val()) {
            $('#membership').trigger('change');
        }

        // --- 5. (PENINGKATAN) Script untuk Image Preview ---
        $('#image_profile').on('change', function(event) {
            var reader = new FileReader();
            reader.onload = function() {
                var output = document.getElementById('image-preview');
                output.src = reader.result;
                output.style.display = 'block'; // Tampilkan gambar
            };

            // Pastikan ada file yang dipilih
            if (event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            } else {
                // Jika user batal memilih, sembunyikan preview
                var output = document.getElementById('image-preview');
                output.src = '';
                output.style.display = 'none';
            }
        });

        // Sembunyikan preview gambar saat halaman dimuat
        $('#image-preview').css('display', 'none');

    });
</script>
