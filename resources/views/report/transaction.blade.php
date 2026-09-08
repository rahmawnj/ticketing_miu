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
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="daterange">Tanggal</label>
                        <input type="text" name="daterange" id="daterange" class="form-control"
                               value="{{ request('daterange') ?: now('Asia/Jakarta')->format('m/d/Y') . ' - ' . now('Asia/Jakarta')->format('m/d/Y') }}">
                        <input type="hidden" name="from" id="from" value="{{ request('from') }}">
                        <input type="hidden" name="to" id="to" value="{{ request('to') }}">
                    </div>
                </div>
                <div class="col-md-2">
                   <div class="form-group me-3">
            <label for="transaction_type">Transaction Type</label>
            <select name="transaction_type" id="transaction_type" class="form-control">
                <option value="">All</option>
                @php
                    $types = ['renewal', 'ticket', 'registration', 'rental'];
                @endphp
                @foreach($types as $type)
                    <option value="{{ $type }}" {{ request('transaction_type') == $type ? 'selected' : '' }}>
                        {{ ucfirst($type) }}
                    </option>
                @endforeach
            </select>
        </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="kasir">Kasir</label>
                        <select name="kasir" id="kasir" class="form-control">
                            <option value="all" {{ request('kasir', 'all') == 'all' ? 'selected' : '' }}>All</option>
                            @foreach($users as $user)
                            <option {{ request('kasir') == $user->id ? 'selected' : '' }} value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group mt-1 d-flex gap-2 flex-nowrap">
                        <button type="submit" class="btn btn-primary mt-3">Submit</button>
                        <a href="{{ route('report.transaction.export') }}?from={{ request('from') }}&to={{ request('to') }}&kasir={{ request('kasir') }}&transaction_type={{ request('transaction_type') }}" class="btn btn-success mt-3"><i class="fas fa-file-excel me-1"></i>Download</a>
                        <a href="#" id="btn-download-txt" class="btn btn-dark mt-3"><i class="fas fa-file-alt me-1"></i>Download TXT</a>
                    </div>
                </div>
            </div>
        </form>
        <table id="datatable" class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th class="text-nowrap">No</th>
                    <th class="text-nowrap">Tanggal</th>
                    <th class="text-nowrap">Kasir</th>
                    <th class="text-nowrap">Transaction Type</th>
                    <th class="text-nowrap">Ticket Code</th>
                    <th class="text-nowrap">Keterangan Produk</th>
                    <th class="text-nowrap">Metode</th>
                    <th class="text-nowrap">Amount</th>
                    <th class="text-nowrap">Jumlah</th>
                    <th class="text-nowrap">Total</th>
                    <th class="text-nowrap">PBJT</th>
                    <th class="text-nowrap">Biaya Admin</th>
                    <th class="text-nowrap">Discount</th>
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
    function syncReportDateRangeFields() {
        const rangeValue = $("#daterange").val() || '';
        if (rangeValue.includes(' - ')) {
            const parts = rangeValue.split(' - ');
            const start = moment(parts[0], 'MM/DD/YYYY', true);
            const end = moment(parts[1], 'MM/DD/YYYY', true);
            if (start.isValid() && end.isValid()) {
                $("#from").val(start.format('YYYY-MM-DD'));
                $("#to").val(end.format('YYYY-MM-DD'));
                return;
            }
        }

        const today = moment().format('YYYY-MM-DD');
        $("#from").val(today);
        $("#to").val(today);
    }

    (function initDateRange() {
        const today = moment();
        let start = today.clone();
        let end = today.clone();
        const rangeValue = $("#daterange").val();

        if (rangeValue && rangeValue.includes(' - ')) {
            const parts = rangeValue.split(' - ');
            const parsedStart = moment(parts[0], 'MM/DD/YYYY', true);
            const parsedEnd = moment(parts[1], 'MM/DD/YYYY', true);
            if (parsedStart.isValid() && parsedEnd.isValid()) {
                start = parsedStart;
                end = parsedEnd;
            }
        } else if ($("#from").val() && $("#to").val()) {
            const parsedFrom = moment($("#from").val(), 'YYYY-MM-DD', true);
            const parsedTo = moment($("#to").val(), 'YYYY-MM-DD', true);
            if (parsedFrom.isValid() && parsedTo.isValid()) {
                start = parsedFrom;
                end = parsedTo;
            }
        }

        $("#daterange").daterangepicker({
            opens: "right",
            autoUpdateInput: true,
            locale: {
                format: "MM/DD/YYYY",
                separator: " - "
            },
            startDate: start,
            endDate: end
        });

        $("#daterange").val(start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY'));
        syncReportDateRangeFields();

        $("#daterange").on('apply.daterangepicker', function() {
            syncReportDateRangeFields();
        });
    })();

    function buildTxtDownloadUrl() {
        syncReportDateRangeFields();
        const params = new URLSearchParams({
            from: $("#from").val() || '',
            to: $("#to").val() || '',
            kasir: $("#kasir").val() || 'all',
            transaction_type: $("#transaction_type").val() || ''
        });

        return "{{ route('report.transaction.export.txt') }}" + "?" + params.toString();
    }

    $("#btn-download-txt").on('click', function(e) {
        e.preventDefault();
        window.location.href = buildTxtDownloadUrl();
    });

    let from = $("#from").val();
    let to = $("#to").val();
    let kasir = $("#kasir").val();
    let transactionType = $("#transaction_type").val();

    var table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('reports.transaction-list') }}",
            type: "GET",
            data: {
                "from": from,
                "to": to,
                "kasir": kasir,
                "transaction_type": transactionType,

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
                data: 'tanggal',
                name: 'tanggal'
            },
            { data: 'kasir', name: 'user.name' },
            { data: 'transaction_type_label', name: 'transaction_type' },
            {
                data: 'ticket_code',
                name: 'ticket_code'
            },
            {
                data: 'keterangan_produk',
                name: 'keterangan_produk',
                searchable: false,
                sortable: false
            },
            {
                data: 'metode',
                name: 'metode'
            },
            {
                data: 'amount',
                name: 'amount',
            },
            {
                data: 'harga',
                name: 'harga'
            },
            {
                data: 'harga_ticket',
                name: 'harga_ticket',
            },
            {
                data: 'ppn',
                name: 'ppn'
            },
            {
                data: 'admin_fee',
                name: 'admin_fee'
            },
            {
                data: 'discount',
                name: 'discount',
            },
        ]
    });

    $("#btn-add").on('click', function() {
        let route = $(this).attr('data-route')
        $("#form-sewa").attr('action', route)
    })

    $("#btn-close").on('click', function() {
        $("#form-sewa").removeAttr('action')
    })

    $("#datatable").on('click', '.btn-edit', function() {
        let route = $(this).attr('data-route')
        let id = $(this).attr('id')

        $("#form-sewa").attr('action', route)
        $("#form-sewa").append(`<input type="hidden" name="_method" value="PUT">`);

        $.ajax({
            url: "/sewa/" + id,
            type: 'GET',
            method: 'GET',
            success: function(response) {
                let sewa = response.sewa;

                $("#name").val(sewa.name)
                $("#harga").val(sewa.harga)
                $("#device").val(sewa.device)
            }
        })
    })

    $("#datatable").on('click', '.btn-delete', function(e) {
        e.preventDefault();
        let route = $(this).attr('data-route')
        $("#form-delete").attr('action', route)

        swal({
            title: 'Hapus data ticket?',
            text: 'Menghapus ticket bersifat permanen.',
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
