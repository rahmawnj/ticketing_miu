@extends('layouts.master', ['title' => $title, 'breadcrumbs' => $breadcrumbs])

@push('style')

@endpush

@section('content')
@if(false)
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
        <form action="{{ $action }}" method="post">
            @method($method)
            @csrf

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="name">Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ $ticket->name ?? old('name') }}" disabled>

                        @error('name')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="harga">Harga</label>
                        <input type="number" name="harga" id="harga" class="form-control" value="{{ $ticket->harga ?? old('harga') }}" disabled>

                        @error('harga')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="harga">Tripod ID</label>
                        <input type="number" name="harga" id="harga" class="form-control" value="{{ $ticket->tripod ?? old('harga') }}" disabled>

                        @error('harga')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                @if($ticket->jenis_ticket_id == 2)
                <div class="col-md-12">
                    <h4>List Tiket Terusan</h4>
                    <a href="#modal-dialog" id="btn-add" class="btn btn-primary mb-3" data-route="{{ route('terusan.store') }}" data-bs-toggle="modal"><i class="ion-ios-add"></i> Add Terusan</a>

                    <table class="table table-bordered" id="datatable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ticket->terusan as $terusan)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $terusan->name }}</td>
                                <td><a href="" data-route="{{ route('terusan.destroy', $terusan->id) }}" class="btn btn-danger btn-delete">Delete</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </form>
    </div>

</div>
<div class="modal fade" id="modal-dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Form Terusan</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <form action="" method="post" id="form-role">
                @csrf

                <div class="modal-body">
                    <input type="hidden" name="ticket" value="{{ $ticket->id }}">
                    <div class="form-group mb-3">
                        <label for="name">Nama Terusan</label>
                        <input type="text" name="nama_terusan" id="name" class="form-control" value="">

                        @error('name')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="tripod">Tripod ID</label>
                        <input type="number" name="tripod" id="tripod" class="form-control" value="">

                        @error('tripod')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
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
@endif
@endsection

@if(false)
@push('script')
<script src="{{ asset('/') }}plugins/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
<script src="{{ asset('/') }}plugins/sweetalert/dist/sweetalert.min.js"></script>

<script>
    $("#datatable").DataTable()

    $("#btn-add").on('click', function() {
        let route = $(this).attr('data-route')
        $("#form-role").attr('action', route)
    })

    $("#datatable").on('click', '.btn-delete', function(e) {
        e.preventDefault();
        let route = $(this).attr('data-route')
        $("#form-delete").attr('action', route)

        swal({
            title: 'Hapus data ticket terusan?',
            text: 'Menghapus ticket terusan bersifat permanen.',
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
@endif
