@extends('layouts.master', ['title' => $title, 'breadcrumbs' => $breadcrumbs])

@push('style')
<link href="{{ asset('/') }}plugins/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />
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
        <a href="#modal-dialog" id="btn-add" class="btn btn-primary mb-3" data-route="{{ route('sewa.store') }}" data-bs-toggle="modal"><i class="ion-ios-add"></i> Add Data Lainnya</a>

        <table id="datatable" class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th class="text-nowrap">No</th>
                    <th class="text-nowrap">Name</th>
                    <th class="text-nowrap">Harga (Total)</th>
                    <th class="text-nowrap">Dynamic Price</th>
                    <th class="text-nowrap">Status PBJT</th> {{-- Kolom baru --}}
                    <th class="text-nowrap">Device</th>
                    <th class="text-nowrap">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="modal fade" id="modal-dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Form Data Lainnya</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form action="{{ route('sewa.store') }}" method="post" id="form-sewa">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="">
                            @error('name')
                            <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="harga">Harga Pokok Default (Tanpa PBJT)</label>
                            <input type="number" name="harga" id="harga" class="form-control" value="">
                            @error('harga')
                            <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_nominal_flexible" name="is_nominal_flexible" value="1">
                            <label class="form-check-label" for="is_nominal_flexible">Aktifkan Dynamic Price (harga bisa diubah saat transaksi penyewaan)</label>
                        </div>

                        {{-- PBJT Checkbox --}}
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="ppn" name="ppn" />
                            <label class="form-check-label" for="ppn">Gunakan PBJT ({{ $setting->ppn ?? 0 }}%)</label>
                        </div>

                        {{-- Jumlah PBJT (Readonly) --}}
                        <div class="form-group mb-3">
                            <label for="jumlah_ppn">Jumlah PBJT (Rp)</label>
                            <input type="number" id="jumlah_ppn" class="form-control" value="0" readonly>
                        </div>

                        <div class="form-group mb-3">
                            <label for="device">Device ID</label>
                            <input type="number" name="device" id="device" class="form-control" value="">
                            @error('device')
                            <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="use_time" name="use_time" value="1">
                            <label class="form-check-label" for="use_time">Aktifkan Jam</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="javascript:;" id="btn-close" class="btn btn-white" data-bs-dismiss="modal">Close</a>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form action="" class="d-none" id="form-delete" method="post">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('script')
<script src="{{ asset('/') }}plugins/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
<script src="{{ asset('/') }}plugins/sweetalert/dist/sweetalert.min.js"></script>

<script>
    // Ambil persentase PBJT dari PHP
    const PBJT_PERCENTAGE = parseFloat("{{ $setting->ppn ?? 0 }}");

    // Fungsi untuk menghitung dan menampilkan jumlah PBJT
    function calculatePpn() {
        const harga = parseFloat($('#harga').val()) || 0;
        const isPpnChecked = $('#ppn').prop('checked');
        let ppnAmount = 0;

        if (isPpnChecked) {
            ppnAmount = (harga * PBJT_PERCENTAGE) / 100;
        }

        $('#jumlah_ppn').val(ppnAmount.toFixed(0)); // Tampilkan 0 desimal
    }

    // 1. Inisialisasi DataTable
    var table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: "{{ route('sewa.list') }}",
        deferRender: true,
        pagination: true,
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex' },
            { data: 'name', name: 'name' },
            { data: 'harga', name: 'harga' },
            { data: 'dynamic_price_status', name: 'dynamic_price_status' },
            { data: 'ppn_status', name: 'ppn_status' }, // Tambahkan kolom PBJT Status
            { data: 'device', name: 'device' },
            { data: 'action', name: 'action' },
        ]
    });

    // 2. Event Handler untuk PBJT Calculation
    $(document).on('change keyup', '#harga, #ppn', calculatePpn);

    // 3. Logic Form Modal (Add/Edit)
    $("#btn-add").on('click', function() {
        // Reset form untuk Add
        $("#form-sewa").trigger('reset');
        $("#form-sewa").attr('method', 'POST');
        $("#form-sewa input[name='_method']").remove();
        $("#jumlah_ppn").val(0); // Pastikan PBJT di-reset
        $('#use_time').prop('checked', false);
        $('#is_nominal_flexible').prop('checked', false);

        let route = $(this).attr('data-route');
        $("#form-sewa").attr('action', route);
    })

    $("#btn-close").on('click', function() {
        $("#form-sewa").attr('action', "{{ route('sewa.store') }}");
        $("#form-sewa").attr('method', 'POST');
        $("#form-sewa input[name='_method']").remove();
    })

    $("#datatable").on('click', '.btn-edit', function() {
        let route = $(this).attr('data-route');
        let id = $(this).attr('id');

        // Reset form sebelum load data
        $("#form-sewa").trigger('reset');

        $("#form-sewa").attr('action', route);
        $("#form-sewa").attr('method', 'POST'); // HTML method tetap POST
        $("#form-sewa input[name='_method']").remove(); // Hapus jika sudah ada
        $("#form-sewa").append(`<input type="hidden" name="_method" value="PUT">`);

        $.ajax({
            url: "/sewa/" + id,
            type: 'GET',
            method: 'GET',
            error: function(xhr) {
                alert("Gagal load data sewa. Status: " + xhr.status);
            },
            success: function(response) {
                let sewa = response.sewa;

                $("#name").val(sewa.name);
                // Harga yang disimpan di DB adalah harga pokok, bukan total
                $("#harga").val(sewa.harga);
                $("#device").val(sewa.device);
                $('#use_time').prop('checked', sewa.use_time == 1);
                $('#is_nominal_flexible').prop('checked', sewa.is_nominal_flexible == 1);
                $("#route-target").val($("#form-sewa").attr('action'));

                // Set PBJT checkbox dan nilai PBJT
                if (sewa.use_ppn == 1) {
                    $('#ppn').prop('checked', true);
                    $('#jumlah_ppn').val(sewa.ppn);
                } else {
                    $('#ppn').prop('checked', false);
                    $('#jumlah_ppn').val(0);
                }
            }
        })
    })


    // 4. Logic Delete (SweetAlert)
    $("#datatable").on('click', '.btn-delete', function(e) {
        e.preventDefault();
        let route = $(this).attr('data-route');
        $("#form-delete").attr('action', route);

        swal({
            title: 'Hapus data sewa?',
            text: 'Menghapus data bersifat permanen.',
            icon: 'error',
            buttons: {
                cancel: {
                    text: 'Cancel',
                    value: null,
                    visible: true,
                    className: 'btn btn-default',
                    closeModal: true,
                },
                confirm: {
                    text: 'Yes',
                    value: true,
                    visible: true,
                    className: 'btn btn-danger',
                    closeModal: true
                }
            }
        }).then((result) => {
            if (result) {
                $("#form-delete").submit()
            } else {
                $("#form-delete").attr('action', '')
            }
        });
    })
</script>
@endpush
