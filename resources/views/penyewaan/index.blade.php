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
        <form action="" class="row mb-3">
            <div class="form-group col-md-3">
                <label for="tanggal">Tanggal</label>
                <input type="date" name="tanggal" id="tanggal" class="form-control" value="{{ request('tanggal') ?? Carbon\Carbon::now()->format('Y-m-d') }}">
            </div>

            <div class="form-group col-md-3 mt-1">
                <button type="submit" class="btn btn-success mt-3">Submit</button>
                {{-- <a href="#modal-dialog" id="btn-add" class="btn btn-primary mt-3" data-route="{{ route('penyewaan.store') }}" data-bs-toggle="modal"><i class="ion-ios-add"></i> Add Penyewaan</a> --}}
            </div>
        </form>

        <table id="datatable" class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th class="text-nowrap">No</th>
                    <th class="text-nowrap">Nama Transaksi Lainnya</th>
                    <th class="text-nowrap">Harga</th>
                    <th class="text-nowrap">Qty</th>
                    <th class="text-nowrap">Metode</th>
                    <th class="text-nowrap">Total</th>
                    <th class="text-nowrap">Description</th>
                    <th class="text-nowrap">Start Time</th>
                    <th class="text-nowrap">End Time</th>
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
                    <h4 class="modal-title">Form Transaksi Lainnya</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form action="" method="post" id="form-penyewaan">
                    @csrf

                    <div class="modal-body row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="sewa">Jenis Lainnya</label>
                                <select name="ticket" id="ticket" class="form-control">
                                    <option disabled selected>-- Select Transaksi Lainnya --</option>
                                    @foreach($tickets as $ticket)
                                    <option value="{{ $ticket->id }}" data-harga="{{ $ticket->harga }}">{{ $ticket->name }}</option>
                                    @endforeach
                                </select>

                                @error('ticket')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="qty">Qty</label>
                                <input type="number" name="qty" id="qty" class="form-control" value="1">

                                @error('qty')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="harga_ticket">Harga Lainnya</label>
                                <input type="text" name="harga_ticket" id="harga_ticket" class="form-control" value="" readonly>

                                @error('harga_ticket')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group mb-3 bayar d-none">
                                <label for="bayar">Bayar</label>
                                <input type="text" name="bayar" id="bayar" class="form-control" value="0">

                                @error('bayar')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="metode">Metode</label>
                                <select name="metode" id="metode" class="form-control">
                                    <option disabled selected>-- Pilih Metode --</option>
                                    @foreach(\App\Support\PaymentMethod::options() as $methodValue => $methodLabel)
                                    <option value="{{ $methodValue }}">{{ $methodLabel }}</option>
                                    @endforeach
                                </select>

                                @error('metode')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="name">RFID</label>
                                <input type="text" name="name" id="name" class="form-control" value="" readonly>

                                @error('name')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="sisa">Saldo</label>
                                <input type="text" name="sisa" id="sisa" class="form-control" value="0" readonly>

                                @error('sisa')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group mb-3 kembali d-none">
                                <label for="kembali">Kembali</label>
                                <input type="text" name="kembali" id="kembali" class="form-control" value="0" readonly>

                                @error('kembali')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label for="jumlah">Jumlah</label>
                                <input type="text" name="jumlah" id="jumlah" class="form-control" value="0" readonly>

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

<script>
    let tanggal = $("#tanggal").val();

    var table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('penyewaan.list') }}",
            type: "GET",
            data: {
                "tanggal": tanggal,
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
                data: 'ticket',
                name: 'ticket'
            },
            {
                data: 'harga',
                name: 'harga'
            },
            {
                data: 'qty',
                name: 'qty'
            },
            {
                data: 'metode',
                name: 'metode'
            },
            {
                data: 'jumlah',
                name: 'jumlah'
            },
            {
                data: 'keterangan',
                name: 'keterangan'
            },
            {
                data: 'start_time',
                name: 'start_time'
            },
            {
                data: 'end_time',
                name: 'end_time'
            },
            {
                data: 'action',
                name: 'action',
            },
        ]
    });

    $("#btn-add").on('click', function() {
        let route = $(this).attr('data-route')
        $("#form-penyewaan").attr('action', route)
    })

    $("#btn-close").on('click', function() {
        $("#form-penyewaan").removeAttr('action')
    })

    $("#datatable").on('click', '.btn-edit', function() {
        let route = $(this).attr('data-route')
        let id = $(this).attr('id')

        $("#form-penyewaan").attr('action', route)
        $("#form-penyewaan").append(`<input type="hidden" name="_method" value="PUT">`);

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
            title: 'Hapus data transaksi lainnya?',
            text: 'Menghapus transaksi lainnya bersifat permanen.',
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
        const frame = document.getElementById('ticket-preview-frame');
        if (frame && frame.contentWindow) {
            try {
                frame.contentWindow.focus();
                frame.contentWindow.print();
                return;
            } catch (e) {}
        }
        const url = $(this).attr('data-print-url');
        if (!url) return;
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
</script>

<script>
    $(document).ready(function() {
        // $("#btn-add").on('click', function() {
        //     $.ajax({
        //         url: '/api/penyewaan/no-trx',
        //         type: "GET",
        //         method: "GET",
        //         success: function(response) {
        //             $("#no_trx").val(response.no_trx)
        //         }
        //     })

        //     $("#name").attr("autofocus", "autofocus")
        // })

        $("#ticket").on('change', function() {
            let element = $(this).find('option:selected');
            let harga = element.attr("data-harga");
            let amount = $("#qty").val();
            let harga_ticket = harga * amount;
            let jumlah = (harga * amount);

            $("#harga_ticket").val((harga_ticket / 1000).toFixed(3))
            $("#jumlah").val((jumlah / 1000).toFixed(3))
            $("#cash").val((jumlah / 1000).toFixed(3))
            $("#bayar").val((jumlah / 1000).toFixed(3))
        })

        $("#metode").on('change', function() {
            let metode = $(this).val()
            if (metode == 'tap') {
                $("#name").removeAttr('readonly');
                $(".bayar").addClass('d-none');
                $(".kembali").addClass('d-none');
            } else {
                $("#name").attr("readonly", "readonly")
                $(".bayar").removeClass('d-none');
                $(".kembali").removeClass('d-none');
            }
        })

        $("#qty").on('change', function() {
            let amount = $(this).val();
            let harga = $('#ticket option:selected').attr('data-harga');
            let harga_ticket = harga * amount;

            let jumlah = (harga * amount);
            // $("#harga_ticket").val(harga_ticket)
            $("#jumlah").val((jumlah / 1000).toFixed(3))
            $("#cash").val((jumlah / 1000).toFixed(3))
            $("#bayar").val((jumlah / 1000).toFixed(3))

        })

        $("#discount").on('change', function() {
            let discount = $(this).val();
            let harga = $("#harga_ticket").val();
            let jumlah = harga - discount;

            $("#jumlah").val("")
            $("#jumlah").val(jumlah)
            $("#cash").val(jumlah)
        })

        $('#form-penyewaan').on('keyup keypress', function(e) {
            var keyCode = e.keyCode || e.which;
            if (keyCode === 13) {
                e.preventDefault();
                return false;
            }
        });

        $("#name").on('keypress', function(e) {
            if (e.which == 13) {
                let rfid = $(this).val();

                $.ajax({
                    url: "/api/members",
                    type: "GET",
                    method: "GET",
                    data: {
                        rfid: rfid
                    },
                    success: function(response) {
                        if (response.status == 'success') {
                            let member = response.member;
                            // $('#form-penyewaan').submit()
                            $("#sisa").val(member.saldo)
                        } else {
                            $("#rfid").val("")

                        }

                    },
                    error: function(response) {
                        $("#rfid").val("")
                    }
                })
            }
        })

        $("#bayar").on('change', function() {
            let bayar = $(this).val().replace('.', '');
            let price = $("#jumlah").val().replace('.', '')
            let kembali = parseInt(bayar - price)
            $("#kembali").val((kembali / 1000).toFixed(3))
        })

        var rupiah = document.getElementById('bayar');
        rupiah.addEventListener('keyup', function(e) {
            // tambahkan 'Rp.' pada saat form di ketik
            // gunakan fungsi formatRupiah() untuk mengubah angka yang di ketik menjadi format angka
            rupiah.value = formatRupiah(this.value);
        });

        /* Fungsi formatRupiah */
        function formatRupiah(angka) {
            var number_string = angka.replace(/[^,\d]/g, '').toString(),
                split = number_string.split(','),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            // tambahkan titik jika yang di input sudah menjadi angka ribuan
            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            return undefined ? rupiah : (rupiah ? rupiah : '');
        }
    })
</script>
@endpush
