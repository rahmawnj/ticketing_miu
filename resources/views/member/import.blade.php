<div class="modal fade" id="modal-dialog-import">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Import Data Member</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <form action="{{ route('member.import') }}" method="post" id="form-member" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="file" class="form-label">Upload File</label>
                        <input type="file" name="file" id="file" class="form-control" accept=".xls, .xlsx">

                        @error('file')
                        <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <a href="{{ route('members.download') }}" class="text-decoration-none"><i class="fas fa-download"></i> Example Import Member File</a>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="javascript:;" id="btn-close" class="btn btn-white" data-bs-dismiss="modal">Close</a>
                    <button type="submit" class="btn btn-success">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
