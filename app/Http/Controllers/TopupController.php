<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Topup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TopupController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:topup-access');
    }


    public function index()
    {
        $title = 'Data Topup';
        $breadcrumbs = ['Access', 'Data Topup'];

        return view('topup.index', compact('title', 'breadcrumbs',));
    }

    public function get(Request $request)
    {
        if ($request->ajax()) {
            $data = Topup::get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<a href="#modal-detail" id="' . $row->id . '" class="btn btn-sm btn-info btn-detail" data-route="' . route('roles.show', $row->id) . '" data-bs-toggle="modal">Detail</a> <a href="#modal-dialog" id="' . $row->id . '" class="btn btn-sm btn-success btn-edit" data-route="' . route('roles.update', $row->id) . '" data-bs-toggle="modal">Edit</a> <button type="button" data-route="' . route('roles.destroy', $row->id) . '" class="delete btn btn-danger btn-delete btn-sm">Delete</button>';
                    return $actionBtn;
                })
                ->editColumn('tanggal', function ($row) {
                    return Carbon::parse($row->created_at)->format('d/m/Y H:i:s');
                })
                ->editColumn('member', function ($row) {
                    return $row->member->nama;
                })
                ->editColumn('jumlah', function ($row) {
                    return 'Rp. ' . number_format($row->jumlah, 0, ',', '.');
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'rfid' => 'required|string',
                'jumlah' => 'required|numeric'
            ]);

            DB::beginTransaction();

            $member = Member::where('rfid', $request->rfid)->first();

            $topup = Topup::create([
                'member_id' => $member->id,
                'jumlah' => $request->jumlah
            ]);

            $member->update([
                'saldo' => $member->saldo + $topup->jumlah
            ]);

            DB::commit();

            return back()->with('success', "Topup saldo berhasil");
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
}
