<?php

namespace App\Http\Controllers;

use App\Models\GateAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;

class GateAccessController extends Controller
{
    /**
     * Terapkan middleware permission
     * Sesuaikan 'gate-access' dengan permission Anda
     */
    public function __construct()
    {
        // $this->middleware('permission:gate-access');
    }

    /**
     * Menampilkan halaman index (view)
     */
    public function index()
    {
        $title = 'Data Dispenser Access';
        $breadcrumbs = ['Master', 'Data Dispenser Access'];

        return view('gate-access.index', compact('title', 'breadcrumbs'));
    }

    /**
     * Mengambil data untuk DataTables
     */
    public function get(Request $request)
    {
        if ($request->ajax()) {
            $data = GateAccess::orderBy('name', 'asc')->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    // Sesuaikan route ke 'gate_accesses.update' dan 'gate_accesses.destroy'
                    $editUrl = route('gate-accesses.update', $row->id);
                    $deleteUrl = route('gate-accesses.destroy', $row->id);

                    $actionBtn = '<a href="#modal-dialog" id="' . $row->id . '" class="btn btn-sm btn-success btn-edit" data-route="' . $editUrl . '" data-bs-toggle="modal">Edit</a> ';
                    $actionBtn .= '<button type="button" data-route="' . $deleteUrl . '" class="delete btn btn-danger btn-delete btn-sm">Delete</button>';
                    return $actionBtn;
                })
                ->editColumn('is_active', function ($row) {
                    // Format status (mirip UserController)
                    return $row->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
                })
                ->rawColumns(['action', 'is_active'])
                ->make(true);
        }
    }

    /**
     * Menyimpan data gate access baru
     */
    public function store(Request $request)
    {
        // Validasi
        $request->validate([
            'gate_access_id' => 'required|unique:gate_accesses',
            'name' => 'required|string|max:255',
            'is_active' => 'required|numeric|in:0,1',
        ]);

        try {
            DB::beginTransaction();

            GateAccess::create([
                'gate_access_id' => $request->gate_access_id,
                'name' => $request->name,
            ]);

            DB::commit();

            return back()->with('success', "Dispenser {$request->name} berhasil ditambahkan");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Mengambil data satu gate access (untuk modal edit)
     */
    public function show(GateAccess $gateAccess)
    {
        // Kirim JSON
        return response()->json([
            'status' => 'success',
            'gate_access' => $gateAccess,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GateAccess $gateAccess)
    {
        // Tidak digunakan jika menggunakan modal
    }

    /**
     * Update data gate access
     */
    public function update(Request $request, GateAccess $gateAccess)
    {
        // Validasi dengan rule unique (ignore ID saat ini)
        $request->validate([
            'gate_access_id' => 'required|unique:gate_accesses,gate_access_id,' . $gateAccess->id,
            'name' => 'required|string|max:255',
            'is_active' => 'required|numeric|in:0,1',
        ]);

        try {
            DB::beginTransaction();

            $gateAccess->update([
                'gate_access_id' => $request->gate_access_id,
                'name' => $request->name,
                'is_active' => $request->is_active,
            ]);

            DB::commit();

            return back()->with('success', "Dispenser {$gateAccess->name} berhasil diupdate");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Hapus data gate access
     */
    public function destroy(GateAccess $gateAccess)
    {
        try {
            DB::beginTransaction();
            $gateAccess->delete();
            DB::commit();

            return back()->with('success', "Dispenser {$gateAccess->name} berhasil didelete");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }
}
