@extends('layouts.master', ['title' => $title, 'breadcrumbs' => $breadcrumbs])

@push('style')
<link href="{{ asset('/') }}plugins/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/select2/dist/css/select2.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet" />
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
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="daterange">Tanggal</label>
                        <input type="text" name="daterange" id="daterange" class="form-control" value="{{ request('daterange') }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="user">Karyawan</label>
                        <select name="user" id="user" class="form-control">
                            <option value="all" selected>All</option>
                            @foreach($users as $user)
                            <option {{ request('user') == $user->id ? 'selected' : '' }} value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group mt-1">
                        <button type="submit" class="btn btn-primary mt-3">Submit</button>
                        <a href="{{ route('history-karyawan.print') }}?daterange={{ request('daterange') }}" class="btn btn-info mt-3"><i class="fas fa-print me-1"></i>Print</a>
                        <a href="{{ route('history-karyawan.export') }}?daterange={{ request('daterange') }}" class="btn btn-success mt-3"><i class="fas fa-file-excel me-1"></i>Download</a>
                    </div>
                </div>
            </div>
        </form>
        <table id="datatable" class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th class="text-nowrap">No</th>
                    <th class="text-nowrap">Tanggal</th>
                    <th class="text-nowrap">Nama Karyawan</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
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
<script src="{{ asset('/') }}plugins/moment/min/moment.min.js"></script>
<script src="{{ asset('/') }}plugins/bootstrap-daterangepicker/daterangepicker.js"></script>

<script>
    let daterange = $("#daterange").val();
    let karyawan = $("#karyawan").val();

    var table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('history-karyawan.list') }}",
            type: "GET",
            data: {
                "daterange": daterange,
                "karyawan": karyawan
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
                data: 'waktu',
                name: 'waktu'
            },
            {
                data: 'user.name',
                name: 'user.name'
            },
        ]
    });

    $("#daterange").daterangepicker({
        opens: "right",
        format: "MM/DD/YYYY",
        separator: " to ",
        startDate: moment("{{ $start }}"),
        endDate: moment("{{ $end }}"),
        minDate: "01/01/2024",
        maxDate: "12/31/2045",
    }, function(start, end) {
        $("#daterange input").val(start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY"));
    });
</script>
@endpush