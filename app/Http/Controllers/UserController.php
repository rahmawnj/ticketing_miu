<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:user-access');
    }

    public function index()
    {
        $title = 'Data User';
        $breadcrumbs = ['Master', 'Data User'];
        $roles = Role::get();

        return view('user.index', compact('title', 'breadcrumbs', 'roles'));
    }

    public function get(Request $request)
    {
        if ($request->ajax()) {
            $data = User::orderBy('name', 'asc')->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<a href="#modal-dialog" id="' . $row->id . '" class="btn btn-sm btn-success btn-edit" data-route="' . route('users.update', $row->id) . '" data-bs-toggle="modal">Edit</a> <button type="button" data-route="' . route('users.destroy', $row->id) . '" class="delete btn btn-danger btn-delete btn-sm">Delete</button>';
                    return $actionBtn;
                })
                ->editColumn('role', function ($row) {
                    return $row->roles()->first() ? $row->roles()->first()->name : '-';
                })
                ->editColumn('is_active', function ($row) {
                    return $row->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
                })
                ->rawColumns(['action', 'is_active'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users',
            'name'     => 'required|string',
            'password' => 'required|string|min:6',
            'uid'      => 'nullable|unique:users',
            'role'     => 'required|numeric',
            'status'   => 'required|numeric',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'username'  => $request->username,
                'uid'       => $request->uid,
                'name'      => $request->name,
                'password'  => bcrypt($request->password),
                'is_active' => $request->status,
            ]);

            $user->syncRoles($request->role);

            DB::commit();
            return back()->with('success', "User {$user->username} berhasil ditambahkan");

        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan sistem: ' . $th->getMessage());
        }
    }

    public function show(User $user)
    {
        return response()->json([
            'status' => 'success',
            'user'   => $user,
            'role'   => $user->roles()->first() ? $user->roles()->first()->id : null
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'username' => 'required|string|unique:users,username,' . $user->id,
            'name'     => 'required|string',
            'uid'      => 'nullable|unique:users,uid,' . $user->id,
            'role'     => 'required|numeric',
            'status'   => 'required|numeric',
        ]);

        try {
            DB::beginTransaction();

            $dataUpdate = [
                'username'  => $request->username,
                'name'      => $request->name,
                'uid'       => $request->uid,
                'is_active' => $request->status,
            ];

            if ($request->filled('password')) {
                $dataUpdate['password'] = bcrypt($request->password);
            }

            $user->update($dataUpdate);
            $user->syncRoles($request->role);

            DB::commit();
            return back()->with('success', "User {$user->username} berhasil diupdate");

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return back()->withInput()->with('error', 'Gagal update: ' . $th->getMessage());
        }
    }

    public function destroy(User $user)
    {
        try {
            DB::beginTransaction();

            // Hapus relasi jika perlu sebelum delete user
            $user->delete();

            DB::commit();
            return back()->with('success', "User {$user->username} berhasil didelete");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus: ' . $th->getMessage());
        }
    }
}
