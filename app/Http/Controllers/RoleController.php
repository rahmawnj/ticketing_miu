<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\DataTables;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:management-access');
    }

    public function index()
    {
        $title = 'Data Role';
        $breadcrumbs = ['Access', 'Data Role'];
        $permissions = Permission::get();

        return view('role.index', compact('title', 'breadcrumbs', 'permissions'));
    }

    public function get(Request $request)
    {
        if ($request->ajax()) {
            $data = Role::get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<a href="#modal-detail" id="' . $row->id . '" class="btn btn-sm btn-info btn-detail" data-route="' . route('roles.show', $row->id) . '" data-bs-toggle="modal">Detail</a> <a href="#modal-dialog" id="' . $row->id . '" class="btn btn-sm btn-success btn-edit" data-route="' . route('roles.update', $row->id) . '" data-bs-toggle="modal">Edit</a> <button type="button" data-route="' . route('roles.destroy', $row->id) . '" class="delete btn btn-danger btn-delete btn-sm">Delete</button>';
                    return $actionBtn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:roles'
            ]);

            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->name,
                'guard_name' => 'web'
            ]);

            $role->syncPermissions($request->permission);

            DB::commit();

            return redirect()->route('roles.index')->with('success', "{$role->name} berhasil ditambahkan");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function show(Role $role)
    {
        return response()->json([
            'status' => 'success',
            'role' => $role,
            'permission' => $role->permissions()->pluck('id'),
            'permissions' => $role->permissions
        ]);
    }

    public function update(Request $request, Role $role)
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:roles,name,' . $role->id
            ]);

            DB::beginTransaction();

            $role->update([
                'name' => $request->name,
            ]);

            $role->syncPermissions($request->permission);

            DB::commit();

            return redirect()->route('roles.index')->with('success', "{$role->name} berhasil diupdate");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function destroy(Role $role)
    {
        try {
            DB::beginTransaction();

            $role->delete();

            DB::commit();

            return redirect()->route('roles.index')->with('success', "{$role->name} berhasil dihapus");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }
}
