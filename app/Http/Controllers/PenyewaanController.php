<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Sewa;
use App\Models\Member;
use App\Models\Ticket;
use App\Models\Setting;
use App\Models\Penyewaan;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\HistoryPenyewaan;
use App\Support\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class PenyewaanController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('permission:penyewaan-access');
    // }

    public function index()
    {
        $title = 'Data Transaksi Lainnya';
        $breadcrumbs = ['Master', 'Data Transaksi Lainnya'];
        $tickets = Sewa::get();

        return view('penyewaan.index', compact('title', 'breadcrumbs', 'tickets'));
    }

    public function get(Request $request)
    {
        if ($request->ajax()) {
            if ($request->tanggal) {
                $data = Penyewaan::whereDate('created_at', $request->tanggal)->orderBy('id', 'DESC');
            } else {
                $now = Carbon::now('Asia/Jakarta')->format('Y-m-d');
                $data = Penyewaan::whereDate('created_at', $now)->orderBy('id', 'DESC');
            }

            return DataTables::eloquent($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $previewUrl = route('penyewaan.print.pdf', $row->id) . '?inline=1';
                    $printUrl = route('penyewaan.print.pdf', $row->id) . '?inline=1';
                    $pdfUrl = route('penyewaan.print.pdf', $row->id);

                    $actionBtn = '<button type="button" class="btn btn-sm btn-primary btn-ticket-preview"'
                        . ' data-preview-url="' . e($previewUrl) . '"'
                        . ' data-print-url="' . e($printUrl) . '"'
                        . ' data-pdf-url="' . e($pdfUrl) . '"'
                        . ' title="Preview Transaksi Lainnya">Print</button> ';

                    if (auth()->user()->can('penyewaan-delete')) {
                        $actionBtn .= '<button type="button" data-route="' . route('penyewaan.destroy', $row->id) . '" class="delete btn btn-danger btn-delete btn-sm">Delete</button>';
                    }
                    return $actionBtn;
                })
                ->editColumn('ticket', function ($row) {
                    return $row->sewa->name;
                })
                ->editColumn('harga', function ($row) {
                    return 'Rp. ' . number_format($row->sewa->harga, 0, ',', '.');
                })
                ->editColumn('jumlah', function ($row) {
                    return 'Rp. ' . number_format($row->jumlah, 0, ',', '.');
                })
                ->editColumn('keterangan', function ($row) {
                    return $row->keterangan ?? '-';
                })
                ->editColumn('start_time', function ($row) {
                    return $row->start_time ?? '-';
                })
                ->editColumn('end_time', function ($row) {
                    return $row->end_time ?? '-';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }
public function store(Request $request)
{
    try {
        $request->validate([
            'ticket' => 'required|numeric',
            'qty' => 'required|numeric',
            'metode' => ['required', Rule::in(PaymentMethod::coreValidationValues())],
            'jumlah' => 'required|string',
            'harga_ticket' => 'nullable|string',
            'jam' => 'nullable|numeric|min:1',
            // Tambahkan validasi untuk bayar/kembali jika metodenya cash
            'bayar' => 'nullable|string',
            'kembali' => 'nullable|string',
            // Validasi RFID jika metode tap
            'name' => 'nullable|string',
            'nama_kartu' => 'nullable|string|max:100',
            'no_kartu' => 'nullable|string|max:100',
            'bank' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        $now = Carbon::now('Asia/Jakarta');
        $metode = PaymentMethod::normalize($request->metode);
        $isCardMethod = in_array($metode, ['debit', 'kredit'], true);
        if ($isCardMethod) {
            $request->validate([
                'nama_kartu' => 'required|string|max:100',
                'no_kartu' => 'required|string|max:100',
                'bank' => 'required|string|max:100',
            ]);
        }

        $ticketData = Sewa::findOrFail($request->ticket);
        $qty = (int) $request->qty;
        $startTime = $request->start_time ?: Carbon::now('Asia/Jakarta')->format('H:i');
        $endTime = $request->end_time;

        if ((int) ($ticketData->use_time ?? 0) === 1) {
            $jamSewa = (float) $request->input('jam', 0);
            if ($jamSewa <= 0) {
                DB::rollBack();
                return back()->with('error', 'Jam sewa wajib diisi untuk item berbasis waktu.');
            }

            $startTimeObj = Carbon::createFromFormat('H:i', $startTime, 'Asia/Jakarta');
            $endTime = $startTimeObj->copy()->addMinutes((int) round($jamSewa * 60))->format('H:i');
        }

        // Harga per-item dari form diperlakukan sebagai DPP (sebelum PBJT).
        $hargaTicketInput = (int) str_replace('.', '', (string) $request->harga_ticket);
        $defaultHargaPerItem = (int) $ticketData->harga;
        $isNominalFlexible = (int) ($ticketData->is_nominal_flexible ?? 0) === 1;
        $usePpn = (int) ($ticketData->use_ppn ?? 0) === 1;

        $setting = Setting::asObject();
        $defaultPpnRate = max((float) (($setting->ppn ?? 0) / 100), 0.0);
        $ppnRate = $defaultPpnRate;
        if ($usePpn && (float) $ticketData->harga > 0) {
            // Prioritas pakai rasio PBJT dari master item agar tetap konsisten per item.
            $ppnRate = max((float) $ticketData->ppn / (float) $ticketData->harga, 0.0);
        }

        // Keamanan: jika dynamic price nonaktif, abaikan input harga dari client.
        $basePerItem = $defaultHargaPerItem;
        if ($isNominalFlexible && $hargaTicketInput > 0) {
            $basePerItem = $hargaTicketInput;
        }

        // Untuk dynamic price, PBJT ikut kalkulasi otomatis dari DPP input.
        $ppnPerItem = $usePpn ? (float) round($basePerItem * $ppnRate) : 0.0;
        $basePrice = max(0, $basePerItem * $qty); // net total (DPP)
        $ppnAmount = max(0, $ppnPerItem * $qty);  // PBJT total
        $grossAmountTotal = $basePrice + $ppnAmount; // total akhir

        // ================== TRANSACTION ==================


        // ================== TRANSACTION DETAIL ==================
        // DetailTransaction::create([
        //     'transaction_id' => $transaction->id,
        //     'ticket_id' => $request->ticket,
        //     'qty' => $request->qty,
        //     'total' => $netPrice,        // total bersih
        //     'ppn' => $ppnAmount
        // ]);

        // ================== RENTAL / TAP / SALDO CHECK ==================
        if ($metode === 'tap') {

            $member = Member::where('rfid', $request->name)->first();
            if (!$member) {
                DB::rollBack();
                return back()->with('error', "Member tidak ditemukan");
            }

            $grossAmount = $grossAmountTotal;

            if ($member->saldo < $grossAmount) {
                DB::rollBack();
                return back()->with('error', "Saldo anda tidak mencukupi. Dibutuhkan: " . number_format($grossAmount));
            }

            $penyewaan = Penyewaan::create([
                'sewa_id' => $request->ticket,
                'qty' => $request->qty,
                'metode' => $metode,
                'jumlah' => $grossAmount, // Simpan gross amount di sini (total yang dipotong dari saldo)
                'bayar' => $grossAmount,
                'kembali' => 0,
                'keterangan' => $request->keterangan,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'user_id' => auth()->user()->id
            ]);

            $member->update([
                'saldo' => $member->saldo - $grossAmount
            ]);

            HistoryPenyewaan::create([
                'penyewaan_id' => $penyewaan->id,
                'member_id' => $member->id,
            ]);
            $rentalNoTrx = Transaction::nextNoTrxByType('rental', $now);

            $transaction = Transaction::create([
            'ticket_id' => $penyewaan->id,
            'user_id' => auth()->id(),
            'no_trx' => $rentalNoTrx,
            'ticket_code' => Transaction::buildTicketCodeByType('rental', $now, $rentalNoTrx),
            'transaction_type' => 'rental',
            'tipe' => 'individual',
                'metode' => $metode,
            'nama_kartu' => $isCardMethod ? $request->nama_kartu : null,
            'no_kartu' => $isCardMethod ? $request->no_kartu : null,
            'bank' => $isCardMethod ? $request->bank : null,
            'amount' => $request->qty,       // qty item
            'bayar' => $basePrice,       // total DPP (sebelum PBJT)
            'status' => 'open',
            'is_active' => 1,
            'ppn' => $ppnAmount,         // simpan ppn untuk laporan
        ]);

            DB::commit();
            return back()->with('success', "Transaksi lainnya berhasil (TAP). Saldo terpotong sebesar " . number_format($grossAmount));

        } else { // CASH

            $bayarCash = (int) str_replace('.', '', $request->bayar);
            $kembaliCash = (int) str_replace('.', '', $request->kembali);

            // Simpan data Penyewaan
            $sewa = Penyewaan::create([
                'sewa_id' => $request->ticket,
                'qty' => $request->qty,
                'metode' => $metode,
                // jumlah harus mengikuti total akhir (sudah termasuk PBJT)
                'jumlah' => $grossAmountTotal,
                'bayar' => $bayarCash,
                'kembali' => $kembaliCash,
                'keterangan' => $request->keterangan,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'user_id' => auth()->user()->id

            ]);
            $rentalNoTrx = Transaction::nextNoTrxByType('rental', $now);

            $transaction = Transaction::create([
            'ticket_id' => $sewa->id,
            'user_id' => auth()->id(),
            'no_trx' => $rentalNoTrx,
            'ticket_code' => Transaction::buildTicketCodeByType('rental', $now, $rentalNoTrx),
            'transaction_type' => 'rental',
            'tipe' => 'individual',
                'metode' => $metode,
            'nama_kartu' => $isCardMethod ? $request->nama_kartu : null,
            'no_kartu' => $isCardMethod ? $request->no_kartu : null,
            'bank' => $isCardMethod ? $request->bank : null,
            'amount' => $request->qty,       // qty item
            'bayar' => $basePrice,       // total DPP (sebelum PBJT)
            'status' => 'open',
            'is_active' => 1,
            'ppn' => $ppnAmount,         // simpan ppn untuk laporan
        ]);

            DB::commit();
            return $this->print($sewa->id);
        }


    } catch (\Throwable $th) {
        DB::rollBack();
        return back()->with('error', "Transaksi lainnya gagal. Error: " . $th->getMessage());
    }
}


    public function print($id)
    {
        $payload = $this->buildPenyewaanPrintPayload($id, false);

        return view('penyewaan.print', $payload + ['isPdf' => false]);
    }

    public function printPdf($id)
    {
        $payload = $this->buildPenyewaanPrintPayload($id, true);

        $pdf = Pdf::loadView('penyewaan.print', $payload + [
            'isPdf' => true,
            'autoPrint' => false,
        ])->setPaper([0, 0, 226.77, 453.54]);

        $trxCode = (string) ($payload['transaction']?->ticket_code ?? ('RENT-' . $id));
        $safeCode = preg_replace('/[^A-Za-z0-9_\-]/', '-', $trxCode);
        $fileName = 'rental-' . $safeCode . '.pdf';

        if (request()->boolean('inline')) {
            return $pdf->stream($fileName);
        }

        return $pdf->download($fileName);
    }

    private function buildPenyewaanPrintPayload(int $id, bool $forPdf): array
    {
        $penyewaan = Penyewaan::findOrFail($id);
        $transaction = Transaction::where('ticket_id', $id)
            ->where('transaction_type', 'rental')
            ->latest()
            ->first();
        $setting = Setting::asObject();

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

        $name = $setting->name ?? 'Ticketing';
        $ucapan = $setting->ucapan ?? 'Terima Kasih';
        $deskripsi = $setting->deskripsi ?? 'qr code hanya berlaku satu kali';
        $use = $setting->use_logo ?? false;

        return compact('penyewaan', 'transaction', 'logo', 'name', 'use', 'ucapan', 'deskripsi');
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

    public function destroy(Penyewaan $penyewaan)
    {
        try {
            $history = HistoryPenyewaan::where('penyewaan_id', $penyewaan->id)->first();

            if ($history) {
                $member = Member::find($history->member_id);
                $member->update([
                    'saldo' => $member->saldo + $penyewaan->jumlah
                ]);

                $history->delete();
                $penyewaan->delete();

                DB::commit();

                return back()->with('success', "Transaksi lainnya berhasil dihapus");
            } else {
                $penyewaan->delete();

                DB::commit();
                return back()->with('success', "Transaksi lainnya berhasil dihapus");
            }
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function create()
    {
        $title = 'Input Transaksi Lainnya Baru';
        $breadcrumbs = ['Master', 'Data Transaksi Lainnya', 'Input Baru'];
        $tickets = Sewa::get();

        return view('penyewaan.create', compact('title', 'breadcrumbs', 'tickets'));
    }
}
