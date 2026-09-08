@extends('layouts.master', ['title' => $title, 'breadcrumbs' => $breadcrumbs])

@push('style')
<link href="{{ asset('/') }}plugins/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="panel panel-inverse">
    <!-- BEGIN panel-heading -->
    <div class="panel-heading">
        <h4 class="panel-title">{{ $title }}</h4>
        <div class="panel-heading-btn">
            <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
            <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
            <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
            <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
    </div>
    <!-- END panel-heading -->
    <!-- BEGIN panel-body -->
    <div class="panel-body">
        <a href="#modal-dialog" id="btn-add" class="btn btn-primary mb-3" data-route="{{ route('gate-accesses.store') }}" data-bs-toggle="modal"><i class="ion-ios-add"></i> Add Dispenser</a>

        <table id="datatable" class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th class="text-nowrap">No</th>
                    <th class="text-nowrap">Dispenser ID</th>
                    <th class="text-nowrap">Dispenser Name</th>
                    <th class="text-nowrap">Status</th>
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
                <h4 class="modal-title">Form Dispenser Access</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            {{-- Ganti id form --}}
            <form action="" method="post" id="form-gate-access">
                @csrf

                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="gate_access_id" class="form-label">Dispenser ID</label>
                        <input type="text" name="gate_access_id" id="gate_access_id" class="form-control" value="">

                        @error('gate_access_id')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="name" class="form-label">Dispenser Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="">

                        @error('name')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="is_active" class="form-label">Status</label>
                        <select name="is_active" id="is_active" class="form-control">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>

                        @error('is_active')
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

<script>
    // --- FUNGSI UNTUK MEMBERSIHKAN MODAL & ERROR ---
    function clearModalForm() {
        var $form = $('#form-gate-access');

        $form[0].reset();
        $form.find("input[name='_method']").remove();
    }

    var table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: "{{ route('gate-accesses.list') }}", // Sesuaikan route
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
                data: 'gate_access_id',
                name: 'gate_access_id'
            },
            {
                data: 'name',
                name: 'name'
            },
            {
                data: 'is_active',
                name: 'is_active'
            },
            {
                data: 'action',
                name: 'action',
                sortable: false,
                searchable: false
            },
        ]
    });

    // --- EVENT TOMBOL ADD ---
    $("#btn-add").on('click', function() {
        clearModalForm();
        let route = $(this).attr('data-route');
        $("#form-gate-access").attr('action', route);
    });

    // --- EVENT TOMBOL CLOSE MODAL ---
    $('#modal-dialog').on('hidden.bs.modal', function() {
        clearModalForm();
        $("#form-gate-access").removeAttr('action');
    });

    // --- EVENT TOMBOL EDIT ---
    $("#datatable").on('click', '.btn-edit', function() {
        clearModalForm();

        let route = $(this).attr('data-route');
        let id = $(this).attr('id');

        $("#form-gate-access").attr('action', route);
        $("#form-gate-access").append(`<input type="hidden" name="_method" value="PUT">`);

        // Panggil data via AJAX
        $.ajax({
            url: "/gate-accesses/" + id, // Sesuaikan URL
            type: 'GET',
            success: function(response) {
                let gate_access = response.gate_access;

                // Isi form
                $("#gate_access_id").val(gate_access.gate_access_id);
                $("#name").val(gate_access.name);
                $("#is_active").val(gate_access.is_active);
            }
        })
    });

    // --- EVENT TOMBOL DELETE ---
    $("#datatable").on('click', '.btn-delete', function(e) {
        e.preventDefault();
        let route = $(this).attr('data-route');
        $("#form-delete").attr('action', route);

        swal({
            title: 'Hapus data dispenser?',
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
