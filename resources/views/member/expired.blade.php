<div class="modal fade" id="modal-expired">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Form Renewal Membership</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body">
                <form action="" id="form-expired">
                    <div class="form-group mb-3">
                        <label for="nama">Nama</label>
                        <input type="text" name="nama" id="expired-nama" class="form-control">
                    </div>

                    <div class="form-group mb-3">
                        <label for="membership_id" class="form-label">Select Membership</label>
                        <select name="membership_id" id="expired-membership_id" class="form-select">
                            @foreach ($memberships as $membership)
                            <option value="{{ $membership->id }}">{{ $membership->name }} - Rp {{ number_format($membership->price) }} - {{ $membership->duration_days }} hari</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <a href="javascript:;" class="btn btn-white" data-bs-dismiss="modal">Close</a>
                <button type="button" class="btn btn-success" id="btn-save-expired">Submit</button>
            </div>
        </div>
    </div>
</div>