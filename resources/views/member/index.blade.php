@extends('layouts.master', ['title' => $title, 'breadcrumbs' => $breadcrumbs])

@push('style')
<link href="{{ asset('/') }}plugins/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />

<style>
    .btn-expired {
        cursor: pointer;
    }
    /* Menambahkan styling untuk tombol filter */
    .btn-filter.active {
        background-color: #007bff; /* Primary color */
        color: white;
    }
    #datatable tbody .js-photo-thumb {
        cursor: zoom-in;
        transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    #datatable tbody .js-photo-thumb:hover {
        transform: scale(1.03);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }
    #modal-photo .modal-dialog {
        max-width: 680px;
    }
    #modal-photo-img {
        max-height: 55vh;
        width: 100%;
        object-fit: cover;
    }
    .photo-meta {
        text-align: left;
    }
    .photo-meta .label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6c757d;
    }
    .photo-meta .value {
        font-weight: 600;
        color: #111;
    }
    #datatable td.text-nowrap,
    #datatable th.text-nowrap {
        white-space: nowrap;
    }
    .member-action-buttons .btn {
        margin: 0 !important;
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
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('members.create') }}" class="btn btn-primary"><i class="ion-ios-add"></i> Add Member</a>
                <div class="btn-group" role="group" aria-label="Import Export Member">
                    <a href="#modal-dialog-import" class="btn btn-success" data-bs-toggle="modal"><i class="ion-ios-document"></i> Import Member</a>
                    <a href="{{ route('members.export') }}" id="btn-export-member" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Export Member
                    </a>
                </div>
                @include("member.import")
            </div>

            <div class="d-flex align-items-center gap-2">
                <select id="filter-membership" class="form-control">
                    <option value="0" selected>Semua Membership</option>
                    @foreach ($memberships as $membership)
                        <option value="{{ $membership->id }}">{{ $membership->name }}</option>
                    @endforeach
                </select>
                <select id="filter-status" class="form-control">
                    <option value="all" selected>Semua Status</option>
                    <option value="active">Active</option>
                    <option value="expired">Expired</option>
                </select>
                <div class="btn-group" role="group" aria-label="Filter Member">
                    <button type="button" class="btn btn-default btn-filter active" data-filter="member">Member</button>
                    <button type="button" class="btn btn-default btn-filter" data-filter="submember">Submember</button>
                    <button type="button" class="btn btn-default btn-filter" data-filter="all">All</button>
                </div>
            </div>
        </div>

        <table id="datatable" class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th class="text-nowrap">No</th>
                    <th class="text-nowrap">Foto</th>
                    <th class="text-nowrap">No. Identitas</th> {{-- Tambah ini --}}
                    <th class="text-nowrap">Member Code</th>
                    <th class="text-nowrap">RFID</th>
                    <th class="text-nowrap">Nama</th>
                    <th class="text-nowrap">No. HP</th>        {{-- Tambah ini --}}
                    <th class="text-nowrap">Tipe</th>
                    <th class="text-nowrap">Membership</th>
                    <th class="text-nowrap">Masa Berlaku</th>
                    <th class="text-nowrap">Sisa Hari</th>
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
                    <h4 class="modal-title">Form Member</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form method="post" class="form-member" id="form-member" enctype="multipart/form-data">
                    @csrf

                    <div class="modal-body" id="modal-form-input"></div>

                    <div class="modal-footer">
                        <a href="javascript:;" id="btn-close" class="btn btn-white" data-bs-dismiss="modal">Close</a>
                        <button type="submit" class="btn btn-primary" id="btn-submit">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<form action="" class="d-none" id="form-delete" method="post">
    @csrf
    @method('DELETE')
</form>
<form action="" class="d-none" id="form-expired" method="post">
    @csrf
</form>

@include("member.show")
@include("member.membership")

<div class="modal fade" id="modal-photo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Foto</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 align-items-start">
                    <div class="col-5">
                        <img id="modal-photo-img" src="" alt="Foto" class="img-fluid rounded">
                    </div>
                    <div class="col-7 photo-meta">
                        <div class="mb-2">
                            <div class="label">Nama</div>
                            <div class="value" id="modal-photo-name">-</div>
                        </div>
                        <div class="mb-2">
                            <div class="label">No. HP</div>
                            <div class="value" id="modal-photo-phone">-</div>
                        </div>
                        <div class="mb-2">
                            <div class="label">No. Identitas</div>
                            <div class="value" id="modal-photo-ktp">-</div>
                        </div>
                        <div class="mb-2">
                            <div class="label">RFID</div>
                            <div class="value" id="modal-photo-rfid">-</div>
                        </div>
                        <div class="mb-2">
                            <div class="label">Membership</div>
                            <div class="value" id="modal-photo-membership">-</div>
                        </div>
                        <div>
                            <div class="label">Tipe</div>
                            <div class="value" id="modal-photo-type">-</div>
                        </div>
                    </div>
                </div>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
    // Menyimpan nilai filter saat ini
    var currentFilter = 'member';
    var currentMembershipId = 0;
    var currentStatusFilter = 'all';

    var table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        // Mengirim data tambahan, yaitu 'filter'
        ajax: {
            url: "{{ route('members.list') }}",
            data: function (d) {
                d.filter = currentFilter;
                d.membership_id = currentMembershipId;
                d.status_filter = currentStatusFilter;
            }
        },
        deferRender: true,
        pagination: true,
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', sortable: false, searchable: false },
            { data: 'image_profile', name: 'image_profile' },
            { data: 'no_ktp', name: 'no_ktp' }, // Kolom Identitas
            { data: 'member_code', name: 'member_code' },
            { data: 'rfid', name: 'rfid' },
            { data: 'nama', name: 'nama' },
            { data: 'no_hp', name: 'no_hp' },    // Kolom No HP
            {
                data: 'member_type', // Nama kolom yang didefinisikan di controller
                name: 'member_type',
                sortable: false,
                searchable: false
            },
            { data: 'membership_name', name: 'membership_name' },
            { data: 'masa_berlaku', name: 'masa_berlaku', sortable: false, searchable: false },
            { data: 'sisa_hari', name: 'sisa_hari', sortable: false, searchable: false },
            { data: 'expired', name: 'expired', sortable: false, searchable: false },
            { data: 'action', name: 'action', sortable: false, searchable: false, className: 'text-nowrap' },
        ]
    });

    function updateExportUrl() {
        var params = $.param({
            filter: currentFilter,
            membership_id: currentMembershipId,
            status_filter: currentStatusFilter
        });
        $('#btn-export-member').attr('href', "{{ route('members.export') }}" + '?' + params);
    }

    $('.btn-filter').on('click', function() {
        $('.btn-filter').removeClass('active');
        $(this).addClass('active');
        currentFilter = $(this).data('filter') || 'member';
        updateExportUrl();
        table.ajax.reload();
    });

    $('#filter-membership').on('change', function() {
        currentMembershipId = parseInt($(this).val() || '0', 10);
        updateExportUrl();
        table.ajax.reload();
    });

    $('#filter-status').on('change', function() {
        currentStatusFilter = $(this).val() || 'all';
        updateExportUrl();
        table.ajax.reload();
    });

    updateExportUrl();


    $('#image_profile').change(function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();

            reader.onload = function(e) {
                $('#image-preview')
                    .attr('src', e.target.result)
                    .show();
            };
            reader.readAsDataURL(this.files[0]);
        } else {
            $('#image-preview').attr('src', '').hide();
        }
    });

    $("#btn-add").on('click', function() {
        $("#rfid").removeAttr('disabled');
        let route = $(this).attr('data-route')
        $("#form-member").attr('action', route)
        $("#form-member")[0].reset();
        $.ajax({
            url: "{{ route('members.create') }}",
            type: 'GET',
            method: 'GET',
            success: function(response) {
                $("#modal-form-input").html(response);
                $('#image-preview').attr('src', '').hide();
            }
        })
    })

    $("#btn-close").on('click', function() {
        $("#form-member").removeAttr('action')
    })

    $("#datatable").on('click', '.btn-edit', function() {
        $("#form-member")[0].reset();
        $("#form-member input[name='_method']").remove();

        let route = $(this).attr('data-route')
        let id = $(this).attr('id')

        $(".form-member").attr('action', route)
        $(".modal-footer").append(`<input type="hidden" name="_method" value="PUT">`);

    })

    $("#datatable").on('click', '.btn-delete', function(e) {
        e.preventDefault();
        let route = $(this).attr('data-route')
        $("#form-delete").attr('action', route)

        swal({
            title: 'Hapus data member?',
            text: 'Menghapus member bersifat permanen.',
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

    $("#datatable").on('click', '.btn-expired', function(e) {
        e.preventDefault();
        let route = $(this).attr('data-route')
        $("#form-expired").attr('action', route)

        swal({
            title: 'Perpanjang data member?',
            icon: 'warning',
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
                    className: 'btn btn-primary',
                    closeModal: true
                }
            }
        }).then((result) => {
            if (result) {
                $("#form-expired").submit()
            } else {
                $("#form-expired").attr('action', '')
            }
        });
    })

    $("#datatable").on('click', '.btn-show', function() {
        let route = $(this).attr('data-route')
        let id = $(this).attr('id')

        $.ajax({
            url: "/members/" + id,
            type: 'GET',
            method: 'GET',
            success: function(response) {
                console.log(response)
                let member = response.member;
                let paymentHistory = response.payment_history || [];
                let paymentHistoryOwner = response.payment_history_owner || null;
                let paymentHistoryNote = response.payment_history_note || 'Riwayat pembayaran membership masih dalam tahap development.';
                let familyMembers = response.family_members || [];

                const textOrDash = (value) => value ? value : '-';

                $("#info-name").text(textOrDash(member.nama))
                $("#info-member-id").val(member.id || '')
                $("#info-id").text(textOrDash(member.no_ktp))
                $("#info-phone").text(textOrDash(member.no_hp))
                $("#info-birth").text(textOrDash(member.tgl_lahir))
                $("#info-gender").text(textOrDash(member.jenis_kelamin))
                $("#info-address").text(textOrDash(member.alamat))
                $("#info-member-code").text(textOrDash(member.member_code))
                $("#info-rfid").text(textOrDash(member.rfid))
                $("#info-register").text(textOrDash(member.tgl_register))
                $("#info-expired").text(textOrDash(member.tgl_expired))

                const defaultMemberImage = "{{ asset('img/user/user-10.jpg') }}";
                $("#image-member")
                    .attr("src", member.image_profile || defaultMemberImage)
                    .off("error")
                    .on("error", function() {
                        $(this).attr("src", defaultMemberImage);
                    });

                if (member.membership_id != 0) {
                    $("#info-membership").text(member.membership.name)
                } else {
                    $("#info-membership").text("-")
                }

                $("#qrcode").html(response.qr_markup || '');

                const printUrl = `/members/${member.id}/print-qr?t=${Date.now()}`;
                $("#btn-print-qr").attr("href", printUrl)
                $("#btn-download-card").attr("href", printUrl)

                $("#family-member-count").text(`Total anggota: ${familyMembers.length}`);
                let familyRows = '';
                if (familyMembers.length === 0) {
                    familyRows = '<tr><td colspan="5" class="text-center text-muted">Belum ada data anggota grup</td></tr>';
                } else {
                    familyMembers.forEach(function(item) {
                        const relationBadge = item.is_current
                            ? `<span class="badge bg-primary">${textOrDash(item.relation)}</span>`
                            : `<span class="badge bg-secondary">${textOrDash(item.relation)}</span>`;
                        familyRows += `<tr>
                            <td class="text-nowrap">${textOrDash(item.nama)}</td>
                            <td class="text-nowrap">${relationBadge}</td>
                            <td class="text-nowrap">${textOrDash(item.rfid)}</td>
                            <td class="text-nowrap">${textOrDash(item.no_hp)}</td>
                            <td class="text-nowrap">${textOrDash(item.tgl_expired)}</td>
                        </tr>`;
                    });
                }
                $("#family-members-body").html(familyRows);

                if (paymentHistoryOwner && paymentHistoryOwner.name) {
                    $("#payment-history-owner").text(`Akun pembayaran: ${paymentHistoryOwner.name}`);
                } else {
                    $("#payment-history-owner").text('');
                }
                $("#payment-history-note").text(paymentHistoryNote);

                let rows = '';
                if (paymentHistory.length === 0) {
                    rows = '<tr><td colspan="8" class="text-center text-muted">Belum ada riwayat pembayaran membership</td></tr>';
                } else {
                    paymentHistory.forEach(function(item) {
                        rows += `<tr>
                            <td class="text-nowrap">${textOrDash(item.date)}</td>
                            <td class="text-nowrap">${textOrDash(item.invoice)}</td>
                            <td class="text-nowrap">${textOrDash(item.type)}</td>
                            <td class="text-nowrap">${textOrDash(item.method)}</td>
                            <td class="text-nowrap">${textOrDash(item.cashier)}</td>
                            <td class="text-nowrap">${textOrDash(item.amount)}</td>
                            <td class="text-nowrap">${textOrDash(item.ppn)}</td>
                            <td class="text-nowrap">${textOrDash(item.total)}</td>
                        </tr>`;
                    });
                }
                $("#payment-history-body").html(rows);
            }
        })
    })

    $("#rfid").on('keypress', function(e) {
        var keyCode = e.keyCode || e.which;
        if (keyCode === 13) {
            e.preventDefault();
            return false;
        }
    })

    function openMemberPrintPopup(url) {
        const popupFeatures = 'width=480,height=720,menubar=no,toolbar=no,location=no,status=no,scrollbars=yes,resizable=yes';
        const popup = window.open(url, 'member-card-print', popupFeatures);
        if (popup) {
            popup.focus();
        } else {
            // Fallback jika popup diblokir browser.
            window.location.href = url;
        }
    }

    $("#btn-print-qr, #btn-download-card").on('click', function(e) {
        e.preventDefault();

        const memberId = $("#info-member-id").val();
        if (!memberId) {
            return false;
        }

        const url = `/members/${memberId}/print-qr?t=${Date.now()}`;
        $(this).attr('href', url);
        openMemberPrintPopup(url);
    });

    $("#datatable").on('click', '.btn-membership', function() {
        let route = $(this).attr('data-route')
        let id = $(this).attr('id')

        $.ajax({
            url: "/members/" + id,
            type: 'GET',
            method: 'GET',
            success: function(response) {
                console.log(response)
                let member = response.member;

                $("#membership-name").val(member.nama)

                if (member.membership_id != 0) {
                    $("#membership-id").val(member.membership_id)
                } else {
                    $("#membership-id").val("")
                }
            }
        })
    });

    $('#membership-id').on('change', function() {

        var selectedOption = $(this).find('option:selected');
        var max_person = parseInt(selectedOption.data('max-person')) || 0;

        if (this.value) {
            // --- Loop untuk Anggota Grup ---
            $('.form-group.title-group').remove();
            $(".form-group.member-group").remove();

            for (var i = 1; i < max_person; i++) {
                // 2. PERBAIKAN DUPLIKAT ID
                // (Kode string HTML Anda di sini sudah benar)
                var rfidGroupField = `
                                <div class="form-group member-group row mb-3">
                                    <div class="col-md-4">
                                        <label for="rfid_${i}" class="form-label">RFID Anggota</label>
                                        <input type="text" name="rfid_group[]" id="rfid_${i}" class="form-control" placeholder="0192029300">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="nama_${i}" class="form-label">Nama Anggota</label>
                                        <input type="text" name="name_group[]" id="nama_${i}" class="form-control" placeholder="John Doe">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="image_profile_${i}" class="form-label">Foto Anggota</label>
                                        <input type="file" name="image_group[]" id="image_profile_${i}" class="form-control" accept="image/*">
                                    </div>
                                </div>
                                `;

                var title = `
                                <div class="form-group title-group mb-3 border-bottom">
                                    <h5>Anggota Grup</h5>
                                </div>
                                `;

                // 3. TAMBAHKAN TITLE HANYA SEKALI
                if (i === 1) {
                    // Cari elemen sebelum field membership, atau sesuaikan dengan struktur form Anda
                    $('#modal-dialog #form-member .modal-body').append(title);
                }
                // 4. INSERT FIELD SETELAH FIELD TERAKHIR
                $('#modal-dialog #form-member .modal-body').append(rfidGroupField);
            }
        } else {
            // Jika user memilih "-- Pilih --", kosongkan field
            $('#duration').val('');
            $('#price').val('');
            $('#tgl_expired').val('');
            $('.form-group.title-group').remove(); // Hapus title
            $(".form-group.member-group").remove(); // Hapus field
        }
    });

    $("#datatable").on('click', '.js-photo-thumb', function() {
        let src = $(this).attr('data-full') || $(this).attr('src');
        let name = $(this).attr('data-name') || '-';
        let phone = $(this).attr('data-phone') || '-';
        let ktp = $(this).attr('data-ktp') || '-';
        let rfid = $(this).attr('data-rfid') || '-';
        let membership = $(this).attr('data-membership') || '-';
        let type = $(this).attr('data-type') || '-';
        if (!src) return;

        $("#modal-photo-img").attr('src', src);
        $("#modal-photo-name").text(name);
        $("#modal-photo-phone").text(phone);
        $("#modal-photo-ktp").text(ktp);
        $("#modal-photo-rfid").text(rfid);
        $("#modal-photo-membership").text(membership);
        $("#modal-photo-type").text(type);
        $('#modal-photo').modal('show');
    });
</script>
@endpush
