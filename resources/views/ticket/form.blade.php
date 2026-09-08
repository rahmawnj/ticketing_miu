@extends('layouts.master', ['title' => $title, 'breadcrumbs' => $breadcrumbs])
<link href="{{ asset('/') }}plugins/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/select2/dist/css/select2.min.css" rel="stylesheet" />
<link href="{{ asset('/') }}plugins/select2/dist/css/select2.min.css" rel="stylesheet" />
@push('style')

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
    <form action="{{ $action }}" method="post">
        @method($method)
        @csrf

        <div class="form-group mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ $ticket->name ?? old('name') }}">
            @error('name')
            <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group mb-3">
            <label for="harga" class="form-label">Harga</label>
            <input type="number" name="harga" id="harga" class="form-control" value="{{ $ticket->harga ?? old('harga') }}">
            @error('harga')
            <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group mb-3">
            <label for="jumlah_ppn" class="form-label">Jumlah PBJT</label>
            {{-- Hapus error check untuk jumlah_ppn karena ini readonly dan tidak divalidasi di controller --}}
            <input type="number" name="jumlah_ppn" id="jumlah_ppn" class="form-control" value="{{ $ticket->ppn ?? old('jumlah_ppn') }}" readonly>
        </div>

        <input type="hidden" name="jenis" value="{{ $jenisRegulerId ?? 1 }}">

        <div class="form-group mb-3">
            <label for="gate_id" class="form-label">Dispenser ID</label>
            <select name="gate_id" id="gate_id" class="form-control select2">
                <option value="" selected>-- Pilih Dispenser --</option>
                @foreach ($gates as $gate)
                {{-- Gunakan $ticket->tripod atau old('gate_id') untuk menjaga nilai setelah validasi gagal --}}
                <option value="{{ $gate->id }}" {{ ($gate->id == ($ticket->tripod ?? old('gate_id'))) ? 'selected' : '' }}>{{ $gate->name }}</option>
                @endforeach
            </select>

            @error('gate_id')
            {{-- PERBAIKAN PENTING DI SINI --}}
            <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="ppn" name="ppn" {{ $ticket->use_ppn == 1 ? 'checked' : '' }} />
            <label class="form-check-label" for="ppn">PBJT</label>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </form>
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
<script src="{{ asset('/') }}plugins/select2/dist/js/select2.min.js"></script>

<script>
    $(document).on('click', '#ppn', function() {
        if ($(this).is(':checked')) {
            $('#jumlah_ppn').val(parseInt("{{ $setting->ppn }}") * $('#harga').val() / 100);
        } else {
            $('#jumlah_ppn').val(0);
        }
    });

    $(".select2").select2({
        placeholder: "Dispenser ID"
    });
</script>
@endpush
