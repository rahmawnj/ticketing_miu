<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Member;
use App\Models\History;
use App\Models\Setting;
use App\Models\Membership;
use App\Models\LimitMember;
use App\Models\Transaction;
use App\Models\MembershipAdminFee;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Imports\MemberImport;
use App\Models\DetailTransaction;
use App\Models\HistoryMembership;
use App\Models\WhatsappNotificationLog;
use App\Support\PaymentMethod;
use App\Exports\MemberExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Requests\Member\CreateMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;

class MemberController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:member-access')->except(['findOne']);
    }

    public function index()
    {
        $title = 'Data Member';
        $breadcrumbs = ['Master', 'Data Member'];
        $limit = LimitMember::first() ?? new LimitMember();
        $memberships = Membership::whereIsActive(1)->get();

        return view('member.index', compact('title', 'breadcrumbs', 'limit', 'memberships'));
    }

    public function get(Request $request)
    {
        if ($request->ajax()) {
           $filter = $request->get('filter', 'all');
           $membershipId = (int) $request->get('membership_id', 0);
           $statusFilter = (string) $request->get('status_filter', 'all');
           $allowedStatusFilters = ['all', 'active', 'expired'];
           if (!in_array($statusFilter, $allowedStatusFilters, true)) {
               $statusFilter = 'all';
           }

            // 2. Tentukan basis query
            $data = Member::with('membership')->orderBy('nama', 'asc');

            // 3. Terapkan filter berdasarkan nilai $filter
            if ($filter === 'member') {
                // Filter untuk Anggota Utama (parent_id = 0 atau NULL, asumsikan 0)
                $data->where('parent_id', 0);
            } elseif ($filter === 'submember') {
                // Filter untuk Anggota Grup (parent_id != 0 atau NULL)
                $data->where('parent_id', '!=', 0);
            }

            if ($membershipId > 0) {
                $data->where('membership_id', $membershipId);
            }
            $today = Carbon::now('Asia/Jakarta')->startOfDay();

            if ($statusFilter === 'active') {
                $data
                    ->whereDate('tgl_expired', '>=', $today->toDateString())
                    ->where('is_active', 1);
            } elseif ($statusFilter === 'expired') {
                $data->where(function ($query) use ($today) {
                    $query
                        ->where('is_active', '!=', 1)
                        ->orWhereDate('tgl_expired', '<', $today->toDateString());
                });
            }

            return DataTables::eloquent($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $buttons = [];
                    // <a href="#modal-dialog-membership" id="' . $row->id . '" class="btn btn-xs btn-outline-secondary btn-membership" data-route="' . route('members.show', $row->id) . '" data-bs-toggle="modal"><i class="fas fa-user-circle"></i></a>
                    $buttons[] = '<a href="#modal-dialog-info" id="' . $row->id . '" class="btn btn-xs btn-outline-info btn-show" data-route="' . route('members.show', $row->id) . '" data-bs-toggle="modal"><i class="fas fa-eye"></i></a>';

                    if ($row->membership_id != 0 || $row->membership != null) {
                        $buttons[] = '<a href="' . route('members.invoice', $row->id) . '" target="_blank" id="" class="btn btn-xs btn-outline-secondary btn-invoice"><i class="fas fa-print"></i></a>';
                    }

                    $buttons[] = '<a href="' . route('members.edit', $row->id) . '" id="' . $row->id . '" class="btn btn-xs btn-outline-success"><i class="fas fa-edit"></i></a>';
                    $buttons[] = '<button type="button" data-route="' . route('members.destroy', $row->id) . '" class="delete btn btn-outline-danger btn-delete btn-xs"><i class="fas fa-trash"></i></button>';

                    return '<div class="d-inline-flex flex-nowrap align-items-center gap-1 member-action-buttons">' . implode('', $buttons) . '</div>';
                })
                ->editColumn('harga', function ($row) {
                    return 'Rp. ' . number_format($row->harga, 0, ',', '.');
                })
                ->editColumn('no_ktp', function($row) {
                return  $row->no_ktp ;
            })
            // Tambahkan no_hp jika kolomnya ada di database
            ->editColumn('no_hp', function($row) {
                return $row->no_hp ?? '-';
            })
               ->addColumn('member_code', function ($row) {
                    return $row->display_member_code ?? '-';
                })
               ->editColumn('expired', function ($row) {
                    $today = Carbon::now('Asia/Jakarta')->startOfDay();
                    $expiredAt = Carbon::parse($row->tgl_expired)->startOfDay();
                    $isActive = ((int) $row->is_active === 1) && $expiredAt->greaterThanOrEqualTo($today);

                    return $isActive
                        ? '<span class="badge fs-12px bg-success">Active</span>'
                        : '<span class="badge fs-12px bg-danger">Expired</span>';
                })
                ->editColumn('masa_berlaku', function ($row) {
                    return Carbon::parse($row->tgl_register)->format('d/m/Y') . ' - ' . Carbon::parse($row->tgl_expired)->format('d/m/Y');
                })
                ->editColumn('sisa_hari', content: function ($row) {
                    $today = Carbon::now('Asia/Jakarta')->startOfDay();
                    $expiredAt = Carbon::parse($row->tgl_expired)->startOfDay();
                    $sisa = $today->diffInDays($expiredAt, false);

                    if ($sisa < 0) {
                        return '<span class="badge bg-danger rounded-0 fs-11px">Expired</span>';
                    }

                    return $sisa . ' Hari';
                })
                ->editColumn('qr_code', function ($row) {
                    return QrCode::size(50)->generate($row->qr_code);
                })
                ->addColumn('membership_name', function ($row) {
                    if ($row->membership_id != 0) {
                        return '<span class="badge bg-primary rounded-0 fs-11px">' . $row->membership->name . '</span>';
                    } else {
                        return '<span class="badge bg-default text-black rounded-0 fs-11px">Pilih Membership</span>';
                    }
                })
                ->addColumn('image_profile', function ($row) {
                    $imageUrl = $row->image_profile != null
                        ? asset('/storage/' . $row->image_profile)
                        : asset('/img/user/user-10.jpg');
                    $membershipName = $row->membership ? $row->membership->name : '-';
                    $memberType = $row->parent_id == 0 ? 'Member' : 'Submember';
                    return '<img src="' . $imageUrl . '" data-full="' . $imageUrl . '" data-name="' . e($row->nama) . '" data-phone="' . e($row->no_hp ?? '-') . '" data-ktp="' . e($row->no_ktp ?? '-') . '" data-rfid="' . e($row->rfid ?? '-') . '" data-membership="' . e($membershipName) . '" data-type="' . e($memberType) . '" alt="" class="rounded h-40px js-photo-thumb">';
                })
                ->addColumn('member_type', function ($row) {
                    if ($row->parent_id == 0) {
                        return '<span class="badge bg-info rounded-0 fs-11px">Member</span>';
                    } else {
                        return '<span class="badge bg-warning text-black rounded-0 fs-11px">Submember</span>';
                    }
                })
                ->rawColumns(['action', 'expired', 'qr_code', 'membership_name', 'sisa_hari', 'member_type', 'image_profile'])
                ->make(true);
        }
    }

    public function export(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $membershipId = (int) $request->get('membership_id', 0);
        $statusFilter = (string) $request->get('status_filter', 'all');

        $filename = 'member_export_' . now('Asia/Jakarta')->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new MemberExport($filter, $membershipId, $statusFilter),
            $filename
        );
    }

    function create()
    {
        $title = 'Create Member';
        $breadcrumbs = ['Master', 'Data Member', 'Create Member'];
        $memberships = Membership::whereIsActive(1)->get();
        $adminFeeOptions = $this->adminFeeOptions();
        $member = new Member();
        $action = route('members.store');
        $method = 'POST';

        return view('member.form', compact('title', 'breadcrumbs', 'memberships', 'adminFeeOptions', 'member', 'action', 'method'));
    }

public function store(CreateMemberRequest $request)
    {
        DB::beginTransaction();
        try {
            $attr = $request->validated();
            $membership = Membership::find($request->membership);

            // Jika membership tidak ditemukan
            if (!$membership) {
                 throw new \Exception("Jenis Member tidak valid.");
            }

            $attr['tgl_lahir'] = $request->tanggal_lahir;
            $attr['tgl_register'] = now('Asia/Jakarta')->format('Y-m-d');
            $attr['tgl_expired'] = now('Asia/Jakarta')->addDay($membership->duration_days)->format('Y-m-d');
            $attr['qr_code'] = "MBR" . strtoupper(Str::random(13));
            $memberCodePrefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($membership->code ?? '')));
            if ($memberCodePrefix === '') {
                $memberCodePrefix = 'MSH';
            }
            // Isi sementara agar insert tidak gagal jika kolom member_code NOT NULL.
            $attr['member_code'] = $memberCodePrefix . '/TEMP-' . strtoupper(Str::random(8));
            $attr['membership_id'] = $request->membership;
            $attr['is_active'] = 1;
            $attr['parent_id'] = 0;
            $attr['access_used'] = 0;

            if ($request->image_profile) {
                $image = $request->file('image_profile');
                // Gunakan RFID dari request atau buat random jika tidak ada
                $rfid = $request->rfid ?? Str::random(10);
                $attr['image_profile'] =
                    $image->storeAs('members', $rfid . now('Asia/Jakarta')->format('YmdHis') . '.' . $image->extension());
            }

            $member = Member::create($attr);
            $member->update([
                'member_code' => sprintf('%s/%04d', $memberCodePrefix, (int) $member->id),
                'qr_code' => sprintf('%s/%04d', $memberCodePrefix, (int) $member->id),
            ]);

            HistoryMembership::create([
                'member_id' => $member->id,
                'membership_id' => $membership->id,
                'start_date' => $member->tgl_register,
                'end_date' => $member->tgl_expired,
                'status' => 'active',
            ]);

            $totalMembersRegistered = 1;

            if ($request->rfid_group) {
                $parentExpiredDate = $member->tgl_expired;
                $parentRegisterDate = $member->tgl_register;
                foreach ($request->rfid_group as $key => $rfid_group) {
                    $name_group = $request->name_group[$key] ?? null;

                    // Guard: lewati baris kosong saat input anggota grup tidak diisi.
                    if (!filled($rfid_group) && !filled($name_group)) {
                        continue;
                    }

                    $image_group = null;

                    if (isset($request->file('image_group')[$key])) {
                        $image = $request->file('image_group')[$key];
                        $image_group =
                            $image->storeAs('members', ($rfid_group ?? Str::random(10)) . now('Asia/Jakarta')->format('YmdHis') . '.' . $image->extension());
                    }

                    $child = Member::create([
                        'rfid' => $rfid_group,
                        'nama' => $name_group,
                        'alamat' => $member->alamat,
                        'no_ktp' => $member->no_ktp,
                        'no_hp' => $member->no_hp,
                        'tgl_lahir' => $member->tgl_lahir,
                        'jenis_kelamin' => $member->jenis_kelamin,

                        'membership_id' => $member->membership_id,
                        'tgl_register' => $parentRegisterDate,
                        // Guard: masa aktif child wajib mengikuti parent.
                        'tgl_expired' => $parentExpiredDate,
                        'qr_code' => "MBR" . strtoupper(Str::random(13)),
                        'member_code' => $memberCodePrefix . '/TEMP-' . strtoupper(Str::random(8)),
                        'is_active' => 1,
                        'image_profile' => $image_group,
                        'parent_id' => $member->id,
                        'access_used' => 0,
                    ]);

                    $child->update([
                        'member_code' => sprintf('%s/%04d', $memberCodePrefix, (int) $child->id),
                        'qr_code' => sprintf('%s/%04d', $memberCodePrefix, (int) $child->id),
                    ]);

                    HistoryMembership::create([
                        'member_id' => $child->id,
                        'membership_id' => $membership->id,
                        'start_date' => $parentRegisterDate,
                        'end_date' => $parentExpiredDate,
                        'status' => 'active',
                    ]);

                    $totalMembersRegistered++;
                }
            }

            $basePricePerMember = (float) $membership->price;
            $ppnAmount = $membership->use_ppn ? ((float) $membership->ppn) : 0;
            $validatedPayment = $request->validate([
                'metode' => ['required', Rule::in(PaymentMethod::coreValidationValues())],
                'admin_fee_master_id' => 'nullable|integer',
            ]);
            $resolvedAdminFee = $this->resolveSelectedAdminFee($validatedPayment['admin_fee_master_id'] ?? null);
            $registrationAdminFee = $resolvedAdminFee['admin_fee'];

            $now = Carbon::now('Asia/Jakarta');
            $notrx = Transaction::nextNoTrxByType('registration', $now);

            $metode = PaymentMethod::normalize($validatedPayment['metode']);
            $isCardMethod = in_array($metode, ['debit', 'kredit'], true);
            if ($isCardMethod) {
                $request->validate([
                    'nama_kartu' => 'required|string|max:100',
                    'no_kartu' => 'required|string|max:100',
                    'bank' => 'required|string|max:100',
                ]);
            }

            $transaction = Transaction::create([
                'user_id' => auth()->id() ?? 1,
                'no_trx' => $notrx,
                'ticket_code' => Transaction::buildTicketCodeByType('registration', $now, $notrx),
                'tipe' => ($totalMembersRegistered > 1) ? 'group' : 'individual',
                'amount' => $totalMembersRegistered,
                'discount' => 0,
                'transaction_type' => 'registration',
                'ppn' => $ppnAmount,
                'admin_fee' => $registrationAdminFee,
                'bayar' => $basePricePerMember,
                'status' => 'open',
                'is_active' => 1,
                'metode' => $metode,
                'nama_kartu' => $isCardMethod ? $request->nama_kartu : null,
                'no_kartu' => $isCardMethod ? $request->no_kartu : null,
                'bank' => $isCardMethod ? $request->bank : null,
                'ticket_id'=> $membership->id,
                'member_id' => $member->id,
                'member_info' => $member->nama . ' - ' . $member->no_hp
            ]);

            $this->enqueueWhatsappNotificationFromTransaction($transaction, $member);

            $invoiceUrl = route('transactions.invoice', $transaction->id);

            // DetailTransaction::create([
            //     'transaction_id' => $transaction->id,
            //     'ticket_id' => $membership->id,
            //     'qty' => $totalMembersRegistered,
            //     'total' => $basePricePerMember,
            //     'ppn' => $ppnAmount,
            // ]);

            DB::commit();
            return redirect()->route('members.index')
                ->with('success', "Member {$member->nama} berhasil ditambahkan")
                ->with('invoice_url', $invoiceUrl);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error creating member: " . $th->getMessage());
            return back()->with('error', $th->getMessage());
        }
    }

    public function show(Member $member)
    {
        $member->load('membership');
        $member->image_profile = $member->image_profile
            ? asset('storage/' . $member->image_profile)
            : asset('img/user/user-10.jpg');

        $ownerMember = $member;
        $memberIdsForHistory = [$member->id];

        if ((int) $member->parent_id === 0) {
            $childIds = Member::where('parent_id', $member->id)->pluck('id')->all();
            $memberIdsForHistory = array_merge($memberIdsForHistory, $childIds);
        } else {
            $parentMember = Member::find($member->parent_id);
            if ($parentMember) {
                $ownerMember = $parentMember;
                $memberIdsForHistory[] = $parentMember->id;
            }
        }

        $ownerMember->loadMissing('membership');
        $familyMembers = collect([$ownerMember])
            ->merge(Member::where('parent_id', $ownerMember->id)->get())
            ->unique('id')
            ->map(function ($family) use ($member) {
                $isCurrentMember = (int) $family->id === (int) $member->id;
                $relation = ((int) $family->parent_id === 0) ? 'Member' : 'Sub Member';

                return [
                    'id' => $family->id,
                    'nama' => $family->nama ?? '-',
                    'rfid' => $family->rfid ?? '-',
                    'no_hp' => $family->no_hp ?? '-',
                    'tgl_expired' => $family->tgl_expired ?? '-',
                    'relation' => $relation,
                    'is_current' => $isCurrentMember,
                ];
            })
            ->values();

        $paymentHistory = Transaction::with('user')
            ->whereIn('member_id', array_values(array_unique($memberIdsForHistory)))
            ->whereIn('transaction_type', ['registration', 'renewal'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($trx) {
                return [
                    'date' => optional($trx->created_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') ?? '-',
                    'invoice' => $trx->ticket_code ?? '-',
                    'type' => ucfirst($trx->transaction_type ?? '-'),
                    'method' => $trx->metode ?: '-',
                    'cashier' => $trx->user->name ?? '-',
                    'amount' => 'Rp. ' . number_format((float) ($trx->bayar ?? 0), 0, ',', '.'),
                    'ppn' => 'Rp. ' . number_format((float) ($trx->ppn ?? 0), 0, ',', '.'),
                    'admin_fee' => 'Rp. ' . number_format((float) ($trx->admin_fee ?? 0), 0, ',', '.'),
                    'total' => 'Rp. ' . number_format(((float) ($trx->bayar ?? 0)) + ((float) ($trx->ppn ?? 0)) + ((float) ($trx->admin_fee ?? 0)), 0, ',', '.'),
                ];
            })->values();

        $memberPayload = $member->toArray();
        $memberPayload['member_code'] = $member->display_member_code;

        return response()->json([
            'status' => 'success',
            'member' => $memberPayload,
            'qr_markup' => QrCode::size(100)->margin(0)->errorCorrection('H')->generate($member->qr_code),
            'payment_history' => $paymentHistory,
            'payment_history_owner' => [
                'id' => $ownerMember->id,
                'name' => $ownerMember->nama ?? '-',
            ],
            'payment_history_note' => 'Riwayat pembayaran membership masih dalam tahap development.',
            'family_members' => $familyMembers,
        ], 200);
    }

    function edit(Member $member)
    {
        $title = 'Edit Member';
        $breadcrumbs = ['Master', 'Data Member', 'Edit Member'];
        $memberships = Membership::whereIsActive(1)->get();
        $adminFeeOptions = $this->adminFeeOptions();
        $action = route('members.update', $member->id);
        $method = 'PUT';

        return view('member.form', compact('title', 'breadcrumbs', 'memberships', 'adminFeeOptions', 'member', 'action', 'method'));
    }

public function update(UpdateMemberRequest $request, Member $member)
    {

        try {
            DB::beginTransaction();
            $attr = $request->validated();
            $is_active = $request->has('is_active') ? 1 : 0;
            $is_membership_changed = false;
            $invoiceUrl = null;

            if ($member->parent_id == 0) {
                $newMembershipId = $request->membership;

                if ($member->membership_id != $newMembershipId) {

                    $is_membership_changed = true;
                    $membership = Membership::find($newMembershipId);

                    if (!$membership) {
                         throw new \Exception("Jenis Member baru tidak valid.");
                    }

                    $attr['tgl_lahir'] = $request->tanggal_lahir;
                    $attr['membership_id'] = $membership->id;
                    $attr['tgl_expired'] = now('Asia/Jakarta')->addDay($membership->duration_days)->format('Y-m-d');
                    $attr['access_used'] = 0;

                    HistoryMembership::where('member_id', $member->id)
                        ->where('status', 'active')
                        ->update(['status' => 'inactive']);

                    HistoryMembership::create([
                        'member_id' => $member->id,
                        'membership_id' => $membership->id,
                        'start_date' => now('Asia/Jakarta')->format('Y-m-d'),
                        'end_date' => $attr['tgl_expired'],
                        'status' => 'active',
                    ]);

                    $totalMembersRegistered = 1 + ($member->childs ? $member->childs->count() : 0);
                    $basePricePerMember = (float) $membership->price; // Harga dasar per member
                    $ppnAmount = $membership->use_ppn ? ((float) $membership->ppn) : 0;
                    $validatedPayment = $request->validate([
                        'metode' => ['required', Rule::in(PaymentMethod::coreValidationValues())],
                        'admin_fee_master_id' => 'nullable|integer',
                    ]);
                    $resolvedAdminFee = $this->resolveSelectedAdminFee($validatedPayment['admin_fee_master_id'] ?? null);
                    $registrationAdminFee = $resolvedAdminFee['admin_fee'];

                    $now = Carbon::now('Asia/Jakarta');
                    $notrx = Transaction::nextNoTrxByType('registration', $now);

                    $tipeTransaksi = ($totalMembersRegistered > 1) ? 'group' : 'individual';
                    $ticketCode = Transaction::buildTicketCodeByType('registration', $now, $notrx);

                    $metode = PaymentMethod::normalize($validatedPayment['metode']);
                    $isCardMethod = in_array($metode, ['debit', 'kredit'], true);
                    if ($isCardMethod) {
                        $request->validate([
                            'nama_kartu' => 'required|string|max:100',
                            'no_kartu' => 'required|string|max:100',
                            'bank' => 'required|string|max:100',
                        ]);
                    }

                    $transaction = Transaction::create([
                        'user_id' => auth()->id() ?? 1,
                        'no_trx' => $notrx,
                        'ticket_code' => $ticketCode,
                        'tipe' => $tipeTransaksi,
                        'amount' => $totalMembersRegistered,
                        'discount' => 0,
                        'transaction_type' => 'registration',
                        'ppn' => $ppnAmount,
                        'admin_fee' => $registrationAdminFee,
                        'bayar' => $basePricePerMember,
                        'status' => 'open',
                        'is_active' => 1,
                        'metode' => $metode,
                        'nama_kartu' => $isCardMethod ? $request->nama_kartu : null,
                        'no_kartu' => $isCardMethod ? $request->no_kartu : null,
                        'bank' => $isCardMethod ? $request->bank : null,
                        'ticket_id'=> $membership->id,
                        'member_id' => $member->id,
                        'member_info' => $member->nama . ' - ' . $member->no_hp

                    ]);

                    $this->enqueueWhatsappNotificationFromTransaction($transaction, $member);

                    $invoiceUrl = route('transactions.invoice', $transaction->id);

                    // DetailTransaction::create([
                    //     'transaction_id' => $transaction->id,
                    //     'ticket_id' => $membership->id,
                    //     'qty' => $totalMembersRegistered,
                    //     'total' => $basePricePerMember,
                    //     'ppn' => $ppnAmount,
                    // ]);
                }

                $attr['tgl_lahir'] = $request->tanggal_lahir;
            }

            $attr['is_active'] = $is_active;

            if ($request->image_profile) {
                // Hapus gambar lama jika ada
                if ($member->image_profile) {
                    \Illuminate\Support\Facades\Storage::delete($member->image_profile);
                }

                $image = $request->file('image_profile');
                $rfid = $request->rfid ?? $member->rfid;
                $attr['image_profile'] =
                    $image->storeAs('members', $rfid . now('Asia/Jakarta')->format('YmdHis') . '.' . $image->extension());
            } else {
                // Jika tidak ada upload, pertahankan gambar lama
                $attr['image_profile'] = $member->image_profile;
            }

            $member->update($attr);

            if ((int) $member->parent_id === 0) {
                $member->refresh();
                $parentExpiredDate = $member->tgl_expired;
                $parentMembershipId = $member->membership_id;

                // Guard + autosync: child selalu ikut parent untuk membership & expired date.
                if ($member->childs) {
                    foreach ($member->childs as $child) {
                        $child->update([
                            'is_active' => $is_active,
                            'membership_id' => $parentMembershipId,
                            'tgl_expired' => $parentExpiredDate,
                            'access_used' => $is_membership_changed ? 0 : $child->access_used,
                        ]);

                        if ($is_membership_changed) {
                            // Nonaktifkan riwayat membership lama child
                            HistoryMembership::where('member_id', $child->id)
                                ->where('status', 'active')
                                ->update(['status' => 'inactive']);

                            // Buat riwayat membership baru child
                            HistoryMembership::create([
                                'member_id' => $child->id,
                                'membership_id' => $parentMembershipId,
                                'start_date' => now('Asia/Jakarta')->format('Y-m-d'),
                                'end_date' => $parentExpiredDate,
                                'status' => 'active',
                            ]);
                        }
                    }
                }
            } else {
                // Validasi saat edit child: jika beda dengan parent, paksa autosync.
                $parent = Member::find($member->parent_id);
                if ($parent) {
                    $syncAttr = [];
                    if ((string) $member->tgl_expired !== (string) $parent->tgl_expired) {
                        $syncAttr['tgl_expired'] = $parent->tgl_expired;
                    }
                    if ((int) $member->membership_id !== (int) $parent->membership_id) {
                        $syncAttr['membership_id'] = $parent->membership_id;
                    }

                    if (!empty($syncAttr)) {
                        $member->update($syncAttr);
                    }
                }
            }

            // Jika status di-uncheck (nonaktif), nonaktifkan riwayat membership yang aktif saat ini
            if (!$request->is_active) {
                HistoryMembership::where('member_id', $member->id)
                    ->where('status', 'active')
                    ->first()
                    ?->update(['status' => 'inactive']);
            }

            DB::commit();
            return redirect()->route('members.index')
                ->with('success', "Member {$member->nama} berhasil diupdate")
                ->with('invoice_url', $invoiceUrl);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error updating member: " . $th->getMessage());
            return back()->with('error', $th->getMessage());
        }
    }


    public function destroy(Member $member)
    {
        try {
            DB::beginTransaction();

            // Kumpulkan semua turunan member (child, grandchild, dst jika ada).
            $memberIdsToDelete = [(int) $member->id];
            $queue = [(int) $member->id];
            while (!empty($queue)) {
                $currentParentId = array_shift($queue);
                $childIds = Member::where('parent_id', $currentParentId)->pluck('id')->map(fn ($id) => (int) $id)->all();

                foreach ($childIds as $childId) {
                    if (in_array($childId, $memberIdsToDelete, true)) {
                        continue;
                    }

                    $memberIdsToDelete[] = $childId;
                    $queue[] = $childId;
                }
            }

            // Hapus riwayat member & membership lebih dulu agar tidak menyisakan data orphan.
            History::whereIn('member_id', $memberIdsToDelete)->delete();
            HistoryMembership::whereIn('member_id', $memberIdsToDelete)->delete();

            // Hapus member utama beserta seluruh submember.
            Member::whereIn('id', $memberIdsToDelete)->delete();

            DB::commit();

            $deletedCount = count($memberIdsToDelete);
            return redirect()->route('members.index')->with('success', "Member {$member->nama} berhasil dihapus ({$deletedCount} data termasuk submember).");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function findOne(Request $request)
    {
        $member = Member::where('rfid', $request->rfid)->first();

        if ($member) {
            return response()->json([
                'status' => 'success',
                'member' => $member
            ]);
        } else {
            return response()->json([
                'status' => 'error',
            ]);
        }
    }

    function expired(Member $member)
    {
        try {
            DB::beginTransaction();

            if ($member->membership_id == 0) {
                return back()->with('error', 'Member belum berlangganan');
            }

            $member->update([
                'tgl_expired' => Carbon::now('Asia/Jakarta')->addDay($member->membership->duration_days)->format('Y-m-d'),
                'access_used' => 0
            ]);

            HistoryMembership::create([
                'member_id' => $member->id,
                'membership_id' => $member->membership_id,
                'start_date' => Carbon::now('Asia/Jakarta')->format('Y-m-d'),
                'end_date' => Carbon::now('Asia/Jakarta')->addDay($member->membership->duration_days)->format('Y-m-d'),
                'status' => 'active',
            ]);

            DB::commit();
            return redirect()->route('members.index')->with('success', "Member {$member->nama} berhasil diperpanjang");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    function update_setting(Request $request)
    {
        try {
            $limit = LimitMember::first();
            if ($limit) {
                $limit->update($request->all());
            } else {
                LimitMember::create($request->all());
            }

            DB::commit();
            return back()->with('success', "Setting member berhasil diupdate");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        try {
            DB::beginTransaction();

            Excel::import(new MemberImport, $request->file('file'));

            DB::commit();
            return back()->with('success', 'Success import member');
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    function download()
    {
        $path = public_path('example/example-import-member.xlsx');

        if (!File::exists($path)) {
            // Jika tidak ada, tampilkan halaman 404 Not Found
            abort(404, 'File not found.');
        }

        return Response::download($path);
    }

    function print_qr(Member $member)
    {
        $setting = Setting::asObject();
        $logoData = null;
        if ((int) ($setting->use_logo ?? 0) === 1 && !empty($setting->logo ?? null)) {
            $logoPath = public_path('storage/' . $setting->logo);
            if (is_file($logoPath)) {
                $logoBase64 = base64_encode(file_get_contents($logoPath));
                $logoExt = strtolower((string) pathinfo($logoPath, PATHINFO_EXTENSION));
                $logoMime = in_array($logoExt, ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/png';
                $logoData = 'data:' . $logoMime . ';base64,' . $logoBase64;
            }
        }

        return view('member.print-qr', compact('member', 'setting', 'logoData'));
    }

    function invoice(Member $member)
    {
        $ownerMemberId = (int) $member->parent_id !== 0 ? (int) $member->parent_id : (int) $member->id;
        $ownerMember = Member::with(['membership', 'childs'])->find($ownerMemberId) ?? $member->load(['membership', 'childs']);

        $transaction = Transaction::where('member_id', $ownerMemberId)
            ->whereIn('transaction_type', ['registration', 'renewal'])
            ->latest()
            ->first();

        if (!$transaction) {
            $qtyItem = 1 + (int) ($ownerMember->childs->count() ?? 0);
            $transaction = new Transaction([
                'ticket_code' => 'INV/' . now('Asia/Jakarta')->format('Ymd') . '/' . str_pad((string) $ownerMember->id, 4, '0', STR_PAD_LEFT),
                'transaction_type' => 'registration',
                'amount' => $qtyItem,
                'ppn' => (float) ((($ownerMember->membership->use_ppn ?? 0) ? ($ownerMember->membership->ppn ?? 0) : 0)),
                'admin_fee' => 0,
                'bayar' => (float) ($ownerMember->membership->price ?? 0),
                'metode' => '-',
            ]);
            $transaction->created_at = now('Asia/Jakarta');
        }

        $cashierName = $transaction->user?->name ?? (auth()->user()->name ?? '-');
        $setting = Setting::asObject();
        $ucapan = $setting->ucapan ?? 'Terima Kasih';
        $deskripsi = $setting->deskripsi ?? '';

        return view('member.invoice', [
            'member' => $ownerMember,
            'transaction' => $transaction,
            'cashierName' => $cashierName,
            'ucapan' => $ucapan,
            'deskripsi' => $deskripsi,
        ]);
    }

    public function invoicePdf(Member $member)
    {
        $ownerMemberId = (int) $member->parent_id !== 0 ? (int) $member->parent_id : (int) $member->id;
        $ownerMember = Member::with(['membership', 'childs'])->find($ownerMemberId) ?? $member->load(['membership', 'childs']);

        $transaction = Transaction::where('member_id', $ownerMemberId)
            ->whereIn('transaction_type', ['registration', 'renewal'])
            ->latest()
            ->first();

        if (!$transaction) {
            $qtyItem = 1 + (int) ($ownerMember->childs->count() ?? 0);
            $transaction = new Transaction([
                'ticket_code' => 'INV/' . now('Asia/Jakarta')->format('Ymd') . '/' . str_pad((string) $ownerMember->id, 4, '0', STR_PAD_LEFT),
                'transaction_type' => 'registration',
                'amount' => $qtyItem,
                'ppn' => (float) ((($ownerMember->membership->use_ppn ?? 0) ? ($ownerMember->membership->ppn ?? 0) : 0)),
                'admin_fee' => 0,
                'bayar' => (float) ($ownerMember->membership->price ?? 0),
                'metode' => '-',
            ]);
            $transaction->created_at = now('Asia/Jakarta');
        }

        $adminFee = max(0, (float) ($transaction->admin_fee ?? 0));
        $type = $adminFee > 0
            ? 'Perpanjangan Baru'
            : (($transaction->transaction_type ?? 'registration') === 'renewal' ? 'Perpanjangan' : 'Registrasi');
        $invoiceCode = $transaction->ticket_code ?? ('INV/' . now('Asia/Jakarta')->format('YmdHis'));
        $price = 'Rp. ' . number_format($ownerMember->membership->price ?? 0, 0, ',', '.');
        $date = optional($transaction->created_at)->format('d/m/Y H:i:s') ?? now('Asia/Jakarta')->format('d/m/Y H:i:s');
        $cashierName = $transaction->user?->name ?? (auth()->user()->name ?? '-');
        $autoPrint = request()->boolean('print');

        $setting = Setting::asObject();
        $appName = $setting->name ?? 'Ticketing App';
        $ucapan = $setting->ucapan ?? 'Terima Kasih';
        $deskripsi = $setting->deskripsi ?? '';
        $logoData = null;
        if ($setting && (int) ($setting->use_logo ?? 0) === 1 && !empty($setting->logo ?? null)) {
            $logoPath = public_path('storage/' . $setting->logo);
            if (is_file($logoPath)) {
                $logoBase64 = base64_encode(file_get_contents($logoPath));
                $logoExt = strtolower((string) pathinfo($logoPath, PATHINFO_EXTENSION));
                $logoMime = in_array($logoExt, ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/png';
                $logoData = 'data:' . $logoMime . ';base64,' . $logoBase64;
            }
        }

        return view('member.invoice-pdf', [
            'member' => $ownerMember,
            'type' => $type,
            'invoice_code' => $invoiceCode,
            'date' => $date,
            'price' => $price,
            'transaction' => $transaction,
            'app_name' => $appName,
            'logo_data' => $logoData,
            'cashier_name' => $cashierName,
            'ucapan' => $ucapan,
            'deskripsi' => $deskripsi,
            'auto_print' => $autoPrint,
        ]);
    }

    public function bulkRenewIndex(Request $request)
    {
        $title = 'Perpanjangan Member';
        $breadcrumbs = ['Member', 'Perpanjang Massal'];

        return view('member.bulk_renew_index', compact('title', 'breadcrumbs'));
    }

public function getRenewableMembers(Request $request)
{
    $today = Carbon::now('Asia/Jakarta')->startOfDay();

    // 1. Ambil settingan hari dari database
    $setting = Setting::asObject();
    $suspendBeforeDays = max((int) ($setting->member_suspend_before_days ?? 7), 1);
    $suspendAfterDays = max((int) ($setting->member_suspend_after_days ?? 0), 0);
    $adminFeeOptions = $this->adminFeeOptions();

    $data = $this->renewableMembersQuery($suspendBeforeDays);

    return DataTables::eloquent($data)
        ->filter(function ($query) use ($request) {
            $search = trim((string) data_get($request->input('search'), 'value', ''));
            if ($search === '') {
                return;
            }

            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%')
                    ->orWhere('no_hp', 'like', '%' . $search . '%')
                    ->orWhereHas('membership', function ($m) use ($search) {
                        $m->where('name', 'like', '%' . $search . '%');
                    });
            });
        })
        ->addIndexColumn()
        ->addColumn('package_price', function ($row) {
            if (!$row->membership) {
                return 'N/A';
            }

            return number_format((float) ($row->membership->price ?? 0), 0, ',', '.');
        })
        ->addColumn('renewal_status', function ($row) use ($today, $suspendAfterDays, $suspendBeforeDays) {
            $expiredAt = Carbon::parse($row->tgl_expired)->startOfDay();

            if ($today->greaterThan($expiredAt)) {
                $daysAfterExpired = $expiredAt->diffInDays($today);

                if ($daysAfterExpired > $suspendAfterDays) {
                    return '<span class="badge bg-danger">Expired (H+' . $daysAfterExpired . ') - Perpanjangan Baru</span>';
                }
                return '<span class="badge bg-warning text-dark">Suspend (H+' . $daysAfterExpired . ')</span>';
            }

            $sisaHari = $today->diffInDays($expiredAt);
            if ($sisaHari <= $suspendBeforeDays) {
                return '<span class="badge bg-success">Active (H-' . $sisaHari . ')</span>';
            }

            return '<span class="badge bg-success">Active</span>';
        })
        ->addColumn('action', function ($row) use ($suspendAfterDays, $adminFeeOptions) {
            $today = Carbon::now('Asia/Jakarta')->startOfDay();
            $expiredAt = Carbon::parse($row->tgl_expired)->startOfDay();
            $daysAfterExpired = $today->greaterThan($expiredAt) ? $expiredAt->diffInDays($today) : 0;
            $isRenewalBaru = $daysAfterExpired > $suspendAfterDays;

            $adminOptions = $adminFeeOptions;
            $defaultAdminFee = 0;

            $grossPrice = 0;
            $adminFee = $defaultAdminFee;
            if ($row->membership) {
                $grossPrice = $row->membership->price + ($row->membership->use_ppn ? $row->membership->ppn : 0) + $adminFee;
            }

            $formattedPrice = number_format($grossPrice, 0, ',', '.');
            $basePrice = $row->membership ? (float) $row->membership->price : 0;
            $ppnPrice = ($row->membership && $row->membership->use_ppn) ? (float) $row->membership->ppn : 0;
            $formattedAdminFee = number_format($adminFee, 0, ',', '.');
            $formattedBasePrice = number_format($basePrice, 0, ',', '.');
            $formattedPpnPrice = number_format($ppnPrice, 0, ',', '.');
            $adminOptionsJson = htmlspecialchars((string) json_encode($adminOptions, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
            $buttonLabel = $isRenewalBaru ? 'Perpanjangan Baru' : 'Perpanjang';
            $buttonClass = $isRenewalBaru ? 'btn-warning text-dark' : 'btn-success';
            $renewalMode = $isRenewalBaru ? 'renewal_baru' : 'renewal';

            $detailRoute = route('members.show', $row->id);
            $noticePdfRoute = route('members.download_renewal_notice_member', ['member' => $row->id, 'format' => 'pdf']);

            return '<div class="d-inline-flex flex-wrap gap-1">
                    <button type="button"
                        class="btn btn-sm btn-info btn-member-detail me-1"
                        data-route="' . $detailRoute . '">
                        <i class="fa fa-eye"></i> Detail
                    </button>
                    <button type="button"
                        class="btn btn-sm ' . $buttonClass . ' btn-renew-single"
                        data-id="' . $row->id . '"
                        data-name="' . $row->nama . '"
                        data-price-base="' . $formattedBasePrice . '"
                        data-price-ppn="' . $formattedPpnPrice . '"
                        data-price-admin="' . $formattedAdminFee . '"
                        data-price-base-raw="' . (int) round($basePrice) . '"
                        data-price-ppn-raw="' . (int) round($ppnPrice) . '"
                        data-price-admin-raw="' . (int) round($adminFee) . '"
                        data-admin-options="' . $adminOptionsJson . '"
                        data-renewal-mode="' . $renewalMode . '"
                        data-price="' . $formattedPrice . '">
                        <i class="fa fa-sync"></i> ' . $buttonLabel . '
                    </button>
                    <a href="' . $noticePdfRoute . '"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fa fa-download"></i> Download PDF
                    </a>
                </div>';
        })
        ->rawColumns(['action', 'renewal_status'])
        ->make(true);
}

public function downloadSingleRenewalNotice(Request $request, Member $member)
{
    $format = (string) $request->route('format', 'preview');
    if (!in_array($format, ['preview', 'pdf'], true)) {
        abort(404);
    }

    $setting = Setting::asObject();
    $suspendBeforeDays = max((int) ($setting->member_suspend_before_days ?? 7), 1);
    $suspendAfterDays = max((int) ($setting->member_suspend_after_days ?? 0), 0);

    $member->loadMissing('membership');
    $expiredAt = Carbon::parse($member->tgl_expired)->startOfDay();
    $limitDate = Carbon::now('Asia/Jakarta')->addDays($suspendBeforeDays)->startOfDay();

    if ((int) $member->parent_id !== 0 || (int) $member->membership_id === 0 || !$member->membership || $expiredAt->greaterThan($limitDate)) {
        return redirect()->route('members.bulk_renew')->with('error', 'Member tidak valid untuk reminder perpanjangan.');
    }

    $notices = $this->buildRenewalNoticeRows(collect([$member]), $suspendAfterDays);
    if (empty($notices)) {
        return redirect()->route('members.bulk_renew')->with('error', 'Data reminder member tidak ditemukan.');
    }

    $noticeContent = $this->renewalNoticeContent();
    $logoData = $this->resolveRenewalNoticeLogoData($setting);

    if ($format === 'pdf') {
        $filename = 'renewal_notice_' . Str::slug((string) ($member->nama ?? 'member')) . '_' . now('Asia/Jakarta')->format('Ymd_His') . '.pdf';

        return Pdf::loadView('member.renewal_notice_download', [
            'notices' => $notices,
            'logoData' => $logoData,
            'noticeContent' => $noticeContent,
            'autoPrint' => false,
        ])->setPaper('a4', 'portrait')->download($filename);
    }

    return response()->view('member.renewal_notice_download', [
        'notices' => $notices,
        'logoData' => $logoData,
        'noticeContent' => $noticeContent,
        'autoPrint' => true,
    ], 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
    ]);
}

public function downloadRenewalNotice(Request $request)
{
    $validated = $request->validate([
        'mode' => 'required|in:selected,all',
        'member_ids' => 'nullable|array',
        'member_ids.*' => 'integer|exists:members,id',
    ]);

    $setting = Setting::asObject();
    $suspendBeforeDays = max((int) ($setting->member_suspend_before_days ?? 7), 1);
    $suspendAfterDays = max((int) ($setting->member_suspend_after_days ?? 0), 0);

    $query = $this->renewableMembersQuery($suspendBeforeDays);

    if ($validated['mode'] === 'selected') {
        $selectedIds = collect($validated['member_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($selectedIds->isEmpty()) {
            return back()->with('error', 'Pilih minimal 1 member untuk download reminder.');
        }

        $query->whereIn('id', $selectedIds->all());
    }

    $members = $query->get()->filter(function ($member) {
        return $member->membership_id != 0 && $member->membership;
    })->values();

    if ($members->isEmpty()) {
        return back()->with('error', 'Data member renewal tidak ditemukan.');
    }

    $notices = $this->buildRenewalNoticeRows($members, $suspendAfterDays);
    $noticeContent = $this->renewalNoticeContent();

    $logoData = $this->resolveRenewalNoticeLogoData($setting);

    $html = view('member.renewal_notice_download', [
        'notices' => $notices,
        'logoData' => $logoData,
        'noticeContent' => $noticeContent,
        'autoPrint' => false,
    ])->render();

    $filename = 'renewal_notice_' . now('Asia/Jakarta')->format('Ymd_His') . '.html';

    return response($html, 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);
}

private function buildRenewalNoticeRows($members, int $suspendAfterDays): array
{
    $today = Carbon::now('Asia/Jakarta')->startOfDay();

    return collect($members)
        ->filter(function ($member) {
            return $member && $member->membership_id != 0 && $member->membership;
        })
        ->map(function ($member) use ($today, $suspendAfterDays) {
            $expiredAt = Carbon::parse($member->tgl_expired)->startOfDay();
            $daysAfterExpired = $today->greaterThan($expiredAt) ? $expiredAt->diffInDays($today) : 0;
            $isRenewalBaru = $daysAfterExpired > $suspendAfterDays;

            $basePrice = (float) ($member->membership->price ?? 0);
            $ppnAmount = (int) ($member->membership->use_ppn ?? 0) === 1 ? (float) ($member->membership->ppn ?? 0) : 0;
            $adminFee = 0;
            $totalPrice = $basePrice + $ppnAmount + $adminFee;

            return [
                'member_name' => (string) ($member->nama ?? '-'),
                'membership_name' => (string) ($member->membership->name ?? '-'),
                'expired_date' => $expiredAt->format('d/m/Y'),
                'due_date' => $expiredAt->format('d/m/Y'),
                'base_price' => $basePrice,
                'ppn_amount' => $ppnAmount,
                'admin_fee' => $adminFee,
                'total_price' => $totalPrice,
                'is_renewal_baru' => $isRenewalBaru,
            ];
        })
        ->values()
        ->all();
}

private function renewalNoticeContent(): array
{
    return [
        'club_name' => (string) Setting::valueOf('renewal_notice_club_name', 'MIU'),
        'bank_account' => (string) Setting::valueOf('renewal_notice_bank_account', 'TRANSFER BANK: BCA 0289011155 A/N PT KARTUNINDO PERKASA ABADI'),
        'admin_phone' => (string) Setting::valueOf('renewal_notice_admin_phone', '0821 2222 9358'),
        'body_template' => (string) Setting::valueOf(
            'renewal_notice_body_template',
            "Yth. Bapak/Ibu :member_name,\n\nKami informasikan bahwa masa aktif membership Anda akan berakhir pada :expired_date.\n\nAgar tetap dapat menikmati seluruh fasilitas, mohon melakukan perpanjangan dengan rincian:\n\nTipe Member: :membership_name\nBiaya: :total_price\nJatuh tempo: :due_date\n:note_block\nSilakan melakukan pembayaran sebelum jatuh tempo agar membership tetap aktif.\n\n:bank_account\n\nJika sudah melakukan pembayaran, harap informasi dan kirim bukti pembayaran ke nomor Admin.\nTerima kasih.\n\nAdmin\n:club_name\nNo.Hp: :admin_phone"
        ),
    ];
}

private function renewableMembersQuery(int $suspendBeforeDays)
{
    $limitDate = Carbon::now('Asia/Jakarta')->addDays($suspendBeforeDays)->format('Y-m-d');

    return Member::with('membership')
        ->where('tgl_expired', '<=', $limitDate)
        ->where('parent_id', 0)
        ->orderBy('tgl_expired', 'asc');
}

private function resolveRenewalNoticeLogoData(object $setting): ?string
{
    if (empty($setting->logo)) {
        return null;
    }

    $logoPath = storage_path('app/public/' . ltrim((string) $setting->logo, '/'));
    if (!is_file($logoPath)) {
        return null;
    }

    $ext = strtolower((string) pathinfo($logoPath, PATHINFO_EXTENSION));
    $mime = in_array($ext, ['jpg', 'jpeg'], true) ? 'image/jpeg' : ($ext === 'gif' ? 'image/gif' : 'image/png');
    $data = base64_encode((string) file_get_contents($logoPath));

    return 'data:' . $mime . ';base64,' . $data;
}

public function processBulkRenew(Request $request)
{
    $validatedPayment = $request->validate([
        'metode' => ['required', Rule::in(PaymentMethod::coreValidationValues())],
        'renewal_mode' => 'nullable|in:renewal,renewal_baru',
        'admin_fee_master_id' => 'nullable|integer',
    ]);

    $memberIdsInput = $request->input('member_ids');
    $memberId = is_array($memberIdsInput)
        ? (int) ($memberIdsInput[0] ?? 0)
        : (int) $memberIdsInput;

    if ($memberId <= 0) {
        return back()->with('error', 'Tidak ada member yang dipilih untuk diperpanjang.');
    }

    DB::beginTransaction();
    try {
        $successCount = 0;
        $firstParentMember = null;

        $member = Member::with('membership')->where('id', $memberId)->first();
        if (!$member) {
            DB::rollBack();
            return back()->with('error', 'Member tidak ditemukan.');
        }

        $today = Carbon::now('Asia/Jakarta')->startOfDay();
        $expiredAt = Carbon::parse($member->tgl_expired)->startOfDay();
        $daysAfterExpired = $today->greaterThan($expiredAt) ? $expiredAt->diffInDays($today) : 0;

        $suspendDays = max((int) Setting::valueOf('member_suspend_after_days', 0), 0);

        $isRenewalBaru = $daysAfterExpired > $suspendDays;
        if (($validatedPayment['renewal_mode'] ?? 'renewal') === 'renewal_baru') {
            $isRenewalBaru = true;
        }

        if ($member->membership_id == 0 || !$member->membership || $member->parent_id != 0) {
            DB::rollBack();
            return back()->with('error', 'Member tidak valid untuk perpanjangan (harus parent dan punya membership).');
        }

        $totalPpnAmount = 0;
        if ($member->membership->use_ppn) {
            $totalPpnAmount = (float) $member->membership->ppn;
        }

        if ($member->membership_id != 0 && $member->membership && $member->parent_id == 0) {

                if ($firstParentMember === null) {
                    $firstParentMember = $member;
                }

                $duration = $member->membership->duration_days;
                $membershipPrice = $member->membership->price ?? 0;

                // === LOGIKA PPN PER MEMBER ===
                $netPrice = $membershipPrice; // Harga bersih awal
                $ppnItemAmount = 0;

                if ($member->membership->use_ppn) {
                    $ppnItemAmount = (float) $member->membership->ppn;
                }

                // Tambahkan ke total akumulasi transaksi

                $startDate = Carbon::now('Asia/Jakarta');

                $tgl_lama = Carbon::parse($member->tgl_expired);
                // Tentukan tanggal mulai perpanjangan: Hari berikutnya dari tgl_expired lama JIKA tgl_expired > hari ini,
                // jika tidak, mulai dari hari ini.
                $tgl_baru_start = $tgl_lama->greaterThan($startDate) ? $tgl_lama->copy()->addDay() : $startDate->copy();
                $tgl_expired_baru = $tgl_baru_start->copy()->addDays($duration)->format('Y-m-d');

                // Update member
                $member->update(['tgl_expired' => $tgl_expired_baru, 'is_active' => true, 'access_used' => 0]); // Reset access

                // Catat History untuk Parent
                HistoryMembership::create([
                    'member_id' => $member->id,
                    'membership_id' => $member->membership_id,
                    'start_date' => $tgl_baru_start->format('Y-m-d'),
                    'end_date' => $tgl_expired_baru,
                    'status' => 'active',
                    'price' => $netPrice,
                    'ppn' => $ppnItemAmount
                ]);

                $successCount++;

                $childMembers = Member::where('parent_id', $member->id)->get();
                foreach ($childMembers as $child) {
                    $child->update(['tgl_expired' => $tgl_expired_baru, 'is_active' => true, 'access_used' => 0]);
                    $child->update(['access_used' => 0]);

                    HistoryMembership::create([
                        'member_id' => $child->id,
                        'membership_id' => $member->membership_id,
                        'start_date' => $tgl_baru_start->format('Y-m-d'),
                        'end_date' => $tgl_expired_baru,
                        'status' => 'active',
                        'price' => 0,
                        'ppn' => 0
                    ]);
                    $successCount++;
                }
        }

        if ($firstParentMember === null) {
            DB::rollBack();
            return back()->with('error', 'Semua member yang dipilih tidak valid untuk perpanjangan (bukan Parent atau tanpa Membership).');
        }

        $resolvedAdminFee = $this->resolveSelectedAdminFee($validatedPayment['admin_fee_master_id'] ?? null);
        $adminFee = $resolvedAdminFee['admin_fee'];
        $baseMembershipPrice = (float) ($member->membership->price ?? 0);

        $now = Carbon::now('Asia/Jakarta');
        $transactionType = $isRenewalBaru ? 'registration' : 'renewal';
        $notrx = Transaction::nextNoTrxByType($transactionType, $now);
        $ticketCode = Transaction::buildTicketCodeByType($transactionType, $now, $notrx);

        $tipeTransaksi = $successCount > 1 ? 'group' : 'individual';

        $metode = PaymentMethod::normalize($validatedPayment['metode']);
        $isCardMethod = in_array($metode, ['debit', 'kredit'], true);
        if ($isCardMethod) {
            $request->validate([
                'nama_kartu' => 'required|string|max:100',
                'no_kartu' => 'required|string|max:100',
                'bank' => 'required|string|max:100',
            ]);
        }

        $transaction = Transaction::create([
            'user_id' => auth()->user()->id,
            'no_trx' => $notrx,
            'ticket_code' => $ticketCode,
            'transaction_type' => $transactionType,
            'amount' => $successCount,
            'discount' => 0,
            'ppn' => $totalPpnAmount,
            'admin_fee' => $adminFee,
            'bayar' => $baseMembershipPrice,
            'status' => 'open',
            'is_active' => 1,
            'tipe' => $tipeTransaksi,
            'metode' => $metode,
            'nama_kartu' => $isCardMethod ? $request->nama_kartu : null,
            'no_kartu' => $isCardMethod ? $request->no_kartu : null,
            'bank' => $isCardMethod ? $request->bank : null,
            'ticket_id' => $member->membership_id,
            'member_id' => $firstParentMember ? $firstParentMember->id : null,
            'member_info' => $firstParentMember ? ($firstParentMember->nama . ' - ' . $firstParentMember->no_hp) : null
        ]);

        if ($firstParentMember) {
            $this->enqueueWhatsappNotificationFromTransaction($transaction, $firstParentMember);
        }

        $invoiceUrl = $transaction ? route('transactions.invoice', $transaction->id) : null;


        DB::commit();
        $renewModeText = $isRenewalBaru ? 'Perpanjangan Baru (dengan biaya admin)' : 'Perpanjangan';
        return redirect()->route('members.index')
            ->with('success', "Berhasil memproses $renewModeText untuk $successCount entri member.")
            ->with('invoice_url', $invoiceUrl);

    } catch (\Throwable $th) {
        DB::rollBack();
        return back()->with('error', 'Gagal memproses perpanjangan massal: ' . $th->getMessage());
    }
}

    private function adminFeeOptions(): array
    {
        if (!Schema::hasTable('membership_admin_fees')) {
            return [];
        }

        return MembershipAdminFee::query()
            ->orderBy('admin_type')
            ->get(['id', 'admin_type', 'admin_fee'])
            ->map(function (MembershipAdminFee $row) {
                return [
                    'id' => (int) $row->id,
                    'admin_type' => (string) $row->admin_type,
                    'admin_fee' => (int) $row->admin_fee,
                ];
            })
            ->values()
            ->all();
    }

    private function resolveSelectedAdminFee(mixed $selectedAdminFeeMasterId): array
    {
        $selectedId = max((int) $selectedAdminFeeMasterId, 0);
        if ($selectedId === 0 || !Schema::hasTable('membership_admin_fees')) {
            return [
                'admin_fee' => 0,
                'admin_fee_master_id' => null,
                'admin_type' => null,
            ];
        }

        $adminFeeMaster = MembershipAdminFee::query()
            ->whereKey($selectedId)
            ->first();

        if (!$adminFeeMaster) {
            throw ValidationException::withMessages([
                'admin_fee_master_id' => 'Jenis admin tidak valid.',
            ]);
        }

        return [
            'admin_fee' => max((int) ($adminFeeMaster->admin_fee ?? 0), 0),
            'admin_fee_master_id' => (int) $adminFeeMaster->id,
            'admin_type' => (string) ($adminFeeMaster->admin_type ?? ''),
        ];
    }

    private function enqueueWhatsappNotificationFromTransaction(Transaction $transaction, Member $member): void
    {
        $setting = Setting::asObject();
        if (!$setting || !(bool) $setting->whatsapp_enabled) {
            return;
        }

        $phone = $this->normalizeWhatsappPhone($member->no_hp ?? null);
        if (!$phone) {
            return;
        }

        $type = strtolower((string) $transaction->transaction_type);
        if (!in_array($type, ['registration', 'renewal'], true)) {
            return;
        }

        $alreadyExists = WhatsappNotificationLog::query()
            ->where('type', $type)
            ->where('transaction_id', $transaction->id)
            ->where('recipient_phone', $phone)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        $message = $this->buildMembershipWhatsappMessage($type, $member, $transaction);

        WhatsappNotificationLog::create([
            'type' => $type,
            'member_id' => $member->id,
            'transaction_id' => $transaction->id,
            'recipient_phone' => $phone,
            'message' => $message,
            'status' => 'pending',
            'retry_count' => 0,
        ]);
    }

    private function buildMembershipWhatsappMessage(string $type, Member $member, Transaction $transaction): string
    {
        $memberName = trim((string) $member->nama) !== '' ? $member->nama : 'Member';
        $invoiceCode = $transaction->ticket_code ?? ('TRX-' . $transaction->id);
        $date = optional($transaction->created_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i') ?? now('Asia/Jakarta')->format('d/m/Y H:i');
        $adminFee = max(0, (float) ($transaction->admin_fee ?? 0));

        if ($adminFee > 0) {
            return "Halo {$memberName}, perpanjangan baru membership Anda berhasil diproses pada {$date}. Kode invoice: {$invoiceCode}. Terima kasih.";
        }

        if ($type === 'renewal') {
            return "Halo {$memberName}, perpanjangan membership Anda berhasil diproses pada {$date}. Kode invoice: {$invoiceCode}. Terima kasih.";
        }

        return "Halo {$memberName}, registrasi membership Anda berhasil pada {$date}. Kode invoice: {$invoiceCode}. Selamat bergabung.";
    }

    private function normalizeWhatsappPhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $phone);
        if (!$normalized) {
            return null;
        }

        if (strpos($normalized, '62') === 0) {
            return $normalized;
        }

        if (strpos($normalized, '0') === 0) {
            return '62' . substr($normalized, 1);
        }

        return $normalized;
    }

}

