<?php

namespace App\Http\Controllers;

use App\Models\MembershipAdminFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class MembershipAdminFeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:master-access');
    }

    public function index()
    {
        $title = 'Jenis Admin';
        $breadcrumbs = ['Master', 'Jenis Admin'];

        return view('membership-admin-fees.index', compact('title', 'breadcrumbs'));
    }

    public function get(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $data = MembershipAdminFee::query()->orderByDesc('id');

        return DataTables::eloquent($data)
            ->addIndexColumn()
            ->editColumn('admin_fee', function (MembershipAdminFee $row) {
                return 'Rp ' . number_format((int) ($row->admin_fee ?? 0), 0, ',', '.');
            })
            ->addColumn('action', function (MembershipAdminFee $row) {
                $editUrl = route('membership-admin-fees.update', $row->id);
                $showUrl = route('membership-admin-fees.show', $row->id);
                $deleteUrl = route('membership-admin-fees.destroy', $row->id);

                return '<a href="#modal-dialog" id="' . $row->id . '" class="btn btn-sm btn-success btn-edit me-1" data-route="' . $editUrl . '" data-show-route="' . $showUrl . '" data-bs-toggle="modal">Edit</a>'
                    . '<button type="button" data-route="' . $deleteUrl . '" class="delete btn btn-danger btn-delete btn-sm">Delete</button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'admin_type' => [
                'required',
                'string',
                'max:100',
                Rule::unique('membership_admin_fees', 'admin_type'),
            ],
            'admin_fee' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            MembershipAdminFee::create([
                'admin_type' => trim((string) $validated['admin_type']),
                'admin_fee' => (int) $validated['admin_fee'],
            ]);

            DB::commit();

            return back()->with('success', 'Jenis admin berhasil ditambahkan.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function show(MembershipAdminFee $membershipAdminFee)
    {
        return response()->json([
            'status' => 'success',
            'data' => $membershipAdminFee,
        ]);
    }

    public function update(Request $request, MembershipAdminFee $membershipAdminFee)
    {
        $validated = $request->validate([
            'admin_type' => [
                'required',
                'string',
                'max:100',
                Rule::unique('membership_admin_fees')
                    ->ignore($membershipAdminFee->id, 'id'),
            ],
            'admin_fee' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            $membershipAdminFee->update([
                'admin_type' => trim((string) $validated['admin_type']),
                'admin_fee' => (int) $validated['admin_fee'],
            ]);

            DB::commit();

            return back()->with('success', 'Jenis admin berhasil diupdate.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function destroy(MembershipAdminFee $membershipAdminFee)
    {
        try {
            $membershipAdminFee->delete();
            return back()->with('success', 'Jenis admin berhasil dihapus.');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
}
