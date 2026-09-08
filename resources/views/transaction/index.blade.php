@extends('layouts.master', ['title' => $title, 'breadcrumbs' => $breadcrumbs])

@push('style')
<link href="{{ asset('/') }}plugins/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/select2/dist/css/select2.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet" />
<style>
    #datatable tbody img {
        cursor: zoom-in;
        transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    #datatable tbody img:hover {
        transform: scale(1.03);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }
    .trx-color-legend .badge {
        margin-right: 6px;
    }
</style>
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
    <form action="" class="mb-3">
        {{-- Baris 1: Tombol aksi kanan dipindah ke atas --}}
        <div class="row">
            <div class="col-12 d-flex justify-content-end flex-wrap gap-2 mb-2">
                <a href="{{ route('members.create') }}" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Registrasi Member
                </a>
                <a href="{{ route('penyewaan.create') }}" class="btn btn-secondary">
                    <i class="fas fa-shopping-cart"></i> Input Transaksi Lainnya
                </a>
                <a href="{{ route('transactions.create') }}" class="btn btn-primary">
                    <i class="fas fa-ticket-alt"></i> Input Ticket
                </a>
                <a href="{{ route('members.bulk_renew') }}" class="btn btn-warning">
                    <i class="fas fa-history"></i> Renewal
                    <span class="badge bg-dark ms-1">{{ $renewalCount ?? 0 }}</span>
                </a>
            </div>
        </div>

        {{-- Baris 2: Filter data --}}
        <div class="row">
            <div class="col-12 d-flex align-items-end flex-wrap">
                <div class="form-group me-3 mb-2">
                    <label for="daterange">Tanggal</label>
                    <input type="text" name="daterange" id="daterange" class="form-control"
                           value="{{ request('daterange') ?: now('Asia/Jakarta')->format('m/d/Y') . ' - ' . now('Asia/Jakarta')->format('m/d/Y') }}">
                </div>
                <div class="form-group me-3 mb-2">
                    <label for="transaction_type">Jenis Transaksi</label>
                    <select name="transaction_type" id="transaction_type" class="form-control">
                        <option value="">Semua</option>
                        @php
                            $types = ['membership', 'renewal', 'ticket', 'registration', 'rental'];
                        @endphp
                        @foreach($types as $type)
                            <option value="{{ $type }}" {{ request('transaction_type') == $type ? 'selected' : '' }}>
                                {{ $type === 'membership' ? 'Membership' : ucfirst($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group me-3 mb-2">
                    <label for="detail_master_id">Nama Detail</label>
                    <select name="detail_master_id" id="detail_master_id" class="form-control">
                        <option value="">Semua Nama Detail</option>
                        <optgroup label="Data Ticket">
                            @foreach(($detailFilterOptions['ticket'] ?? []) as $option)
                            <option value="{{ $option['value'] }}" {{ request('detail_master_id') == $option['value'] ? 'selected' : '' }}>
                                {{ $option['text'] }}
                            </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Data Lain-lain">
                            @foreach(($detailFilterOptions['rental'] ?? []) as $option)
                            <option value="{{ $option['value'] }}" {{ request('detail_master_id') == $option['value'] ? 'selected' : '' }}>
                                {{ $option['text'] }}
                            </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Data Membership">
                            @foreach(($detailFilterOptions['membership'] ?? []) as $option)
                            <option value="{{ $option['value'] }}" {{ request('detail_master_id') == $option['value'] ? 'selected' : '' }}>
                                {{ $option['text'] }}
                            </option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-success mt-3">Submit</button>
                </div>
                <div class="form-group ms-2 mb-2">
                    <a href="#" id="btn-export-excel" class="btn btn-outline-success mt-3">
                        <i class="fas fa-file-excel"></i> Download Excel
                    </a>
                </div>
            </div>
        </div>
    </form>
        <div class="trx-color-legend small text-muted mb-2">
            Warna Type:
            <span class="badge bg-primary">Ticket</span>
            <span class="badge bg-warning text-dark">Penyewaan</span>
            <span class="badge bg-success">Membership</span>
            <span class="badge bg-info text-dark">Renewal</span>
        </div>

        <table id="datatable" class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th class="text-nowrap">No</th>
                    <th class="text-nowrap">Tanggal</th>
                    <!-- <th class="text-nowrap">No Trx</th> -->
                    <th class="text-nowrap">Invoice</th>
                    <th class="text-nowrap">Dibuat Oleh (Kasir)</th>
                    <th class="text-nowrap">Data Member</th>
                    <!-- <th class="text-nowrap">Ticket</th> -->
                    <!-- <th class="text-nowrap">Harga</th> -->
                    <th class="text-nowrap">Jenis Transaksi</th>
                    <th class="text-nowrap">Detail Transaksi</th>
                    <th class="text-nowrap">Qty</th>
                    <th class="text-nowrap">Scanned</th>
                    <th class="text-nowrap">Bayar</th>
                    <th class="text-nowrap">PBJT</th>
                    <th class="text-nowrap">Discount</th>
                    <th class="text-nowrap">Total</th>
                    <th class="text-nowrap">Status</th>
                    <th class="text-nowrap">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="modal fade" id="modal-dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Form Transaction</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form action="" method="post" id="form-transaction">
                    @csrf

                    <div class="modal-body row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="no_trx">No Transaksi</label>
                                <input type="number" name="no_trx" id="no_trx" class="form-control" readonly value="">

                                @error('no_trx')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="name">Name</label>
                                <input type="text" name="name" id="name" class="form-control" value="">

                                @error('name')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="ticket">Ticket</label>
                                <select name="ticket" id="ticket" class="form-control">
                                    <option disabled selected>-- Select Ticket --</option>
                                    @foreach($tickets as $ticket)
                                    <option value="{{ $ticket->id }}" data-harga="{{ $ticket->harga }}">{{ $ticket->name }}</option>
                                    @endforeach
                                </select>

                                @error('ticket')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="amount">Jumlah</label>
                                <input type="number" name="amount" id="amount" class="form-control" value="1">

                                @error('amount')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="print">Jumlah Print</label>
                                <input type="number" name="print" id="print" class="form-control" readonly value="1">

                                @error('print')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="harga_ticket">Harga Tiket</label>
                                <input type="number" name="harga_ticket" id="harga_ticket" class="form-control" value="" readonly>

                                @error('harga_ticket')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="discount">Discount</label>
                                <input type="number" name="discount" id="discount" class="form-control" value="0">

                                @error('discount')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="metode">Metode</label>
                                <select name="metode" id="metode" class="form-control">
                                    @foreach(\App\Support\PaymentMethod::options() as $methodValue => $methodLabel)
                                    <option value="{{ $methodValue }}">{{ $methodLabel }}</option>
                                    @endforeach
                                </select>

                                @error('metode')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="cash">Cash</label>
                                <input type="number" name="cash" id="cash" class="form-control" value="">

                                @error('cash')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="kembalian">Kembalian</label>
                                <input type="number" name="kembalian" id="kembalian" class="form-control" value="0" readonly>

                                @error('kembalian')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label for="jumlah">Jumlah</label>
                                <input type="number" name="jumlah" id="jumlah" class="form-control" value="0" readonly>

                                @error('jumlah')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
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
</div>


<div class="modal fade" id="modal-full-scan">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Konfirmasi Selesaikan Scan</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <form action="" method="post" id="form-full-scan">
                @csrf
                <div class="modal-body">
                    <p>Anda yakin ingin menandai transaksi <strong id="trx-code-placeholder"></strong> sebagai **Full Scanned**?</p>
                    <p class="text-danger">Aksi ini akan mengatur jumlah scan menjadi sama dengan total kuantitas tiket.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-white" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Ya, Selesaikan Scan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-scan-detail" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detail Scan Tiket</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2 text-muted small">Invoice: <span id="scan-detail-code">-</span></div>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap">ID/Code</th>
                                <th>Ticket</th>
                                <th class="text-nowrap">Qty</th>
                                <th class="text-nowrap">Scanned</th>
                                <th class="text-nowrap">Allowed</th>
                                <th class="text-nowrap">Sisa</th>
                            </tr>
                        </thead>
                        <tbody id="scan-detail-body">
                            <tr>
                                <td colspan="6" class="text-center text-muted">Memuat...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-photo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Foto</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modal-photo-img" src="" alt="Foto" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-ticket-preview" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-scrollable" role="document" style="max-width: 560px;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Preview</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="ticket-preview-frame" src="about:blank" style="width:100%; height:60vh; border:0; background:#f8f9fa;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="btn-ticket-print">Print</button>
                <button type="button" class="btn btn-success" id="btn-ticket-download">Download PDF</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script src="{{ asset('/') }}plugins/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
<script src="{{ asset('/') }}plugins/sweetalert/dist/sweetalert.min.js"></script>
<script src="{{ asset('/') }}plugins/select2/dist/js/select2.min.js"></script>
<script src="{{ asset('/') }}plugins/moment/min/moment.min.js"></script>
<script src="{{ asset('/') }}plugins/bootstrap-daterangepicker/daterangepicker.js"></script>

<script>
    var table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('transactions.list') }}",
            type: "GET",
            data: function(d) {
                d.daterange = $("#daterange").val() || "";
                d.transaction_type = $("#transaction_type").val() || "";
                d.detail_master_id = $("#detail_master_id").val() || "";
            }
        },
        deferRender: true,
        pagination: true,
        columns: [{
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                sortable: false,
                searchable: false
            },
            {
                data: 'tanggal',
                name: 'created_at'
            },
            {
                data: 'ticket_code',
                name: 'ticket_code'
            },
            {
        data: 'user_name',
        name: 'user_name', // Kolom turunan dari relasi, non-searchable agar tidak memicu SQL unknown column
        searchable: false,
        sortable: false
    },
            {
                data: 'member_info',
                name: 'member_info',
                searchable: false,
                sortable: false
            },
            {
                data: 'transaction_type_badge',
                name: 'transaction_type',
            },
            {
                data: 'detail_description',
                name: 'detail_description',
                sortable: false,
                searchable: false
            },
            {
                data: 'qty',
                name: 'qty',
                sortable: false,
                searchable: false
            },
            {
                data: 'scanned',
                name: 'scanned',
                sortable: false,
                searchable: false
            },
            {
                data: 'bayar',
                name: 'bayar'
            },

            {
                data: 'ppn',
                name: 'ppn'
            },
            {
                data: 'discount',
                name: 'discount'
            },
            {
                data: 'harga_ticket',
                name: 'harga_ticket',
                searchable: false,
                sortable: false
            },
            {
                data: 'status_ticket',
                name: 'status_ticket',
                searchable: false,
                sortable: false
            },
            {
                data: 'action',
                name: 'action',
                searchable: false,
                sortable: false
            },
        ]
    });

    (function initDateRange() {
        let today = moment();
        let rangeValue = $("#daterange").val();
        let start = today.clone();
        let end = today.clone();

        if (rangeValue && rangeValue.includes(' - ')) {
            let parts = rangeValue.split(' - ');
            let parsedStart = moment(parts[0], 'MM/DD/YYYY', true);
            let parsedEnd = moment(parts[1], 'MM/DD/YYYY', true);
            if (parsedStart.isValid() && parsedEnd.isValid()) {
                start = parsedStart;
                end = parsedEnd;
            }
        }

        $("#daterange").daterangepicker({
            opens: "right",
            autoUpdateInput: true,
            locale: {
                format: "MM/DD/YYYY",
                separator: " - ",
                applyLabel: "Terapkan",
                cancelLabel: "Batal",
                customRangeLabel: "Pilih Manual"
            },
            startDate: start,
            endDate: end,
            ranges: {
                "Hari Ini": [moment(), moment()],
                "Kemarin": [moment().subtract(1, "days"), moment().subtract(1, "days")],
                "Minggu Ini": [moment().startOf("isoWeek"), moment().endOf("isoWeek")],
                "7 Hari Terakhir": [moment().subtract(6, "days"), moment()],
                "30 Hari Terakhir": [moment().subtract(29, "days"), moment()],
                "Bulan Ini": [moment().startOf("month"), moment().endOf("month")],
                "Tahun Ini": [moment().startOf("year"), moment().endOf("year")],
            }
        });

        $("#daterange").val(start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY'));
    })();

    $("#btn-add").on('click', function() {
        let route = $(this).attr('data-route')
        $("#form-transaction").attr('action', route)
    })

    $("#btn-close").on('click', function() {
        $("#form-transaction").removeAttr('action')
    })

    $("#datatable").on('click', '.btn-edit', function() {
        let route = $(this).attr('data-route')
        let id = $(this).attr('id')

        $("#form-transaction").attr('action', route)
        $("#form-transaction").append(`<input type="hidden" name="_method" value="PUT">`);

        $.ajax({
            url: "/tickets/" + id,
            type: 'GET',
            method: 'GET',
            success: function(response) {
                let ticket = response.ticket;

                $("#name").val(ticket.name)
                $("#harga").val(ticket.harga)
            }
        })
    })

    $("#datatable").on('click', '.btn-delete', function(e) {
        e.preventDefault();
        let route = $(this).attr('data-route')
        $("#form-delete").attr('action', route)

        swal({
            title: 'Hapus data transaction?',
            text: 'Menghapus transaction bersifat permanen.',
            icon: 'error',
            buttons: {
                cancel: {
                    text: 'Batal',
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

<script>
    $(document).ready(function() {
        $("#transaction_type, #detail_master_id").select2({
            width: '100%'
        });

        function syncTransactionTypeByDetail(shouldReload = true) {
            const selectedDetail = ($("#detail_master_id").val() || "").toLowerCase();
            const currentType = ($("#transaction_type").val() || "").toLowerCase();
            let expectedType = '';

            if (selectedDetail.startsWith('ticket:')) {
                expectedType = 'ticket';
            } else if (selectedDetail.startsWith('rental:')) {
                expectedType = 'rental';
            } else if (selectedDetail.startsWith('membership:')) {
                expectedType = 'membership';
            }

            if (expectedType !== currentType) {
                $("#transaction_type").val(expectedType).trigger('change');
                if (shouldReload && typeof table !== 'undefined') {
                    table.ajax.reload();
                }
            }
        }

        syncTransactionTypeByDetail(false);

        $("#detail_master_id").on('change', function() {
            syncTransactionTypeByDetail();
        });

        $("#btn-add").on('click', function() {
            $.ajax({
                url: '/api/transactions/no-trx',
                type: "GET",
                method: "GET",
                success: function(response) {
                    $("#no_trx").val(response.no_trx)
                }
            })

            $("#name").attr("autofocus", "autofocus")
        })

        $("#ticket").on('change', function() {
            let element = $(this).find('option:selected');
            let harga = element.attr("data-harga");
            let amount = $("#amount").val();
            let discount = $("#discount").val()
            let harga_ticket = harga * amount;
            let jumlah = (harga * amount) - discount;

            $("#harga_ticket").val(harga_ticket)
            $("#jumlah").val(jumlah)
            $("#cash").val(jumlah)
        })

        $("#amount").on('change', function() {
            let amount = $(this).val();
            let harga = $('#ticket option:selected').attr('data-harga');
            let type = $('#type_customer option:selected').val();
            let discount = $("#discount").val()
            let harga_ticket = harga * amount;
            $("#print").val(amount)

            // if (type == 'group') {
            let jumlah = (harga * amount) - discount;
            $("#harga_ticket").val(harga_ticket)
            $("#jumlah").val(jumlah)
            $("#cash").val(jumlah)
            // } else {
            //     let jumlah = harga - discount;
            //     $("#harga_ticket").val(harga)
            //     $("#jumlah").val(jumlah)
            //     $("#cash").val(jumlah)
            // }

        })

        $("#discount").on('change', function() {
            let discount = $(this).val();
            let harga = $("#harga_ticket").val();
            let jumlah = harga - discount;

            $("#jumlah").val("")
            $("#jumlah").val(jumlah)
            $("#cash").val(jumlah)
        })

        $("#type_customer").on('change', function() {
            let type = $(this).val();

            // if (type == 'group') {
            //     $("#print").removeAttr('readonly')
            // } else {
            //     $("#amount").val(1)
            //     $("#print").val(1)
            //     $("#print").attr('readonly', 'readonly')
            // }
        })

        $("#metode").on('change', function() {
            let metode = $(this).val();

            if (metode != 'cash') {
                $("#cash").val(0);
                $("#cash").attr('readonly', 'readonly')
            } else {
                $("#cash").removeAttr('readonly')
            }
        })

        $("#metode").trigger('change')
    })

    $("#datatable").on('click', '.btn-full-scan', function(e) {
        e.preventDefault();
        let route = $(this).attr('data-route');
        let ticketCode = $(this).attr('data-ticket-code');

        // Set action form dan placeholder
        $("#form-full-scan").attr('action', route);
        $("#form-full-scan").append(`<input type="hidden" name="_method" value="POST">`); // Asumsi Anda menggunakan POST/PUT di route controller
        $("#trx-code-placeholder").text(ticketCode);

        // Tampilkan modal
        $('#modal-full-scan').modal('show');
    });

    $("#datatable").on('click', '.btn-scan-detail', function(e) {
        e.preventDefault();
        const transactionId = $(this).attr('data-transaction-id');
        const ticketCode = $(this).attr('data-ticket-code') || '-';
        $("#scan-detail-code").text(ticketCode);
        $("#scan-detail-body").html('<tr><td colspan="6" class="text-center text-muted">Memuat...</td></tr>');

        if (!transactionId) {
            $("#scan-detail-body").html('<tr><td colspan="6" class="text-center text-danger">ID transaksi tidak ditemukan.</td></tr>');
            $('#modal-scan-detail').modal('show');
            return;
        }

        $.ajax({
            url: "{{ url('transactions') }}/" + transactionId + "/scan-details",
            type: "GET",
            success: function(response) {
                if (!response || response.status !== 'success') {
                    $("#scan-detail-body").html('<tr><td colspan="6" class="text-center text-danger">Gagal memuat data.</td></tr>');
                    return;
                }

                const dataRows = response.data || [];
                const groups = {};

                dataRows.forEach(function(row) {
                    const name = row.ticket_name ?? '-';
                    if (!groups[name]) {
                        groups[name] = [];
                    }
                    groups[name].push(row);
                });

                const rows = [];
                Object.keys(groups).forEach(function(name) {
                    const items = groups[name];
                    const span = items.length || 1;

                    items.forEach(function(row, index) {
                        const ticketCell = index === 0 ? `<td rowspan="${span}">${name}</td>` : '';
                        rows.push(`<tr>
                            <td class="text-nowrap">${row.ticket_code ?? '-'}</td>
                            ${ticketCell}
                            <td class="text-center">${row.qty ?? 0}</td>
                            <td class="text-center">${row.scanned ?? 0}</td>
                            <td class="text-center">${row.allowed ?? 0}</td>
                            <td class="text-center">${row.remaining ?? 0}</td>
                        </tr>`);
                    });
                });

                $("#scan-detail-body").html(rows.length ? rows.join('') : '<tr><td colspan="6" class="text-center text-muted">Tidak ada data tiket.</td></tr>');
            },
            error: function() {
                $("#scan-detail-body").html('<tr><td colspan="6" class="text-center text-danger">Gagal memuat data.</td></tr>');
            }
        });

        $('#modal-scan-detail').modal('show');
    });

    $("#datatable").on('click', 'tbody img', function() {
        let src = $(this).attr('data-full') || $(this).attr('src');
        if (!src) return;

        $("#modal-photo-img").attr('src', src);
        $('#modal-photo').modal('show');
    });

    $("#datatable").on('click', '.btn-ticket-preview', function(e) {
        e.preventDefault();
        const previewUrl = $(this).attr('data-preview-url');
        const printUrl = $(this).attr('data-print-url');
        const pdfUrl = $(this).attr('data-pdf-url');

        $("#ticket-preview-frame").attr('src', previewUrl || 'about:blank');
        $("#btn-ticket-print").attr('data-print-url', printUrl || '');
        $("#btn-ticket-download").attr('data-pdf-url', pdfUrl || '');

        $('#modal-ticket-preview').modal('show');
    });

    $("#btn-ticket-print").on('click', function() {
        const url = $(this).attr('data-print-url');
        if (!url) return;
        const frame = document.getElementById('ticket-preview-frame');
        if (frame && frame.contentWindow) {
            try {
                frame.contentWindow.focus();
                frame.contentWindow.print();
                return;
            } catch (e) {}
        }
        window.open(url, '_blank');
    });

    $("#btn-ticket-download").on('click', function() {
        const url = $(this).attr('data-pdf-url');
        if (!url) return;
        window.open(url, '_blank');
    });

    $('#modal-ticket-preview').on('hidden.bs.modal', function() {
        $("#ticket-preview-frame").attr('src', 'about:blank');
        $("#btn-ticket-print").attr('data-print-url', '');
        $("#btn-ticket-download").attr('data-pdf-url', '');
    });

    $("#btn-export-excel").on('click', function(e) {
        e.preventDefault();
        const params = new URLSearchParams({
            daterange: $("#daterange").val() || "",
            transaction_type: $("#transaction_type").val() || "",
            detail_master_id: $("#detail_master_id").val() || ""
        });
        window.location.href = "{{ route('transactions.export.daily') }}" + "?" + params.toString();
    });
</script>
@endpush

