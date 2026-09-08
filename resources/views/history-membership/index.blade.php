@extends('layouts.master', ['title' => $title, 'breadcrumbs' => $breadcrumbs])

@push('style')
<link href="{{ asset('/') }}plugins/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" />
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
        <form action="" method="get">
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="from">From</label>
                        <input type="date" name="from" id="from" class="form-control" value="{{ request('from') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="to">To</label>
                        <input type="date" name="to" id="to" class="form-control" value="{{ request('to') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="kasir">Kasir</label>
                        <select name="kasir" id="kasir" class="form-control">
                            <option value="all" selected>All</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group mt-1">
                        <button type="submit" class="btn btn-primary mt-3">Submit</button>
                        <a href="from={{ request('from') }}&to={{ request('to') }}&kasir={{ request('kasir') }}" class="btn btn-success mt-3"><i class="fas fa-file-excel me-1"></i>Download</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table id="datatable" class="table table-striped table-bordered align-middle w-100">
                <thead>
                    <tr>
                        <th class="text-nowrap">No</th>
                        <th class="text-nowrap">Nama</th>
                        <th class="text-nowrap">Membership</th>
                        <th class="text-nowrap">Start Date</th>
                        <th class="text-nowrap">End Date</th>
                        <th class="text-nowrap">Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('script')
<script src="{{ asset('/') }}plugins/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-buttons/js/buttons.colVis.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-buttons/js/buttons.flash.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="{{ asset('/') }}plugins/pdfmake/build/pdfmake.min.js"></script>
<script src="{{ asset('/') }}plugins/pdfmake/build/vfs_fonts.js"></script>
<script src="{{ asset('/') }}plugins/jszip/dist/jszip.min.js"></script>
<script src="{{ asset('/') }}plugins/sweetalert/dist/sweetalert.min.js"></script>

<script>
    let from = $("#from").val();
    let to = $("#to").val();
    let kasir = $("#kasir").val();

    var table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('history-memberships.list') }}",
            type: "GET",
            data: {
                "from": from,
                "to": to,
            }
        },
        deferRender: true,
        pagination: true,
        columns: [{
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                searchable: false,
                sortable: false
            },
            {
                data: 'member.nama',
                name: 'member.nama'
            },
            {
                data: 'membership.name',
                name: 'membership.name'
            },
            {
                data: 'start_date',
                name: 'start_date',
            },
            {
                data: 'end_date',
                name: 'end_date'
            },
            {
                data: 'status',
                name: 'status'
            },
        ]
    });
</script>
@endpush