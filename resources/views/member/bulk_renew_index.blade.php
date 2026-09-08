@extends('layouts.master', ['title' => $title, 'breadcrumbs' => $breadcrumbs])

@push('style')
<link href="{{ asset('/') }}plugins/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
<style>
    #modal-member-detail .modal-dialog {
        max-width: 680px;
    }
    #modal-member-detail .modal-body {
        padding: 1rem 1.25rem;
    }
    #modal-member-detail #detail-image {
        max-height: 170px !important;
        width: auto;
    }
    #modal-member-detail .table th,
    #modal-member-detail .table td {
        padding-top: .22rem;
        padding-bottom: .22rem;
    }
    @media (max-width: 767.98px) {
        #modal-member-detail .modal-dialog {
            max-width: 92%;
            margin: 1rem auto;
        }
        #modal-member-detail #detail-image {
            max-height: 140px !important;
        }
    }
</style>
@endpush

@section('content')
<div class="panel panel-inverse">
    <div class="panel-heading">
        <h4 class="panel-title">{{ $title }}</h4>
    </div>

    <div class="panel-body">
        <table id="datatable-renew" class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th class="text-nowrap">No</th>
                    <th class="text-nowrap">Nama</th>
                    <th class="text-nowrap">No HP</th>
                    <th class="text-nowrap">Membership</th>
                    <th class="text-nowrap">Harga Paket</th>
                    <th class="text-nowrap">Tgl. Expired Lama</th>
                    <th class="text-nowrap">Status</th>
                    <th class="text-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modal-member-detail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-3">
                        <img id="detail-image" src="{{ asset('img/user/user-10.jpg') }}" alt="Foto Member" class="img-fluid rounded border" style="max-height: 170px;">
                    </div>
                    <div class="col-md-8">
                        <table class="table table-sm table-borderless mb-3">
                            <tr><th style="width: 180px;">Nama</th><td id="detail-nama">-</td></tr>
                            <tr><th>No HP</th><td id="detail-nohp">-</td></tr>
                            <tr><th>Membership</th><td id="detail-membership">-</td></tr>
                            <tr><th>Tgl. Expired</th><td id="detail-expired">-</td></tr>
                        </table>
                        <div id="detail-submember-wrap" style="display: none;">
                            <h6 class="mb-2">Sub Member</h6>
                            <ul id="detail-submembers" class="mb-0 ps-3"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Form tersembunyi untuk mengirim request perpanjangan --}}
<form id="form-single-renew" method="POST" action="{{ route('members.process_bulk_renew') }}" style="display: none;">
    @csrf
    <input type="hidden" name="member_ids[]" id="member-id-input">
    <input type="hidden" name="metode" id="renew-metode-input">
    <input type="hidden" name="admin_fee_master_id" id="renew-admin-fee-master-id-input">
    <input type="hidden" name="renewal_mode" id="renewal-mode-input">
    <input type="hidden" name="nama_kartu" id="renew-nama-kartu-input">
    <input type="hidden" name="no_kartu" id="renew-no-kartu-input">
    <input type="hidden" name="bank" id="renew-bank-input">
</form>

@endsection

@push('script')
<script src="{{ asset('/') }}plugins/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="{{ asset('/') }}plugins/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="{{ asset('/') }}plugins/sweetalert/dist/sweetalert.min.js"></script>

<script>
    function formatRupiahNumber(value) {
        return new Intl.NumberFormat('id-ID').format(Number(value || 0));
    }

    var table = $('#datatable-renew').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('members.get_renewable') }}",
        },
        deferRender: true,
        pagination: true,
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'nama', name: 'nama' },
            { data: 'no_hp', name: 'no_hp' },
            { data: 'membership.name', name: 'membership.name' },
            { data: 'package_price', name: 'package_price' },
            { data: 'tgl_expired', name: 'tgl_expired' },
            { data: 'renewal_status', name: 'renewal_status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
    });

    // --- Logika Perpanjangan Individual ---

    $('#datatable-renew').on('click', '.btn-renew-single', function(e) {
        e.preventDefault();
        var memberId = $(this).data('id');
        var memberName = $(this).data('name');
        var memberBasePriceRaw = parseInt($(this).attr('data-price-base-raw') || '0', 10) || 0;
        var memberPpnPriceRaw = parseInt($(this).attr('data-price-ppn-raw') || '0', 10) || 0;
        var renewalMode = $(this).data('renewal-mode') || 'renewal';
        var isRenewalBaru = renewalMode === 'renewal_baru';
        var confirmTitle = isRenewalBaru ? "Konfirmasi Perpanjangan Baru" : "Konfirmasi Perpanjangan";
        var confirmButton = isRenewalBaru ? "Ya, Perpanjangan Baru!" : "Ya, Perpanjang!";
        var adminOptionsRaw = $(this).attr('data-admin-options') || '[]';
        var adminOptions = [];

        try {
            adminOptions = JSON.parse(adminOptionsRaw);
            if (!Array.isArray(adminOptions)) {
                adminOptions = [];
            }
        } catch (error) {
            adminOptions = [];
        }

        var paymentWrapper = document.createElement('div');
        paymentWrapper.style.display = 'grid';
        paymentWrapper.style.gap = '8px';

        var summaryBox = document.createElement('div');
        summaryBox.className = 'alert alert-info text-start mb-1';
        summaryBox.id = 'renew-price-summary';
        paymentWrapper.appendChild(summaryBox);

        var adminLabel = document.createElement('label');
        adminLabel.setAttribute('for', 'renew-admin-fee-swal');
        adminLabel.textContent = 'Jenis Admin';
        paymentWrapper.appendChild(adminLabel);

        var adminSelect = document.createElement('select');
        adminSelect.id = 'renew-admin-fee-swal';
        adminSelect.className = 'swal-content__input';
        paymentWrapper.appendChild(adminSelect);

        var placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = '-- Pilih Jenis Admin --';
        placeholderOption.setAttribute('data-fee', '0');
        adminSelect.appendChild(placeholderOption);

        adminOptions.forEach(function(item) {
            var option = document.createElement('option');
            option.value = item.id;
            option.textContent = (item.admin_type || '-') + ' (Rp ' + formatRupiahNumber(item.admin_fee || 0) + ')';
            option.setAttribute('data-fee', String(parseInt(item.admin_fee || 0, 10) || 0));
            adminSelect.appendChild(option);
        });

        var metodeLabel = document.createElement('label');
        metodeLabel.setAttribute('for', 'renew-metode-swal');
        metodeLabel.textContent = 'Metode Pembayaran';
        paymentWrapper.appendChild(metodeLabel);

        var metodeSelect = document.createElement('select');
        metodeSelect.id = 'renew-metode-swal';
        metodeSelect.className = 'swal-content__input';
        paymentWrapper.appendChild(metodeSelect);

        var namaKartuInput = document.createElement('input');
        namaKartuInput.type = 'text';
        namaKartuInput.id = 'renew-nama-kartu-swal';
        namaKartuInput.className = 'swal-content__input renew-card-field';
        namaKartuInput.placeholder = 'Nama Rekening / Pemilik Kartu';
        namaKartuInput.style.display = 'none';
        paymentWrapper.appendChild(namaKartuInput);

        var noKartuInput = document.createElement('input');
        noKartuInput.type = 'text';
        noKartuInput.id = 'renew-no-kartu-swal';
        noKartuInput.className = 'swal-content__input renew-card-field';
        noKartuInput.placeholder = 'No Kartu / No Rekening';
        noKartuInput.style.display = 'none';
        paymentWrapper.appendChild(noKartuInput);

        var bankInput = document.createElement('input');
        bankInput.type = 'text';
        bankInput.id = 'renew-bank-swal';
        bankInput.className = 'swal-content__input renew-card-field';
        bankInput.placeholder = 'Bank';
        bankInput.style.display = 'none';
        paymentWrapper.appendChild(bankInput);

        var paymentMethodOptions = @json(\App\Support\PaymentMethod::options());

        Object.entries(paymentMethodOptions).forEach(function(item) {
            var option = document.createElement('option');
            option.value = item[0];
            option.textContent = item[1];
            metodeSelect.appendChild(option);
        });

        function getSelectedAdminFee() {
            var selectedOption = adminSelect.options[adminSelect.selectedIndex];
            if (!selectedOption) {
                return 0;
            }
            return parseInt(selectedOption.getAttribute('data-fee') || '0', 10) || 0;
        }

        function renderRenewSummary() {
            var adminFee = getSelectedAdminFee();
            var total = memberBasePriceRaw + memberPpnPriceRaw + adminFee;
            summaryBox.innerHTML = ''
                + 'Harga Dasar: <strong>Rp ' + formatRupiahNumber(memberBasePriceRaw) + '</strong><br>'
                + 'PBJT: <strong>Rp ' + formatRupiahNumber(memberPpnPriceRaw) + '</strong><br>'
                + 'Biaya Admin: <strong>Rp ' + formatRupiahNumber(adminFee) + '</strong><br>'
                + 'Total: <strong>Rp ' + formatRupiahNumber(total) + '</strong>';
        }

        function toggleRenewCardFields() {
            var isCardMethod = metodeSelect.value === 'debit' || metodeSelect.value === 'kredit';
            var displayMode = isCardMethod ? 'block' : 'none';

            namaKartuInput.style.display = displayMode;
            noKartuInput.style.display = displayMode;
            bankInput.style.display = displayMode;

            if (!isCardMethod) {
                namaKartuInput.value = '';
                noKartuInput.value = '';
                bankInput.value = '';
            }
        }

        metodeSelect.addEventListener('change', toggleRenewCardFields);
        adminSelect.addEventListener('change', renderRenewSummary);
        toggleRenewCardFields();
        renderRenewSummary();

        swal({
            title: confirmTitle,
            text: "Anda yakin ingin memperpanjang keanggotaan " + memberName + " (beserta submember-nya)?",
            content: paymentWrapper,
            icon: "warning",
            buttons: ["Batal", confirmButton],
            dangerMode: true,
        })
        .then((willRenew) => {
            if (willRenew) {
                var selectedMetode = (metodeSelect.value || '').trim();
                var selectedAdminFeeMasterId = (adminSelect.value || '').trim();
                var isCardMethod = selectedMetode === 'debit' || selectedMetode === 'kredit';
                var namaKartu = (namaKartuInput.value || '').trim();
                var noKartu = (noKartuInput.value || '').trim();
                var bank = (bankInput.value || '').trim();

                if (isCardMethod && (!namaKartu || !noKartu || !bank)) {
                    swal("Data pembayaran belum lengkap", "Untuk metode debit/kredit, isi nama rekening, nomor kartu/rekening, dan bank.", "warning");
                    return;
                }

                // Set ID member ke form tersembunyi
                $('#member-id-input').val(memberId);
                $('#renew-metode-input').val(selectedMetode);
                $('#renew-admin-fee-master-id-input').val(selectedAdminFeeMasterId);
                $('#renewal-mode-input').val(renewalMode);
                $('#renew-nama-kartu-input').val(isCardMethod ? namaKartu : '');
                $('#renew-no-kartu-input').val(isCardMethod ? noKartu : '');
                $('#renew-bank-input').val(isCardMethod ? bank : '');

                // Submit form
                $('#form-single-renew').submit();
            }
        });
    });

    $('#datatable-renew').on('click', '.btn-member-detail', function(e) {
        e.preventDefault();
        var route = $(this).data('route');
        if (!route) return;

        $.get(route, function(response) {
            if (!response || response.status !== 'success' || !response.member) {
                return;
            }

            var member = response.member;
            var familyMembers = response.family_members || [];
            var subMembers = familyMembers.filter(function(item) {
                return (item.relation || '').toLowerCase().indexOf('sub') >= 0;
            });

            $('#detail-image').attr('src', member.image_profile || '{{ asset('img/user/user-10.jpg') }}');
            $('#detail-nama').text(member.nama || '-');
            $('#detail-nohp').text(member.no_hp || '-');
            $('#detail-membership').text((member.membership && member.membership.name) ? member.membership.name : '-');
            $('#detail-expired').text(member.tgl_expired || '-');

            var $subList = $('#detail-submembers');
            var $subWrap = $('#detail-submember-wrap');
            $subList.empty();
            if (subMembers.length === 0) {
                $subWrap.hide();
            } else {
                $subWrap.show();
                subMembers.forEach(function(item) {
                    $subList.append('<li>' + (item.nama || '-') + '</li>');
                });
            }

            $('#modal-member-detail').modal('show');
        });
    });

</script>
@endpush
