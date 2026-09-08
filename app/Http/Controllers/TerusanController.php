<?php

namespace App\Http\Controllers;

use App\Models\Terusan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TerusanController extends Controller
{
    public function index()
    {
        $title = 'Data Terusan';
        $breadcrumbs = ['Master', 'Data Terusan'];

        return view('terusan.index', compact('title', 'breadcrumbs'));
    }

    public function get(Request $request)
    {
        if ($request->ajax()) {
            $data = Terusan::orderBy('name', 'asc')->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('action', function ($row) {
                    $actionBtn = '<a href="#modal-dialog" id="' . $row->id . '" class="btn btn-sm btn-success btn-edit" data-route="' . route('terusan.update', $row->id) . '" data-bs-toggle="modal">Edit</a> <button type="button" data-route="' . route('terusan.destroy', $row->id) . '" class="delete btn btn-danger btn-delete btn-sm">Delete</button>';

                    return $actionBtn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'tripod' => 'required|unique:terusans',
        ]);

        try {
            DB::beginTransaction();

            $terusan = Terusan::create([
                'name' => $request->name,
                'tripod' => $request->tripod,
            ]);

            DB::commit();

            return back()->with('success', "{$terusan->name} berhasil ditambahkan");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function show(Terusan $terusan)
    {
        return response()->json([
            'status' => 'success',
            'terusan' => $terusan
        ]);
    }

    public function update(Request $request, Terusan $terusan)
    {
        $request->validate([
            'name' => 'required|string',
            'tripod' => 'required|unique:terusans,tripod,' . $terusan->id,
        ]);

        try {
            DB::beginTransaction();

            $terusan->update([
                'name' => $request->name,
                'tripod' => $request->tripod,
            ]);

            DB::commit();

            return back()->with('success', "{$terusan->name} berhasil ditambahkan");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function destroy(Terusan $terusan)
    {
        try {
            DB::beginTransaction();

            $terusan->delete();

            DB::commit();

            return back()->with('success', "Data terusan berhasil didelete");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }
}
