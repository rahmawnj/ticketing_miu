@extends('layouts.master', ['title' => $title, 'breadcrumbs' => $breadcrumbs])

@push('style')
<link href="{{ asset('/') }}plugins/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/select2/dist/css/select2.min.css" rel="stylesheet" />
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
        <a href="#modal-dialog" id="btn-add" class="btn btn-primary mb-3" data-route="{{ route('memberships.store') }}" data-bs-toggle="modal"><i class="ion-ios-add"></i> Add Membership</a>

        <table id="datatable" class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th class="text-nowrap">No</th>
                    <th class="text-nowrap">Name</th>
                    <th class="text-nowrap">Kode</th>
                    <th class="text-nowrap">Price (Total)</th>
                    <th class="text-nowrap">Status PBJT</th> {{-- Kolom Baru --}}
                    <th class="text-nowrap">Duration</th>
                    <th class="text-nowrap">Max Person</th>
                    <th class="text-nowrap">Registered Member</th>
                    <th class="text-nowrap">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Modal Form --}}
<div class="modal fade" id="modal-dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Form Membership</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <form action="" method="post" id="form-membership">
                @csrf

                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="name">Membership Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="">
                        @error('name')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="code">Kode Membership</label>
                        <input type="text" name="code" id="code" class="form-control" value="" placeholder="Contoh: FP3B">
                        @error('code')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="price">Price Pokok (Tanpa PBJT)</label>
                        <input type="number" name="price" id="price" class="form-control" value="">
                        @error('price')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- PBJT Checkbox --}}
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="use_ppn" name="use_ppn" />
                        <label class="form-check-label" for="use_ppn">Gunakan PBJT ({{ $setting->ppn ?? 0 }}%)</label>
                    </div>

                    {{-- Jumlah PBJT (Readonly) --}}
                    <div class="form-group mb-3">
                        <label for="calculated_ppn">Jumlah PBJT (Rp)</label>
                        <input type="number" id="calculated_ppn" class="form-control" value="0" readonly>
                    </div>

                    <div class="form-group mb-3">
                        <label for="duration_days">Duration (Days)</label>
                        <input type="number" name="duration_days" id="duration_days" class="form-control" value="">
                        @error('duration_days')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="max_person">Max Person</label>
                        <input type="number" name="max_person" id="max_person" class="form-control" value="">
                        @error('max_person')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="max_access">Max Access (0 = Unlimited)</label>
                        <input type="number" name="max_access" id="max_access" class="form-control" value="0" min="0">
                        @error('max_access')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="gates">Dispenser ID</label>
                        <select name="gates[]" id="gates" class="form-control multiple-select2" multiple>
                            @foreach ($gates as $gate)
                            <option value="{{ $gate->id }}">{{ $gate->name }}</option>
                            @endforeach
                        </select>
                        @error('gates')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <a href="javascript:;" id="btn-close" class="btn btn-white" data-bs-dismiss="modal">Close</a>
                    <button type="submit" class="btn btn-primary" id="btn-submit">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Form Delete (hidden) --}}
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
<script src="{{ asset('/') }}plugins/select2/dist/js/select2.min.js"></script>

<script>
    $(".multiple-select2").select2({
        placeholder: "Select dispensers",
        dropdownParent: $("#modal-dialog")
    });

    // Ambil persentase PBJT dari PHP
    const PBJT_PERCENTAGE = parseFloat("{{ $setting->ppn ?? 0 }}");

    // Fungsi untuk menghitung dan menampilkan jumlah PBJT
    function calculatePpn() {
        const price = parseFloat($('#price').val()) || 0;
        const isPpnChecked = $('#use_ppn').prop('checked');
        let ppnAmount = 0;

        if (isPpnChecked) {
            ppnAmount = (price * PBJT_PERCENTAGE) / 100;
        }

        $('#calculated_ppn').val(ppnAmount.toFixed(0)); // Tampilkan 0 desimal
    }

    // --- FUNGSI UNTUK MEMBERSIHKAN MODAL & ERROR ---
    function clearModalForm() {
        var $form = $('#form-membership');

        // 1. Reset nilai form
        $form[0].reset();

        // 2. Hapus input _method (jika ada)
        $form.find("input[name='_method']").remove();

        // 3. Hapus semua class error validasi
        $form.find(".is-invalid").removeClass('is-invalid');

        // 4. Hapus semua pesan error <small>
        $form.find("small.text-danger").remove();

        // 5. Reset Select2 dan PBJT display
        $('#gates').val(null).trigger('change');
        $('#calculated_ppn').val(0);
    }

    var table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: "{{ route('memberships.list') }}", // Sesuaikan route
        deferRender: true,
        pagination: true,
        columns: [ // Sesuaikan columns
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                sortable: false,
                searchable: false
            },
            {
                data: 'name',
                name: 'name'
            },
            {
                data: 'code',
                name: 'code'
            },
            {
                data: 'price',
                name: 'price'
            },
            {
                data: 'ppn_status', // Kolom baru
                name: 'ppn_status',
                sortable: false
            },
            {
                data: 'duration_days',
                name: 'duration_days'
            },
            {
                data: 'max_person',
                name: 'max_person'
            },
            {
                data: 'total_members',
                name: 'total_members',
                searchable: false
            },
            {
                data: 'action',
                name: 'action',
                sortable: false,
                searchable: false
            },
        ]
    });

    // Event Handler untuk PBJT Calculation
    $(document).on('change keyup', '#price, #use_ppn', calculatePpn);
    $(document).on('input', '#code', function() {
        this.value = (this.value || '').toUpperCase().replace(/\s+/g, '');
    });

    // --- EVENT TOMBOL ADD ---
    $("#btn-add").on('click', function() {
        clearModalForm(); // Bersihkan modal
        let route = $(this).attr('data-route');
        $("#form-membership").attr('action', route);
    });

    // --- EVENT TOMBOL CLOSE MODAL ---
    $('#modal-dialog').on('hidden.bs.modal', function() {
        clearModalForm();
        $("#form-membership").removeAttr('action');
    });

    // --- EVENT TOMBOL EDIT ---
    $("#datatable").on('click', '.btn-edit', function() {
        clearModalForm(); // Bersihkan modal

        let route = $(this).attr('data-route');
        let id = $(this).attr('id');

        $("#form-membership").attr('action', route);
        $("#form-membership").append(`<input type="hidden" name="_method" value="PUT">`);

        // Panggil data via AJAX
        $.ajax({
            url: "/memberships/" + id, // Sesuaikan URL
            type: 'GET',
            success: function(response) {
                let membership = response.membership;

                // Isi form
                $("#name").val(membership.name);
                $("#code").val(membership.code);
                $("#price").val(membership.price);
                $("#duration_days").val(membership.duration_days);
                $("#max_person").val(membership.max_person);
                $("#max_access").val(membership.max_access ?? 0);

                // Isi PBJT
                if (membership.use_ppn == 1) {
                    $('#use_ppn').prop('checked', true);
                    $('#calculated_ppn').val(membership.ppn);
                } else {
                    $('#use_ppn').prop('checked', false);
                    $('#calculated_ppn').val(0);
                }

                // Isi gates
                if (membership.gates && Array.isArray(membership.gates)) {
                    let gateIds = membership.gates.map(function(gate) {
                        return gate.id;
                    });
                    $("#gates").val(gateIds).trigger('change');
                }
            }
        })
    });

    // --- EVENT TOMBOL DELETE ---
    $("#datatable").on('click', '.btn-delete', function(e) {
        e.preventDefault();
        let route = $(this).attr('data-route');
        $("#form-delete").attr('action', route);

        swal({
            title: 'Hapus data membership?',
            text: 'Data yang terhapus tidak bisa dikembalikan.',
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
                    text: 'Yes, Delete',
                    value: true,
                    visible: true,
                    className: 'btn btn-danger',
                    closeModal: true
                }
            }
        }).then((result) => {
            if (result) {
                $("#form-delete").submit();
            } else {
                $("#form-delete").attr('action', '');
            }
        });
    });
</script>
@endpush
