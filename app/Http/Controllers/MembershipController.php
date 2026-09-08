<?php

namespace App\Http\Controllers;

use App\Models\GateAccess;
use App\Models\Membership;
use App\Models\Setting; // <-- Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;

class MembershipController extends Controller
{
    /**
     * Terapkan middleware permission
     * Sesuaikan 'membership-access' dengan permission Anda
     */
    public function __construct()
    {
        // $this->middleware('permission:membership-access');
    }

    /**
     * Menampilkan halaman index (view)
     */
    public function index()
    {
        $title = 'Data Membership';
        $breadcrumbs = ['Master', 'Data Membership'];
        $gates = GateAccess::whereIsActive(1)->get();
        $setting = Setting::asObject(); // <-- Ambil data setting

        // Kirim setting ke view
        return view('membership.index', compact('title', 'breadcrumbs', 'gates', 'setting'));
    }

    /**
     * Mengambil data untuk DataTables
     */
    public function get(Request $request)
    {
        if ($request->ajax()) {
            $data = Membership::withCount([
                'members as total_members' => function ($query) {
                    $query->where('parent_id', 0);
                }
            ])->orderBy('name', 'asc')->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    // Sertakan data PPN di tombol edit
                    $editUrl = route('memberships.update', $row->id);
                    $deleteUrl = route('memberships.destroy', $row->id);

                    $actionBtn = '<a href="#modal-dialog" id="' . $row->id . '" class="btn btn-sm btn-success btn-edit" data-route="' . $editUrl . '" data-bs-toggle="modal" data-use-ppn="' . $row->use_ppn . '" data-ppn-value="' . $row->ppn . '">Edit</a> ';
                    $actionBtn .= '<button type="button" data-route="' . $deleteUrl . '" class="delete btn btn-danger btn-delete btn-sm">Delete</button>';
                    return $actionBtn;
                })
                ->editColumn('price', function ($row) {
                    // Tampilkan Harga Pokok + PPN (Total Harga)
                    $totalPrice = $row->price + $row->ppn;
                    return 'Rp ' . number_format($totalPrice, 0, ',', '.');
                })
                ->editColumn('code', function ($row) {
                    return $row->code ?: '-';
                })
                ->addColumn('ppn_status', function ($row) {
                    // Kolom baru untuk status PPN
                    if ($row->use_ppn == 1) {
                        return '<span class="badge bg-success">Diterapkan (Rp. ' . number_format($row->ppn, 0, ',', '.') . ')</span>';
                    }
                    return '<span class="badge bg-danger">Tidak Diterapkan</span>';
                })
                ->editColumn('duration_days', function ($row) {
                    // Tambahkan "Hari"
                    return $row->duration_days . ' Hari';
                })
                ->addColumn('total_members', function ($row) {
                    return (int) ($row->total_members ?? 0);
                })
                ->rawColumns(['action', 'ppn_status']) // Tambahkan 'ppn_status' ke rawColumns
                ->make(true);
        }
    }

    /**
     * Menyimpan data membership baru
     */
    public function store(Request $request)
    {
        // Validasi
        $request->validate([
            'name' => 'required|string|max:255|unique:memberships,name',
            'code' => 'required|string|max:20|unique:memberships,code',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'max_person' => 'nullable|integer|min:1',
            'max_access' => 'nullable|integer|min:0',
            'gates' => 'required|array',
            'gates.*' => 'exists:gate_accesses,id'
        ]);

        try {
            DB::beginTransaction();

            $harga = $request->price;
            $setting = Setting::asObject();

            // Hitung PPN
            $usePpn = $request->use_ppn == "on" ? 1 : 0;
            $calculatedPpn = $usePpn ? ($harga * $setting->ppn / 100) : 0;

            $data = $request->all();
            $data['code'] = strtoupper(trim((string) $request->code));
            $data['use_ppn'] = $usePpn;
            $data['ppn'] = $calculatedPpn;
            $data['max_access'] = $request->max_access ?? 0;

            $membership = Membership::create($data);

            $membership->gates()->sync($request->gates);

            DB::commit();

            return back()->with('success', "Membership {$request->name} berhasil ditambahkan");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Mengambil data satu membership (untuk modal edit)
     */
    public function show(Membership $membership)
    {
        // Mirip UserController, kirim JSON
        $membership->load('gates');

        return response()->json([
            'status' => 'success',
            'membership' => $membership,
        ]);
    }

    /**
     * Update data membership
     */
    public function update(Request $request, Membership $membership)
    {
        // Validasi dengan rule unique (ignore ID saat ini)
        $request->validate([
            'name' => 'required|string|max:255|' . Rule::unique('memberships')->ignore($membership->id),
            'code' => ['required', 'string', 'max:20', Rule::unique('memberships', 'code')->ignore($membership->id)],
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'max_person' => 'nullable|integer|min:1',
            'max_access' => 'nullable|integer|min:0',
            'gates' => 'required|array',
            'gates.*' => 'exists:gate_accesses,id'
        ]);

        try {
            DB::beginTransaction();

            $harga = $request->price;
            $setting = Setting::asObject();

            // Hitung PPN
            $usePpn = $request->use_ppn == "on" ? 1 : 0;
            $calculatedPpn = $usePpn ? ($harga * $setting->ppn / 100) : 0;

            $data = $request->all();
            $data['code'] = strtoupper(trim((string) $request->code));
            $data['use_ppn'] = $usePpn;
            $data['ppn'] = $calculatedPpn;
            $data['max_access'] = $request->max_access ?? 0;

            $membership->update($data);

            $membership->gates()->sync($request->gates);

            DB::commit();

            return back()->with('success', "Membership {$membership->name} berhasil diupdate");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Hapus data membership
     */
    public function destroy(Membership $membership)
    {
        try {
            // Safety Check: Cek apakah membership ini sedang dipakai oleh member
            // (Asumsi relasi di model Membership: public function members())
            if (method_exists($membership, 'members') && $membership->members()->count() > 0) {
                return back()->with('error', "Gagal! Membership {$membership->name} masih digunakan oleh member aktif.");
            }

            DB::beginTransaction();
            // Penting: Hapus relasi di tabel pivot sebelum delete membership
            $membership->gates()->detach();
            $membership->delete();
            DB::commit();

            return back()->with('success', "Membership {$membership->name} berhasil didelete");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }
}
