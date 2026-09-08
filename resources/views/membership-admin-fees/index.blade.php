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
        <a href="#modal-dialog" id="btn-add" class="btn btn-primary mb-3" data-route="{{ route('membership-admin-fees.store') }}" data-bs-toggle="modal">
            <i class="ion-ios-add"></i> Add Jenis Admin
        </a>

        <table id="datatable" class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th class="text-nowrap">No</th>
                    <th class="text-nowrap">Jenis Admin</th>
                    <th class="text-nowrap">Biaya Admin</th>
                    <th class="text-nowrap">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modal-dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Form Jenis Admin</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <form action="" method="post" id="form-membership-admin-fee">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="admin_type">Jenis Admin</label>
                        <input type="text" name="admin_type" id="admin_type" class="form-control" maxlength="100" required placeholder="Contoh: Admin Family">
                    </div>

                    <div class="form-group mb-3">
                        <label for="admin_fee">Biaya Admin (Rp)</label>
                        <input type="number" name="admin_fee" id="admin_fee" class="form-control" min="0" value="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="javascript:;" class="btn btn-white" data-bs-dismiss="modal">Close</a>
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
    function clearAdminFeeModal() {
        var $form = $('#form-membership-admin-fee');
        $form[0].reset();
        $form.find("input[name='_method']").remove();
    }

    var table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: "{{ route('membership-admin-fees.list') }}",
        deferRender: true,
        pagination: true,
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', sortable: false, searchable: false },
            { data: 'admin_type', name: 'admin_type' },
            { data: 'admin_fee', name: 'admin_fee' },
            { data: 'action', name: 'action', sortable: false, searchable: false },
        ]
    });

    $('#btn-add').on('click', function() {
        clearAdminFeeModal();
        $('#form-membership-admin-fee').attr('action', $(this).data('route'));
    });

    $('#modal-dialog').on('hidden.bs.modal', function() {
        clearAdminFeeModal();
        $('#form-membership-admin-fee').removeAttr('action');
    });

    $('#datatable').on('click', '.btn-edit', function() {
        clearAdminFeeModal();

        var updateRoute = $(this).data('route');
        var showRoute = $(this).data('show-route');

        $('#form-membership-admin-fee').attr('action', updateRoute);
        $('#form-membership-admin-fee').append('<input type="hidden" name="_method" value="PUT">');

        $.ajax({
            url: showRoute,
            type: 'GET',
            success: function(response) {
                var row = response.data || {};
                $('#admin_type').val(row.admin_type || '');
                $('#admin_fee').val(row.admin_fee || 0);
            }
        });
    });

    $('#datatable').on('click', '.btn-delete', function(e) {
        e.preventDefault();
        var route = $(this).data('route');
        $('#form-delete').attr('action', route);

        swal({
            title: 'Hapus jenis admin?',
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
        }).then(function(result) {
            if (result) {
                $('#form-delete').submit();
                return;
            }

            $('#form-delete').attr('action', '');
        });
    });
</script>
@endpush
