<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Setting;
use App\Models\Terusan;
use App\Models\GateAccess;
use App\Models\JenisTicket;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\TicketRequest;

class TicketController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ticket-access');
    }

    public function index()
    {
        $title = 'Data Ticket';
        $breadcrumbs = ['Master', 'Data Ticket'];

        return view('ticket.index', compact('title', 'breadcrumbs'));
    }

    public function get(Request $request)
    {
        if ($request->ajax()) {
            $data = Ticket::orderBy('tripod', 'asc')->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('action', function ($row) {
                    $actionBtn = '<a href="' . route('tickets.edit', $row->id) . '" class="btn btn-sm btn-success btn-edit">Edit</a> ';
                    $actionBtn .= '<button type="button" data-route="' . route('tickets.destroy', $row->id) . '" class="delete btn btn-danger btn-delete btn-sm">Delete</button>';

                    return $actionBtn;
                })
                ->editColumn('harga', function ($row) {
                    return 'Rp. ' . number_format($row->harga + $row->ppn, 0, ',', '.');
                })
                ->editColumn('jenis', function ($row) {
                    return $row->jenis->nama_jenis;
                })
                ->editColumn('use_ppn', function ($row) {
                    return '<input class="form-check-input" type="checkbox" disabled ' . ($row->use_ppn == 1 ? 'checked' : '') . '/>';
                })
                ->rawColumns(['action', 'use_ppn'])
                ->make(true);
        }
    }

    public function create()
    {
        $title = 'Add Ticket';
        $breadcrumbs = ['Master', 'Add Ticket'];
        $action = route('tickets.store');
        $method = 'POST';
        $ticket = new Ticket();
        $jenis = JenisTicket::get();
        $jenisRegulerId = (int) (JenisTicket::query()->where('nama_jenis', 'Reguler')->value('id') ?? 1);
        $terusan = Terusan::get();
        $setting = Setting::asObject();
        $gates = GateAccess::whereIsActive(1)->get();

        return view('ticket.form', compact('title', 'breadcrumbs', 'action', 'method', 'ticket', 'jenis', 'terusan', 'setting', 'gates', 'jenisRegulerId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'harga' => 'required|numeric',
            'jenis' => 'required|numeric',
            'gate_id' => 'required|exists:gate_accesses,id',
        ]);

        try {
            DB::beginTransaction();

            $harga = $request->harga;
            $ppn = Setting::asObject();

            if ($request->ppn && $request->ppn == "on") {
                $ppn = $harga * $ppn->ppn / 100;
            }

            $ticket = Ticket::create([
                'name' => $request->name,
                'harga' => $harga,
                'jenis_ticket_id' => $request->jenis,
                'tripod' => $request->gate_id,
                'use_ppn' => $request->ppn == "on" ? 1 : 0,
                'ppn' => $request->ppn == "on" ? $ppn : 0,
            ]);

            $ticket->terusan()->sync($request->terusan);

            DB::commit();

            return redirect()->route('tickets.index')->with('success', "Ticket {$ticket->name} berhasil ditambahkan");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function show(Ticket $ticket)
    {
        $title = 'Data Ticket';
        $breadcrumbs = ['Master', 'Data Ticket'];
        $action = 'asd';
        $method = 'post';

        return view('ticket.show', compact('ticket', 'title', 'breadcrumbs', 'action', 'method'));
    }

    public function edit(Ticket $ticket)
    {
        $title = 'Edit Ticket';
        $breadcrumbs = ['Master', 'Edit Ticket'];
        $action = route('tickets.update', $ticket->id);
        $method = 'PUT';
        $jenis = JenisTicket::get();
        $jenisRegulerId = (int) (JenisTicket::query()->where('nama_jenis', 'Reguler')->value('id') ?? 1);
        $terusan = Terusan::get();
        $setting = Setting::asObject();
        $gates = GateAccess::whereIsActive(1)->get();

        return view('ticket.form', compact('title', 'breadcrumbs', 'action', 'method', 'ticket', 'jenis', 'terusan', 'setting', 'gates', 'jenisRegulerId'));
    }

    public function update(TicketRequest $request, Ticket $ticket)
    {
        $request->validate([
            'name' => 'required|string',
            'harga' => 'required|numeric',
            'jenis' => 'required|numeric',
            'gate_id' => 'required|exists:gate_accesses,id',
        ]);


        try {
            DB::beginTransaction();

            $harga = $request->harga;
            $ppn = Setting::asObject();

            $ppn = $harga * $ppn->ppn / 100;

            $ticket->update([
                'name' => $request->name,
                'harga' => $harga,
                'jenis_ticket_id' => $request->jenis,
                'tripod' => $request->gate_id,
                'use_ppn' => $request->ppn == "on" ? 1 : 0,
                'ppn' => $request->ppn == "on" ? $ppn : 0,
            ]);

            $ticket->terusan()->sync($request->terusan);

            DB::commit();

            return redirect()->route('tickets.index')->with('success', "Ticket {$ticket->name} berhasil diupdate");
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::info($th);
            return back()->with('error', $th->getMessage());
        }
    }

    public function destroy(Ticket $ticket)
    {
        try {
            DB::beginTransaction();

            $ticket->terusan()->detach();
            $ticket->delete();

            DB::commit();

            return redirect()->route('tickets.index')->with('success', "Ticket {$ticket->name} berhasil dihapus");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function find(Ticket $ticket)
    {
        return response()->json([
            'status' => 'success',
            'ticket' => $ticket
        ], 200);
    }
}
