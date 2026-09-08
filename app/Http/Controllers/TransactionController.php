<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Sewa;
use App\Models\Ticket;
use App\Models\Membership;
use App\Models\Member;
use App\Models\Penyewaan;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Exports\ReportExport;
use App\Exports\TransactionDailyReceiptExport;
use App\Models\DetailTransaction;
use App\Support\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Requests\Transaction\CreateTransactionRequest;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:transaction-access');
    }

    public function index()
    {
        $title = 'Data Transaction';
        $breadcrumbs = ['Master', 'Data Transaction'];
        $tickets = Ticket::query()->orderBy('name')->get();
        $rentals = Sewa::query()->orderBy('name')->get();
        $memberships = Membership::query()->orderBy('name')->get();
        $setting = Setting::asObject();
        $reminderDays = (int) ($setting->member_suspend_before_days ?? 7);
        $limitDate = Carbon::now('Asia/Jakarta')->addDays($reminderDays)->toDateString();
        $renewalCount = Member::where('tgl_expired', '<=', $limitDate)
            ->where('is_active', 1)
            ->where('parent_id', 0)
            ->count();

        $detailFilterOptions = [
            'ticket' => $tickets->map(fn ($ticket) => [
                'value' => 'ticket:' . (int) $ticket->id,
                'text' => (string) $ticket->name,
            ])->values(),
            'rental' => $rentals->map(fn ($rental) => [
                'value' => 'rental:' . (int) $rental->id,
                'text' => (string) $rental->name,
            ])->values(),
            'membership' => $memberships->map(fn ($membership) => [
                'value' => 'membership:' . (int) $membership->id,
                'text' => (string) $membership->name,
            ])->values(),
        ];

        return view('transaction.index', compact('title', 'breadcrumbs', 'tickets', 'renewalCount', 'detailFilterOptions'));
    }

    public function get(Request $request)
    {
        if ($request->ajax()) {
            [$startDate, $endDate] = $this->resolveDateRange($request);

            $transactionType = $request->transaction_type;
            $detailMasterValue = $request->input('detail_master_id');

            // --- Query Building Logic ---
            if (auth()->user()->roles()->first()->id == 1) {
                $data = Transaction::with(['detail.ticket', 'user', 'member'])
                    ->where('is_active', 1)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->orderBy('created_at', 'DESC')
                    ->orderBy('id', 'DESC');
            } else {
                $data = Transaction::with(['detail.ticket', 'user', 'member'])
                    ->where(['is_active' => 1, 'user_id' => auth()->user()->id])
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->orderBy('created_at', 'DESC')
                    ->orderBy('id', 'DESC');
            }

            $this->applyTransactionTypeFilter($data, $transactionType);

            $this->applyDetailMasterFilter($data, $detailMasterValue);

            return DataTables::eloquent($data)
                ->addIndexColumn()
                ->addColumn('tanggal', function ($row) {
                    return optional($row->created_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s');
                })

                ->addColumn('transaction_type_badge', function ($row) {
                    return $this->resolveTransactionTypeBadge($row->transaction_type);
                })
                ->addColumn('detail_description', function ($row) {
                    return $this->resolveTransactionDetail($row);
                })
                ->addColumn('qty', function ($row) {
                    return $this->resolveTransactionQty($row);
                })
                ->addColumn('member_info', function ($row) {
                    if (!in_array($row->transaction_type, ['renewal', 'registration'])) {
                        return '-';
                    }

                    if ($row->member_id && $row->member) {
                        return $row->member->nama . ' - ' . $row->member->no_hp;
                    }

                    if (!empty($row->member_info)) {
                        return $row->member_info . ' (deleted)';
                    }

                    return '-';
                })
                ->editColumn('action', function ($row) {
                    $buttons = [];

                    if ($row->transaction_type === 'ticket') {
                        $previewUrl = route('transactions.print', $row->id) . '?auto_print=0&auto_redirect=0';
                        $printUrl = $previewUrl;
                        $pdfUrl = route('transactions.ticket.pdf', $row->id);

                        $buttons[] = '<button type="button" class="btn btn-sm btn-primary btn-ticket-preview"'
                            . ' data-preview-url="' . e($previewUrl) . '"'
                            . ' data-print-url="' . e($printUrl) . '"'
                            . ' data-pdf-url="' . e($pdfUrl) . '"'
                            . ' title="Preview Ticket">'
                            . '<i class="fas fa-print"></i>'
                            . '</button>';
                    } elseif ($row->transaction_type === 'rental') {
                        $rentalId = (int) ($row->ticket_id ?? 0);
                        $previewUrl = $rentalId > 0 ? route('penyewaan.print.pdf', $rentalId) . '?inline=1' : '#';
                        $printUrl = $previewUrl;
                        $pdfUrl = $rentalId > 0 ? route('penyewaan.print.pdf', $rentalId) : '#';

                        $buttons[] = '<button type="button" class="btn btn-sm btn-primary btn-ticket-preview"'
                            . ' data-preview-url="' . e($previewUrl) . '"'
                            . ' data-print-url="' . e($printUrl) . '"'
                            . ' data-pdf-url="' . e($pdfUrl) . '"'
                            . ' title="Preview Transaksi Lainnya">'
                            . '<i class="fas fa-print"></i>'
                            . '</button>';
                    } elseif (!in_array($row->transaction_type, ['registration', 'renewal'])) {
                        $buttons[] = '<a href="' . route("transactions.print", $row->id) . '" class="btn btn-sm btn-primary"><i class="fas fa-print"></i></a>';
                    }

                    $totalQty = (int) $row->detail->sum('qty');
                    $totalScanned = (int) $row->detail->sum('scanned');

                    if ($row->transaction_type == 'ticket' && $totalScanned < $totalQty) {
                        $routeFullScan = route('transactions.set_full_scan', $row->id);
                        $buttons[] = '<button type="button"
                                                data-route="' . $routeFullScan . '"
                                                data-ticket-code="' . $row->ticket_code . '"
                                                class="btn btn-sm btn-warning btn-full-scan"
                                                title="Set Full Scan">
                                                <i class="fas fa-check-double"></i>
                                            </button>';
                    }

                    if (in_array($row->transaction_type, ['registration', 'renewal'])) {
                        $previewUrl = route('transactions.invoice.pdf_file', $row->id) . '?inline=1';
                        $printUrl = route('transactions.invoice.pdf_file', $row->id) . '?inline=1';
                        $pdfUrl = route('transactions.invoice.pdf_file', $row->id);

                        $buttons[] = '<button type="button" class="btn btn-sm btn-secondary btn-ticket-preview"'
                            . ' data-preview-url="' . e($previewUrl) . '"'
                            . ' data-print-url="' . e($printUrl) . '"'
                            . ' data-pdf-url="' . e($pdfUrl) . '"'
                            . ' title="Preview Invoice">'
                            . '<i class="fas fa-file-invoice"></i>'
                            . '</button>';
                    }

                    if (auth()->user()->can('transaction-delete')) {
                        $buttons[] = '<button type="button" data-route="' . route('transactions.destroy', $row->id) . '" class="delete btn btn-danger btn-delete btn-sm"><i class="fas fa-trash"></i></button>';
                    }

                    return '<div class="d-inline-flex flex-nowrap align-items-center gap-1">' . implode('', $buttons) . '</div>';
                })
                ->editColumn('disc', function ($row) {
                    return $row->discount . '%';
                })
                ->editColumn('discount', function ($row) {
                    return 'Rp. ' . number_format($row->detail->sum('total') * $row->discount / 100, 0, ',', '.');
                })
                ->editColumn('scanned', function ($row) {
                    $hideForTypes = ['rental', 'renewal', 'registration'];

                    if (in_array($row->transaction_type, $hideForTypes)) {
                        return '-';
                    }

                    $totalQty = (int) $row->detail->sum('qty');
                    $totalScanned = (int) $row->detail->sum('scanned');
                    $scanLimit = (int) Setting::valueOf('ticket_scan_limit', 0);
                    $totalAllowed = $scanLimit > 0 ? ($totalQty * $scanLimit) : $totalQty;

                    $label = '<span class="fw-bold fs-14px">' . $totalScanned . '</span>' . ' / ' . $totalAllowed;

                    if ($row->transaction_type !== 'ticket') {
                        return $label;
                    }

                    $detailBtn = '<button type="button" class="btn btn-xs btn-outline-info ms-2 btn-scan-detail" data-transaction-id="' . $row->id . '" data-ticket-code="' . e((string) $row->ticket_code) . '">Detail</button>';
                    return $label . $detailBtn;
                })
->addColumn('user_name', function ($row) {
        return $row->user ? $row->user->name : 'N/A';
    })
                ->editColumn('ppn', function ($row): string {
                    return 'Rp. ' . number_format($row->ppn, 0, ',', '.');
                })

                ->editColumn('sisa', function ($row) {
                    $totalQty = (int) $row->detail->sum('qty');
                    $totalScanned = (int) $row->detail->sum('scanned');
                    $scanLimit = (int) Setting::valueOf('ticket_scan_limit', 0);
                    $totalAllowed = $scanLimit > 0 ? ($totalQty * $scanLimit) : $totalQty;

                    return max($totalAllowed - $totalScanned, 0);
                })

                ->editColumn('bayar', function ($row) {
                    return "Rp. " . number_format($row->bayar, 0, ',', '.');
                })

                ->editColumn('harga_ticket', function ($row) {
                    $disc = $row->bayar * $row->discount / 100;
                    return 'Rp. ' . number_format($row->bayar - $disc + $row->ppn, 0, ',', '.');
                })

                // Perubahan ada di sini: Pengecekan transaction_type sebelum menampilkan status
                ->addColumn('status_ticket', function ($row) {
                    if ($row->transaction_type != 'ticket') {
                        return '-';
                    }

                    return $row->status == 'open'
                        ? '<span class="badge bg-success text-capitalize">' . ucfirst($row->status) . '</span>'
                        : '<span class="badge bg-danger">' . ucfirst($row->status) . '</span>';
                })

                ->rawColumns(['action', 'status_ticket', 'scanned', 'transaction_type_badge'])
                ->make(true);
        }
    }

    public function exportDaily(Request $request)
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $transactionType = $request->input('transaction_type');
        $detailMasterValue = $request->input('detail_master_id');

        $query = Transaction::with(['detail.ticket', 'user', 'member'])
            ->where('is_active', 1)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if (auth()->user()->roles()->first()->id != 1) {
            $query->where('user_id', auth()->id());
        }

        $this->applyTransactionTypeFilter($query, $transactionType);

        $this->applyDetailMasterFilter($query, $detailMasterValue);

        $transactions = $query->orderBy('created_at')->orderBy('id')->get();
        $exportRows = $this->buildDataTransactionExportRows($transactions);
        $setting = Setting::asObject();
        $reportTitle = strtoupper(trim((string) ($setting->name ?? config('app.name', 'TICKETING'))));

        $fileName = 'Laporan_Penerimaan_Harian_' . now('Asia/Jakarta')->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new TransactionDailyReceiptExport($startDate, $endDate, $exportRows, $reportTitle),
            $fileName
        );
    }

    public function scanDetails(Transaction $transaction)
    {
        if ($transaction->transaction_type !== 'ticket') {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaksi bukan ticket.',
                'data' => [],
            ], 422);
        }

        $scanLimit = (int) Setting::valueOf('ticket_scan_limit', 0);
        $details = $transaction->detail()->with('ticket')->get();

        $rows = collect();
        $allowedPerPiece = $scanLimit > 0 ? $scanLimit : 1;

        foreach ($details as $detail) {
            $qty = max((int) ($detail->qty ?? 0), 0);
            $ticketName = (string) ($detail->ticket->name ?? '-');
            $ticketCodeBase = trim((string) ($detail->ticket_code ?? ''));
            $scannedRemaining = max((int) ($detail->scanned ?? 0), 0);

            if ($qty <= 1) {
                $pieceScanned = min($scannedRemaining, $allowedPerPiece);
                $rows->push([
                    'ticket_code' => $ticketCodeBase !== '' ? $ticketCodeBase : (string) $detail->id,
                    'ticket_name' => $ticketName,
                    'qty' => 1,
                    'allowed' => $allowedPerPiece,
                    'scanned' => $pieceScanned,
                    'remaining' => max($allowedPerPiece - $pieceScanned, 0),
                ]);
                continue;
            }

            for ($piece = 1; $piece <= $qty; $piece++) {
                $pieceScanned = min($scannedRemaining, $allowedPerPiece);
                $scannedRemaining -= $pieceScanned;

                $displayCode = $ticketCodeBase !== ''
                    ? ($ticketCodeBase . '-' . $piece)
                    : ($detail->id . '-' . $piece);

                $rows->push([
                    'ticket_code' => (string) $displayCode,
                    'ticket_name' => $ticketName,
                    'qty' => 1,
                    'allowed' => $allowedPerPiece,
                    'scanned' => $pieceScanned,
                    'remaining' => max($allowedPerPiece - $pieceScanned, 0),
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'ticket_code' => (string) ($transaction->ticket_code ?? ''),
            'data' => $rows,
        ]);
    }

    private function buildDataTransactionExportRows($transactions): array
    {
        $rows = [];

        foreach ($transactions as $trx) {
            $tanggal = optional($trx->created_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s');
            $kasir = $trx->user->name ?? '-';
            $caraBayar = strtoupper((string) ($trx->metode ?? '-'));

            if ($trx->transaction_type === 'ticket' && $trx->detail->isNotEmpty()) {
                foreach ($trx->detail as $detail) {
                    $qty = max((int) ($detail->qty ?? 1), 1);
                    $ppn = (float) ($detail->ppn ?? 0);
                    $harga = (float) ($detail->total ?? 0) + $ppn;

                    $rows[] = $this->makeExportRow(
                        (string) ($trx->ticket_code ?? ('TRX/' . $trx->id)),
                        $kasir,
                        $tanggal,
                        $harga,
                        $qty,
                        $caraBayar,
                        $harga
                    );
                }
                continue;
            }

            $qty = max((int) ($trx->amount ?? 1), 1);
            $ppn = (float) ($trx->ppn ?? 0);
            $harga = (float) ($trx->bayar ?? 0) - (float) ($trx->kembali ?? 0) + $ppn;

            $rows[] = $this->makeExportRow(
                (string) ($trx->ticket_code ?? ('TRX/' . $trx->id)),
                $kasir,
                $tanggal,
                $harga,
                $qty,
                $caraBayar,
                $harga
            );
        }

        return $rows;
    }

    private function makeExportRow(
        string $noTransaksi,
        string $namaKasir,
        string $tanggal,
        float $harga,
        int $qty,
        string $caraBayar,
        float $totalBayar
    ): array {
        $tunai = 0.0;
        $debit = 0.0;
        $qr = 0.0;
        $creditCard = 0.0;
        $transfer = 0.0;
        $pembayaranLainnya = 0.0;

        $metode = PaymentMethod::normalize($caraBayar);
        if ($metode === 'cash') {
            $tunai = $totalBayar;
        } elseif ($metode === 'debit') {
            $debit = $totalBayar;
        } elseif ($metode === 'qris') {
            $qr = $totalBayar;
        } elseif ($metode === 'transfer') {
            $transfer = $totalBayar;
        } elseif ($metode === 'kredit') {
            $creditCard = $totalBayar;
        } else {
            $pembayaranLainnya = $totalBayar;
        }

        return [
            'no_transaksi' => $noTransaksi,
            'nama_kasir' => $namaKasir,
            'tanggal' => $tanggal,
            'harga' => $harga,
            'qty' => $qty,
            'cara_bayar' => $caraBayar,
            'tunai' => $tunai,
            'debit' => $debit,
            'qr' => $qr,
            'credit_card' => $creditCard,
            'transfer' => $transfer,
            'pembayaran_lainnya' => $pembayaranLainnya,
            'total_bayar' => $totalBayar,
        ];
    }

    private function resolveDateRange(Request $request): array
    {
        $startDate = Carbon::now('Asia/Jakarta')->startOfDay();
        $endDate = Carbon::now('Asia/Jakarta')->endOfDay();
        $daterange = trim((string) $request->input('daterange', ''));

        if ($daterange !== '') {
            $parts = explode(' - ', $daterange);
            if (count($parts) === 2) {
                try {
                    $startDate = Carbon::createFromFormat('m/d/Y', trim($parts[0]), 'Asia/Jakarta')->startOfDay();
                    $endDate = Carbon::createFromFormat('m/d/Y', trim($parts[1]), 'Asia/Jakarta')->endOfDay();
                } catch (\Throwable $e) {
                    $startDate = Carbon::now('Asia/Jakarta')->startOfDay();
                    $endDate = Carbon::now('Asia/Jakarta')->endOfDay();
                }
            }
        } elseif ($request->filled('tanggal')) {
            try {
                $legacyDate = Carbon::parse($request->input('tanggal'), 'Asia/Jakarta');
                $startDate = $legacyDate->copy()->startOfDay();
                $endDate = $legacyDate->copy()->endOfDay();
            } catch (\Throwable $e) {
                $startDate = Carbon::now('Asia/Jakarta')->startOfDay();
                $endDate = Carbon::now('Asia/Jakarta')->endOfDay();
            }
        }

        return [$startDate, $endDate];
    }

    private function applyTransactionTypeFilter(Builder $query, ?string $transactionType): void
    {
        $transactionType = strtolower(trim((string) $transactionType));
        if ($transactionType === '') {
            return;
        }

        if ($transactionType === 'membership') {
            $query->whereIn('transaction_type', ['registration', 'renewal']);
            return;
        }

        $query->where('transaction_type', $transactionType);
    }

    private function applyDetailMasterFilter(Builder $query, $detailMasterValue): void
    {
        $selection = strtolower(trim((string) $detailMasterValue));
        if ($selection === '') {
            return;
        }

        if (!preg_match('/^(ticket|rental|membership):(\d+)$/', $selection, $matches)) {
            return;
        }

        $detailType = $matches[1];
        $masterId = (int) $matches[2];
        if ($masterId <= 0) {
            return;
        }

        if ($detailType === 'ticket') {
            $query->where('transaction_type', 'ticket')
                ->whereHas('detail', function (Builder $detailQuery) use ($masterId) {
                    $detailQuery->where('ticket_id', $masterId);
                });
            return;
        }

        if ($detailType === 'rental') {
            $query->where('transaction_type', 'rental')
                ->whereIn('ticket_id', Penyewaan::query()
                    ->where('sewa_id', $masterId)
                    ->select('id'));
            return;
        }

        if ($detailType === 'membership') {
            $query->whereIn('transaction_type', ['registration', 'renewal'])
                ->where('ticket_id', $masterId);
        }
    }

    private function resolveTransactionTypeBadge(?string $transactionType): string
    {
        $type = strtolower((string) $transactionType);

        return match ($type) {
            'ticket' => '<span class="badge bg-primary">Ticket</span>',
            'rental' => '<span class="badge bg-warning text-dark">Penyewaan</span>',
            'registration' => '<span class="badge bg-success">Membership</span>',
            'renewal' => '<span class="badge bg-info text-dark">Renewal</span>',
            default => '<span class="badge bg-secondary">' . e(ucfirst($type ?: '-')) . '</span>',
        };
    }

    private function resolveTransactionDetail(Transaction $transaction): string
    {
        if ($transaction->transaction_type === 'ticket') {
            $details = $transaction->relationLoaded('detail')
                ? $transaction->detail
                : $transaction->detail()->with('ticket')->get();

            $lines = $details
                ->map(function ($detail) {
                    $name = trim((string) ($detail->ticket->name ?? 'Ticket'));
                    $qty = max((int) ($detail->qty ?? 1), 1);

                    return $qty > 1 ? ($name . ' x' . $qty) : $name;
                })
                ->filter()
                ->values();

            return $lines->isNotEmpty() ? $lines->implode(', ') : '-';
        }

        if (in_array($transaction->transaction_type, ['registration', 'renewal'], true)) {
            static $membershipCache = [];
            $membershipId = (int) ($transaction->ticket_id ?? 0);

            if ($membershipId <= 0) {
                return '-';
            }

            if (!array_key_exists($membershipId, $membershipCache)) {
                $membershipCache[$membershipId] = (string) (Membership::query()->whereKey($membershipId)->value('name') ?? '-');
            }

            $name = $membershipCache[$membershipId] ?: '-';
            $qty = max((int) ($transaction->amount ?? 1), 1);

            return $qty > 1 ? ($name . ' x' . $qty) : $name;
        }

        if ($transaction->transaction_type === 'rental') {
            static $rentalCache = [];
            $rentalId = (int) ($transaction->ticket_id ?? 0);

            if ($rentalId <= 0) {
                return '-';
            }

            if (!array_key_exists($rentalId, $rentalCache)) {
                $rentalCache[$rentalId] = (string) (Penyewaan::query()
                    ->with('sewa:id,name')
                    ->whereKey($rentalId)
                    ->first()?->sewa?->name ?? '-');
            }

            $name = $rentalCache[$rentalId] ?: '-';
            $qty = max((int) ($transaction->amount ?? 1), 1);

            return $qty > 1 ? ($name . ' x' . $qty) : $name;
        }

        return '-';
    }

    private function resolveTransactionQty(Transaction $transaction): int
    {
        if ($transaction->transaction_type === 'ticket') {
            $details = $transaction->relationLoaded('detail')
                ? $transaction->detail
                : $transaction->detail()->get();

            $qty = (int) $details->sum('qty');
            if ($qty > 0) {
                return $qty;
            }
        }

        return max((int) ($transaction->amount ?? 1), 1);
    }

    private function buildDailyReceiptGroups($transactions): array
    {
        $membershipIds = $transactions
            ->whereIn('transaction_type', ['registration', 'renewal'])
            ->pluck('ticket_id')
            ->filter()
            ->unique()
            ->values();

        $rentalIds = $transactions
            ->where('transaction_type', 'rental')
            ->pluck('ticket_id')
            ->filter()
            ->unique()
            ->values();

        $memberships = Membership::query()
            ->whereIn('id', $membershipIds)
            ->get()
            ->keyBy('id');

        $penyewaans = Penyewaan::query()
            ->with('sewa')
            ->whereIn('id', $rentalIds)
            ->get()
            ->keyBy('id');

        $groups = [];

        $pushRow = function (array $row) use (&$groups) {
            $groupCode = $row['group_code'];
            if (!isset($groups[$groupCode])) {
                $groups[$groupCode] = [
                    'group_code' => $groupCode,
                    'group_name' => $row['group_name'],
                    'rows' => [],
                    'subtotal_qty' => 0,
                    'subtotal_tunai' => 0,
                    'subtotal_non_tunai' => 0,
                    'subtotal_total' => 0,
                ];
            }

            $groups[$groupCode]['rows'][] = $row;
            $groups[$groupCode]['subtotal_qty'] += $row['qty'];
            $groups[$groupCode]['subtotal_tunai'] += $row['tunai'];
            $groups[$groupCode]['subtotal_non_tunai'] += $row['non_tunai'];
            $groups[$groupCode]['subtotal_total'] += $row['total_bayar'];
        };

        foreach ($transactions as $trx) {
            $shift = (int) $trx->created_at->timezone('Asia/Jakarta')->format('H') < 15 ? 1 : 2;
            $jam = $trx->created_at->timezone('Asia/Jakarta')->format('H:i:s');
            $fc = strtoupper((string) ($trx->user->name ?? '-'));
            $metode = strtolower((string) ($trx->metode ?? ''));
            $memberFlag = in_array($trx->transaction_type, ['registration', 'renewal']) ? 'Y' : 'T';
            $voucherFlag = 'T';

            if ($trx->transaction_type === 'ticket') {
                foreach ($trx->detail as $detail) {
                    $qty = max((int) ($detail->qty ?? 1), 1);
                    $total = (float) ($detail->total ?? 0) + (float) ($detail->ppn ?? 0);
                    $tarif = $qty > 0 ? $total / $qty : $total;
                    $productCode = 'TK' . str_pad((string) ($detail->ticket_id ?? 0), 2, '0', STR_PAD_LEFT);
                    $productName = strtoupper((string) ($detail->ticket->name ?? 'TIKET'));

                    $pushRow([
                        'no_bukti' => $trx->ticket_code,
                        'shift' => $shift,
                        'jam' => $jam,
                        'fc' => $fc,
                        'member' => $memberFlag,
                        'voucher' => $voucherFlag,
                        'group_code' => $productCode,
                        'group_name' => $productName,
                        'qty' => $qty,
                        'tarif' => (float) $tarif,
                        'tunai' => $metode === 'cash' ? (float) $total : 0.0,
                        'non_tunai' => $metode === 'cash' ? 0.0 : (float) $total,
                        'total_bayar' => (float) $total,
                    ]);
                }
                continue;
            }

            if (in_array($trx->transaction_type, ['registration', 'renewal'])) {
                $membership = $memberships->get($trx->ticket_id);
                $productCode = strtoupper((string) ($membership->code ?? ('M' . str_pad((string) ($trx->ticket_id ?? 0), 2, '0', STR_PAD_LEFT))));
                $productName = strtoupper((string) ($membership->name ?? ucfirst($trx->transaction_type)));
            } elseif ($trx->transaction_type === 'rental') {
                $penyewaan = $penyewaans->get($trx->ticket_id);
                $sewa = $penyewaan?->sewa;
                $sewaId = $sewa->id ?? 0;
                $productCode = 'SR' . str_pad((string) $sewaId, 2, '0', STR_PAD_LEFT);
                $productName = strtoupper((string) ($sewa->name ?? 'RENTAL'));
            } else {
                $productCode = strtoupper((string) $trx->transaction_type);
                $productName = strtoupper((string) $trx->transaction_type);
            }

            $qty = max((int) ($trx->amount ?? 1), 1);
            $total = (float) ($trx->bayar ?? 0) - (float) ($trx->kembali ?? 0) + (float) ($trx->ppn ?? 0);
            $tarif = $qty > 0 ? $total / $qty : $total;

            $pushRow([
                'no_bukti' => $trx->ticket_code,
                'shift' => $shift,
                'jam' => $jam,
                'fc' => $fc,
                'member' => $memberFlag,
                'voucher' => $voucherFlag,
                'group_code' => $productCode,
                'group_name' => $productName,
                'qty' => $qty,
                'tarif' => (float) $tarif,
                'tunai' => $metode === 'cash' ? (float) $total : 0.0,
                'non_tunai' => $metode === 'cash' ? 0.0 : (float) $total,
                'total_bayar' => (float) $total,
            ]);
        }

        ksort($groups);

        return $groups;
    }

    public function create()
    {
        $title = 'Input Ticket';
        $breadcrumbs = ['Master', 'Input Ticket'];
        $action = route('transactions.store');
        $method = 'POST';

        if (request('tipe') == 'sewa') {
            $tickets = Sewa::get();
        } else {
            $tickets = Ticket::get();
        }

        $draftIds = Transaction::query()
            ->where('user_id', auth()->id())
            ->where('is_active', 0)
            ->where('transaction_type', 'ticket')
            ->pluck('id');

        if ($draftIds->isNotEmpty()) {
            DetailTransaction::query()->whereIn('transaction_id', $draftIds->all())->delete();
            Transaction::query()->whereIn('id', $draftIds->all())->delete();
        }

        session()->forget('ticket_cart_items_user_' . auth()->id());

        $transaction = new Transaction();
        $transaction->id = 0;

        $setting = Setting::asObject();
        $total = 0;


        return view('transaction.form', compact('title', 'breadcrumbs', 'action', 'method', 'transaction', 'tickets', 'total', 'setting'));
    }

    public function store(CreateTransactionRequest $request)
    {

        try {
            $request->validate([
                'metode' => ['required', Rule::in(PaymentMethod::coreValidationValues())],
            ]);

            DB::beginTransaction();

            $transactions = [];

            $attr = $request->except('name', 'ticket', 'type_customer', 'print', 'jumlah');
            $ticket = Ticket::where('id', $request->ticket)->first();
            $now = Carbon::now('Asia/Jakarta');

            // $tipe = $request->type_customer;
            $tipe = 'group';
            $attr['ticket_id'] = $request->ticket;
            $attr['tipe'] = $tipe;
            $attr['nama_customer'] = $request->name;
            $attr['metode'] = PaymentMethod::normalize($request->metode);
            $attr['cash'] = $request->cash;
            $attr['amount'] = 1;
            $attr['harga_ticket'] = $request->harga_ticket;
            $attr['kembalian'] = $request->kembalian;
            $attr['discount'] = ($request->harga_ticket * $request->discount) / 100;
            $attr['user_id'] = auth()->user()->id;
            $attr['transaction_type'] = 'ticket';
            $showPrint = $request->show_print;

            $print = 1;
            $transactions = [];
            $notrx = Transaction::nextNoTrxByType('ticket', $now);

            if ($tipe == 'individual') {
                for ($i = 0; $i < $request->amount; $i++) {
                    $attr['no_trx'] = $notrx++;
                    $attr['ticket_code'] = Transaction::buildTicketCodeByType('ticket', $now, $attr['no_trx']);
                    $attr['transaction_type'] = 'ticket';

                    $transaction = Transaction::create($attr);

                    $transactions[] = $transaction->id;
                }
            } else {
                $attr['no_trx'] = $notrx;
                $attr['ticket_code'] = Transaction::buildTicketCodeByType('ticket', $now, $notrx);
                $attr['amount'] = $request->amount;
                $attr['transaction_type'] = 'ticket';

                $transaction = Transaction::create($attr);

                $transactions = $transaction->id;
            }

            DB::commit();

            $tickets = [];

            if ($tipe == 'individual') {
                foreach ($transactions as $transaction) {
                    $tickets[] =   Transaction::where('id', $transaction)->first();
                }
            } else {
                $tickets[] = $transaction;
            }

            return view('transaction.print', compact('tipe', 'print', 'tickets', 'showPrint', '
            '));
        } catch (\Throwable $th) {
            return $th->getMessage();
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function show(Transaction $transaction)
    {
        return response()->json([
            'status' => 'success',
            'ticket' => $transaction
        ], 200);
    }

    public function edit(Transaction $transaction)
    {
        $title = 'Edit Transaction';
        $breadcrumbs = ['Master', 'Edit Transaction'];
        $action = route('transactions.update', $transaction->id);
        $method = 'PUT';

        return view('transaction.form', compact('title', 'breadcrumbs', 'action', 'method', 'transaction'));
    }

    public function update(CreateTransactionRequest $request, Transaction $transaction)
    {
        try {
            DB::beginTransaction();

            $transaction->update($request->all());

            DB::commit();

            return redirect()->route('transactions.index')->with('success', "Transaction berhasil diupdate");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function destroy(Transaction $transaction)
    {
        try {
            DB::beginTransaction();

            foreach ($transaction->detail as $detail) {
                $detail->delete();
            }

            $transaction->delete();

            DB::commit();

            return redirect()->route('transactions.index')->with('success', "Transaction berhasil dihapus");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function print(Transaction $transaction)
{
    if ($transaction->transaction_type === 'rental') {
        if (!$transaction->ticket_id) {
            abort(404);
        }

        return redirect()->route('penyewaan.print', $transaction->ticket_id);
    }

    $payload = $this->buildTicketPrintPayload($transaction, false);

    return view('transaction.print', $payload + ['isPdf' => false]);
}

    public function ticketPdf(Transaction $transaction)
    {
        if ($transaction->transaction_type !== 'ticket') {
            abort(404);
        }

        $payload = $this->buildTicketPrintPayload($transaction, true);
        $pdfPaperHeight = $this->resolveTicketPdfPaperHeight($payload);

        $pdf = Pdf::loadView('transaction.print', $payload + [
            'isPdf' => true,
            'autoPrint' => false,
            'autoRedirect' => false,
        ])->setPaper([0, 0, 226.77, $pdfPaperHeight]);

        $ticketCode = (string) ($transaction->ticket_code ?? ('TRX-' . $transaction->id));
        $safeCode = preg_replace('/[^A-Za-z0-9_\-]/', '-', $ticketCode);
        $fileName = 'ticket-' . $safeCode . '.pdf';

        if (request()->boolean('inline')) {
            return $pdf->stream($fileName);
        }

        return $pdf->download($fileName);
    }

    private function buildTicketPrintPayload(Transaction $transaction, bool $forPdf): array
    {
        $setting = Setting::asObject();
        $ticketCodeMode = in_array((string) ($setting->ticket_code_mode ?? 'unique'), ['shared', 'unique'], true)
            ? (string) $setting->ticket_code_mode
            : 'unique';

        DB::transaction(function () use ($transaction, $ticketCodeMode) {
            DetailTransaction::applyTicketCodeMode($transaction, $ticketCodeMode);
        });

        $transaction->load(['detail.ticket', 'user']);
        $tickets = [];
        if ($ticketCodeMode === 'unique') {
            $tickets = $transaction->detail->map(function ($detail) {
                return [
                    'name' => $detail->ticket->name ?? '-',
                    'harga' => number_format(((float) ($detail->total ?? 0)) + ((float) ($detail->ppn ?? 0)), 0, ',', '.'),
                    'ticket_code' => (string) ($detail->ticket_code ?? '-'),
                    'qty' => 1,
                ];
            })->values()->all();
        } else {
            foreach ($transaction->detail as $detail) {
                $qty = max((int) ($detail->qty ?? 1), 1);
                $lineSubtotal = ((float) ($detail->total ?? 0)) + ((float) ($detail->ppn ?? 0));
                $lineUnitPrice = $qty > 0 ? ($lineSubtotal / $qty) : $lineSubtotal;

                for ($i = 1; $i <= $qty; $i++) {
                    $tickets[] = [
                        'name' => $detail->ticket->name ?? '-',
                        'harga' => number_format($lineUnitPrice, 0, ',', '.'),
                        'ticket_code' => (string) ($detail->ticket_code ?? '-'),
                        'qty' => $qty,
                    ];
                }
            }
        }

        $logo = null;
        if (!empty($setting->logo)) {
            $logoPath = public_path('storage/' . $setting->logo);
            if (is_file($logoPath)) {
                $logo = $forPdf ? $this->encodeImageAsDataUri($logoPath) : asset('/storage/' . $setting->logo);
            }
        }

        if ($logo === null) {
            $fallbackLogoPath = public_path('/images/rio.png');
            if (is_file($fallbackLogoPath)) {
                $logo = $this->encodeImageAsDataUri($fallbackLogoPath);
            }
        }

        $use = $logo !== null ? 1 : 0;
        $name = $setting->name ?? 'Ticketing';
        $ucapan = $this->sanitizeTicketFooterText($setting->ucapan ?? 'Terima Kasih');
        $deskripsi = $this->sanitizeTicketFooterText($setting->deskripsi ?? 'qr code hanya berlaku satu kali');
        $ppn = $setting->ppn ?? 0;
        $print = 0;
        $ticketPrintOrientation = $setting->ticket_print_orientation ?? 'without_summary';

        return compact(
            'transaction',
            'logo',
            'ucapan',
            'deskripsi',
            'use',
            'name',
            'tickets',
            'ppn',
            'print',
            'ticketPrintOrientation'
        );
    }

    private function resolveTicketPdfPaperHeight(array $payload): float
    {
        $ticketPrintMode = $this->normalizeTicketPrintMode((string) ($payload['ticketPrintOrientation'] ?? 'without_summary'));
        $transaction = $payload['transaction'] ?? null;
        $details = $transaction?->detail ?? collect();
        $tickets = $payload['tickets'] ?? [];
        $ticketCount = max(count($tickets), 1);

        $cardLineCount = 0;
        foreach (['nama_kartu', 'no_kartu', 'bank'] as $field) {
            if (trim((string) ($transaction?->{$field} ?? '')) !== '') {
                $cardLineCount++;
            }
        }

        $footerLineCount = $this->countTicketTextLines((string) ($payload['ucapan'] ?? ''))
            + $this->countTicketTextLines((string) ($payload['deskripsi'] ?? ''));

        $ticketHeightMm = 92
            + ($cardLineCount * 5)
            + ($footerLineCount * 4.5);

        $summaryHeightMm = $ticketHeightMm;
        if ($ticketPrintMode === 'with_summary') {
            $summaryItemCount = $details
                ->groupBy(function ($detail) {
                    $ticketId = (int) ($detail->ticket_id ?? 0);
                    $ticketName = trim((string) ($detail->ticket->name ?? '-'));
                    return $ticketId . '|' . $ticketName;
                })
                ->count();

            $summaryHeightMm = 96
                + ($summaryItemCount * 10)
                + ($cardLineCount * 5)
                + ($footerLineCount * 4.5);
        }

        $sectionGapMm = 6;
        $paperHeightMm = ($ticketHeightMm * $ticketCount) + ($sectionGapMm * max($ticketCount - 1, 0));

        if ($ticketPrintMode === 'with_summary') {
            $paperHeightMm += $summaryHeightMm + $sectionGapMm;
        }

        $paperHeightMm += 18;
        $paperHeightMm = min(max($paperHeightMm, 120), 3000);

        return round($paperHeightMm * 72 / 25.4, 2);
    }

    private function normalizeTicketPrintMode(string $ticketPrintOrientation): string
    {
        if ($ticketPrintOrientation === 'portrait') {
            return 'with_summary';
        }

        if ($ticketPrintOrientation === 'portrait_with_first_qr') {
            return 'without_summary';
        }

        return in_array($ticketPrintOrientation, ['with_summary', 'without_summary'], true)
            ? $ticketPrintOrientation
            : 'without_summary';
    }

    private function sanitizeTicketFooterText(?string $value): string
    {
        $text = trim((string) $value);

        return in_array($text, ['', '-', '--'], true) ? '' : $text;
    }

    private function countTicketTextLines(string $value): int
    {
        $text = $this->sanitizeTicketFooterText($value);
        if ($text === '') {
            return 0;
        }

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $nonEmptyLines = array_filter($lines, static fn ($line) => trim((string) $line) !== '');

        return max(count($nonEmptyLines), 1);
    }

    private function encodeImageAsDataUri(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    public function report(Request $request)
    {
        $title = 'Report Transaction';
        $breadcrumbs = ['Master', 'Report Transaction'];
        $transactions = Transaction::get();
        $from = $request->from ? Carbon::parse($request->from)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
        $to = $request->to ? Carbon::parse($request->to)->addDay(1)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
        $tickets = Ticket::whereNotIn('id', [11, 12, 13])->get();

        return view('transaction.report', compact('title', 'breadcrumbs', 'transactions', 'from', 'to', 'tickets'));
    }

    public function invoice(Transaction $transaction)
    {
        if (!in_array($transaction->transaction_type, ['registration', 'renewal'])) {
            abort(404);
        }

        $member = $transaction->member;
        if (!$member) {
            abort(404);
        }

        $member->load(['membership', 'childs']);
        $cashierName = $transaction->user?->name ?? '-';
        $setting = Setting::asObject();
        $ucapan = $setting->ucapan ?? 'Terima Kasih';
        $deskripsi = $setting->deskripsi ?? '';

        return view('member.invoice', compact('member', 'transaction', 'cashierName', 'ucapan', 'deskripsi'));
    }

    public function invoicePdf(Transaction $transaction)
    {
        $payload = $this->buildMembershipInvoicePayload($transaction);

        return view('member.invoice-pdf', $payload);
    }

    public function invoicePdfFile(Transaction $transaction)
    {
        $payload = $this->buildMembershipInvoicePayload($transaction);
        $payload['auto_print'] = false;

        $pdf = Pdf::loadView('member.invoice-pdf', $payload)
            ->setPaper([0, 0, 226.77, 566.93]);

        $invoiceCode = (string) ($transaction->ticket_code ?? ('INV-' . $transaction->id));
        $safeCode = preg_replace('/[^A-Za-z0-9_\-]/', '-', $invoiceCode);
        $fileName = 'invoice-' . $safeCode . '.pdf';

        if (request()->boolean('inline')) {
            return $pdf->stream($fileName);
        }

        return $pdf->download($fileName);
    }

    private function buildMembershipInvoicePayload(Transaction $transaction): array
    {
        if (!in_array($transaction->transaction_type, ['registration', 'renewal'])) {
            abort(404);
        }

        $member = $transaction->member;
        if ($member) {
            $member->load(['membership', 'childs']);
        } else {
            $memberInfoRaw = trim((string) ($transaction->member_info ?? ''));
            $memberName = '-';
            $memberPhone = null;
            if ($memberInfoRaw !== '') {
                $parts = array_map('trim', explode('-', $memberInfoRaw, 2));
                $memberName = $parts[0] !== '' ? $parts[0] : '-';
                $memberPhone = $parts[1] ?? null;
            }

            $member = (object) [
                'nama' => $memberName,
                'no_ktp' => null,
                'no_hp' => $memberPhone,
                'tgl_register' => null,
                'tgl_expired' => null,
                'membership' => (object) [
                    'name' => null,
                    'price' => 0,
                    'max_access' => null,
                ],
                'childs' => collect(),
            ];
        }

        $adminFee = max(0, (float) ($transaction->admin_fee ?? 0));
        $type = $adminFee > 0
            ? 'Perpanjangan Baru'
            : ($transaction->transaction_type === 'renewal' ? 'Perpanjangan' : 'Registrasi');
        $invoiceCode = $transaction->ticket_code;
        $price = 'Rp. ' . number_format($member->membership->price ?? 0, 0, ',', '.');
        $date = $transaction->created_at?->format('d/m/Y H:i:s') ?? now('Asia/Jakarta')->format('d/m/Y H:i:s');
        $cashierName = $transaction->user?->name ?? '-';
        $autoPrint = request()->boolean('print');

        $setting = Setting::asObject();
        $appName = $setting->name ?? 'Ticketing App';
        $ucapan = $setting->ucapan ?? 'Terima Kasih';
        $deskripsi = $setting->deskripsi ?? '';
        $logoData = null;
        if ($setting && $setting->use_logo && $setting->logo) {
            $logoPath = public_path('storage/' . $setting->logo);
            if (is_file($logoPath)) {
                $logoBase64 = base64_encode(file_get_contents($logoPath));
                $logoMime = pathinfo($logoPath, PATHINFO_EXTENSION) === 'png' ? 'image/png' : 'image/jpeg';
                $logoData = 'data:' . $logoMime . ';base64,' . $logoBase64;
            }
        }

        return [
            'member' => $member,
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
        ];
    }

    // Dalam TransactionController.php

public function setFullScan(Transaction $transaction)
{
    try {
        // Mulai transaksi database untuk memastikan atomisitas
        DB::beginTransaction();

        // Hanya proses jika transaction_type adalah 'ticket'
        // Catatan: Asumsi $transaction->transaction_type sesuai dengan kolom yang benar (misalnya 'tipe' di DB Anda)
        if ($transaction->transaction_type != 'ticket') {
             return back()->with('error', "Aksi ini hanya berlaku untuk transaksi tiket.");
        }

        $detailTransactions = $transaction->detail;

        // 1. Update detail transactions
        $scanLimit = (int) Setting::valueOf('ticket_scan_limit', 0);
        foreach ($detailTransactions as $detail) {
            $qty = max((int) $detail->qty, 0);
            $allowed = $scanLimit > 0 ? ($qty * $scanLimit) : $qty;
            // Set scanned menjadi sama dengan batas scan yang diizinkan
            $detail->scanned = $allowed;
            // Opsional: Anda mungkin ingin mengatur status detail menjadi 'close' jika ada
            // $detail->status = 'close';
            $detail->save();
        }

        // 2. Perbarui status utama transaksi menjadi 'closed'
        // Baris ini sudah ada sebagai komentar dan kini diaktifkan/dimodifikasi
        if ($transaction->status != 'closed') {
           $transaction->status = 'closed';
           $transaction->save();
        }

        // Commit transaksi jika semua berhasil
        DB::commit();

        return back()->with('success', "Transaksi " . $transaction->ticket_code . " berhasil ditandai sebagai Full Scanned dan ditutup.");

    } catch (\Throwable $th) {
        // Rollback transaksi jika terjadi kesalahan
        DB::rollBack();
        return back()->with('error', $th->getMessage());
    }
}
}
