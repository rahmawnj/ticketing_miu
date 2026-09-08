<?php

namespace App\Http\Controllers;

use App\Models\Sewa;
use App\Models\Setting; // <-- Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class SewaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:sewa-access');
    }

    public function index()
    {
        $title = 'Data Lainnya';
        $breadcrumbs = ['Master', 'Data Lainnya'];
        $setting = Setting::asObject(); // Ambil data setting

        return view('sewa.index', compact('title', 'breadcrumbs', 'setting')); // Kirim setting ke view
    }

    public function get(Request $request)
    {
        if ($request->ajax()) {
            $data = Sewa::orderBy('device', 'asc')->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<a href="#modal-dialog" id="' . $row->id . '" class="btn btn-sm btn-success btn-edit" data-route="' . route('sewa.update', $row->id) . '" data-bs-toggle="modal" data-use-ppn="' . $row->use_ppn . '" data-ppn-value="' . $row->ppn . '" data-is-nominal-flexible="' . ((int) ($row->is_nominal_flexible ?? 0)) . '">Edit</a> <button type="button" data-route="' . route('sewa.destroy', $row->id) . '" class="delete btn btn-danger btn-delete btn-sm">Delete</button>';
                    return $actionBtn;
                })
                ->editColumn('harga', function ($row) {
                    return 'Rp. ' . number_format($row->harga + $row->ppn, 0, ',', '.');
                })
                ->addColumn('dynamic_price_status', function ($row) {
                    if ((int) ($row->is_nominal_flexible ?? 0) === 1) {
                        return '<span class="badge bg-success">Aktif (Bisa diubah di Penyewaan)</span>';
                    }
                    return '<span class="badge bg-secondary">Nonaktif (Ikuti harga master)</span>';
                })
                ->editColumn('ppn_status', function ($row) {
                    if ($row->use_ppn == 1) {
                        return '<span class="badge bg-success">Diterapkan (Rp. ' . number_format($row->ppn, 0, ',', '.') . ')</span>';
                    }
                    return '<span class="badge bg-danger">Tidak Diterapkan</span>';
                })
                ->rawColumns(['action', 'dynamic_price_status', 'ppn_status']) // Tambahkan kolom badge ke rawColumns
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'harga' => 'required|numeric',
                'device' => 'required|numeric',
            ]);
            if ($validator->fails()) {
                Log::warning('Sewa store validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'payload' => $request->except(['_token']),
                ]);
                return back()->withErrors($validator)->withInput()->with('error', 'Validasi gagal. Mohon cek input.');
            }


            DB::beginTransaction();

            $harga = $request->harga;
            $setting = Setting::asObject();

            // Hitung PPN jika checkbox "ppn" dicentang ("on")
            $usePpn = $request->ppn == "on" ? 1 : 0;
            $calculatedPpn = $usePpn ? ($harga * $setting->ppn / 100) : 0;

            $data = $request->all();
            $data['use_ppn'] = $usePpn;
            $data['ppn'] = $calculatedPpn;
            $data['use_time'] = $request->has('use_time') ? 1 : 0;
            $data['is_nominal_flexible'] = $request->has('is_nominal_flexible') ? 1 : 0;

            $sewa = Sewa::create($data);

            DB::commit();

            return redirect()->route('sewa.index')->with('success', "{$sewa->name} berhasil ditambahkan");
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Sewa store failed', [
                'message' => $th->getMessage(),
                'payload' => $request->except(['_token']),
            ]);
            return back()->with('error', $th->getMessage());
        }
    }

    public function show(Sewa $sewa)
    {
        return response()->json([
            'status' => 'success',
            'sewa' => $sewa
        ], 200);
    }

    public function update(Request $request, Sewa $sewa)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'harga' => 'required|numeric',
                'device' => 'required|numeric',
            ]);
            if ($validator->fails()) {
                Log::warning('Sewa update validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'payload' => $request->except(['_token']),
                    'sewa_id' => $sewa->id,
                ]);
                return back()->withErrors($validator)->withInput()->with('error', 'Validasi gagal. Mohon cek input.');
            }

            DB::beginTransaction();

            $harga = $request->harga;
            $setting = Setting::asObject();

            // Hitung PPN jika checkbox "ppn" dicentang ("on")
            $usePpn = $request->ppn == "on" ? 1 : 0;
            $calculatedPpn = $usePpn ? ($harga * $setting->ppn / 100) : 0;

            $data = $request->all();
            $data['use_ppn'] = $usePpn;
            $data['ppn'] = $calculatedPpn;
            $data['use_time'] = $request->has('use_time') ? 1 : 0;
            $data['is_nominal_flexible'] = $request->has('is_nominal_flexible') ? 1 : 0;

            $sewa->update($data);

            DB::commit();

            return redirect()->route('sewa.index')->with('success', "{$sewa->name} berhasil diupdate");
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Sewa update failed', [
                'message' => $th->getMessage(),
                'payload' => $request->except(['_token']),
                'sewa_id' => $sewa->id,
            ]);
            return back()->with('error', $th->getMessage());
        }
    }

    public function destroy(Sewa $sewa)
    {
        try {
            DB::beginTransaction();

            $sewa->delete();

            DB::commit();

            return redirect()->route('sewa.index')->with('success', "{$sewa->name} berhasil dihapus");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }
}
