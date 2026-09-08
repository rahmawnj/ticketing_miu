<?php

namespace App\Http\Controllers;

use App\Models\HistoryMembership;
use Illuminate\Http\Request;

class HistoryMembershipController extends Controller
{
    public function index()
    {
        $title = 'History Membership';
        $breadcrumbs = ["Report", "History Membership"];

        return view('history-membership.index', compact('title', 'breadcrumbs'));
    }

    function list(Request $request)
    {
        if ($request->ajax()) {
            $from = $request->get('from') ?? now()->format('Y-m-d');
            $to = $request->get('to') ?? now()->format('Y-m-d');

            $data = HistoryMembership::with(['member', 'membership'])
                ->whereBetween('start_date', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->orderBy('start_date', 'desc')
                ->get();

            return datatables()->of($data)
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    return $row->status == 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
                })
                ->rawColumns(['status'])
                ->make(true);
        }
    }
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\HistoryMembership  $historyMembership
     * @return \Illuminate\Http\Response
     */
    public function show(HistoryMembership $historyMembership)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\HistoryMembership  $historyMembership
     * @return \Illuminate\Http\Response
     */
    public function edit(HistoryMembership $historyMembership)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\HistoryMembership  $historyMembership
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, HistoryMembership $historyMembership)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\HistoryMembership  $historyMembership
     * @return \Illuminate\Http\Response
     */
    public function destroy(HistoryMembership $historyMembership)
    {
        //
    }
}
