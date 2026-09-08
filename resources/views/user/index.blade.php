@extends('layouts.master', ['title' => $title, 'breadcrumbs' => $breadcrumbs])

@push('style')
<link href="{{ asset('/') }}plugins/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="panel panel-inverse">
    <div class="panel-heading">
        <h4 class="panel-title">{{ $title }}</h4>
    </div>
    <div class="panel-body">
        <a href="#modal-dialog" id="btn-add" class="btn btn-primary mb-3" data-route="{{ route('users.store') }}" data-bs-toggle="modal">
            <i class="fa fa-plus"></i> Add User
        </a>

        <table id="datatable" class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th width="1%">No</th>
                    <th>Username</th>
                    <th>UID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th width="10%">Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modal-dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Form User</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <form action="" method="post" id="form-user">
                @csrf
                <div id="method-field"></div> <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}">
                        @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">UID</label>
                        <input type="text" name="uid" id="uid" class="form-control @error('uid') is-invalid @enderror" value="{{ old('uid') }}">
                        @error('uid') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror">
                        <small class="text-muted f-s-11">Kosongkan jika tidak ingin mengubah password (saat edit)</small>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" id="role" class="form-select @error('role') is-invalid @enderror">
                            <option value="" disabled selected>-- Select Role --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-white" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form action="" id="form-delete" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('script')
<script src="{{ asset('/') }}plugins/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="{{ asset('/') }}plugins/sweetalert/dist/sweetalert.min.js"></script>

<script>
    $(document).ready(function() {
        var table = $('#datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('users.list') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                {data: 'username', name: 'username'},
                {data: 'uid', name: 'uid'},
                {data: 'name', name: 'name'},
                {data: 'role', name: 'role'},
                {data: 'is_active', name: 'is_active'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ]
        });

        // FUNGSI UTAMA UNTUK RESET ERROR
        function clearErrors() {
            // Hapus class merah di input
            $('.form-control, .form-select').removeClass('is-invalid');
            // Hapus tulisan pesan error di bawah input
            $('.invalid-feedback').remove();
            // Reset form data
            $('#form-user')[0].reset();
            $('#method-field').html('');
        }

        // Klik tombol Add
        $("#btn-add").on('click', function() {
            clearErrors();
            $("#form-user").attr('action', $(this).data('route'));
        });

        // Klik tombol Edit
        $('#datatable').on('click', '.btn-edit', function() {
            clearErrors();
            let route = $(this).data('route');
            let id = $(this).attr('id');

            $("#form-user").attr('action', route);
            $("#method-field").html('@method("PUT")');

            $.ajax({
                url: "/users/" + id,
                type: 'GET',
                success: function(res) {
                    $("#username").val(res.user.username);
                    $("#uid").val(res.user.uid);
                    $("#name").val(res.user.name);
                    $("#role").val(res.role);
                    $("#status").val(res.user.is_active);
                }
            });
        });

        // Auto open modal jika validasi PHP gagal
        @if($errors->any())
            var myModal = new bootstrap.Modal(document.getElementById('modal-dialog'));
            myModal.show();
        @endif

        // Delete handling
        $('#datatable').on('click', '.btn-delete', function() {
            let route = $(this).data('route');
            swal({
                title: "Are you sure?",
                text: "Data user akan dihapus permanen!",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $('#form-delete').attr('action', route).submit();
                }
            });
        });
    });
</script>
@endpush
