<?php

namespace App\Http\Controllers;

use App\Models\DetailTransaction;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Support\PaymentMethod;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\ImagickEscposImage;

class DetailTransactionController extends Controller
{
    private function cartSessionKey(): string
    {
        return 'ticket_cart_items_user_' . (string) (auth()->id() ?? 'guest');
    }

    private function getCartItems(): array
    {
        return array_values(session($this->cartSessionKey(), []));
    }

    private function putCartItems(array $items): void
    {
        session([$this->cartSessionKey() => array_values($items)]);
    }

    private function clearCartItems(): void
    {
        session()->forget($this->cartSessionKey());
    }

    private function calculateCartTotalPrice(array $items): int
    {
        return (int) array_reduce($items, function ($carry, $item) {
            $qty = max((int) ($item['qty'] ?? 1), 1);
            $harga = max((int) ($item['ticket_harga'] ?? 0), 0);
            $ppn = max((int) ($item['ticket_ppn'] ?? 0), 0);

            return $carry + (($harga + $ppn) * $qty);
        }, 0);
    }

    private function isSessionMode($transactionId): bool
    {
        $id = (int) $transactionId;
        if ($id <= 0) {
            return true;
        }

        return !Transaction::query()->whereKey($id)->exists();
    }

    public function index(Request $request,  $id)
    {
        if ($request->ajax()) {
            if ($this->isSessionMode($id)) {
                $items = collect($this->getCartItems())->map(function (array $item) {
                    return (object) [
                        'id' => (string) ($item['row_id'] ?? ''),
                        'ticket_name' => (string) ($item['ticket_name'] ?? 'Ticket'),
                        'ticket_harga' => (int) ($item['ticket_harga'] ?? 0),
                        'ticket_ppn' => (int) ($item['ticket_ppn'] ?? 0),
                        'qty' => max((int) ($item['qty'] ?? 1), 1),
                    ];
                });

                return DataTables::collection($items)
                    ->addIndexColumn()
                    ->editColumn('action', function ($row) {
                        $route = route('detail.destroy.session', $row->id);
                        return '<button type="button" data-route="' . $route . '" class="delete btn btn-danger btn-delete btn-sm"><i class="fas fa-trash"></i></button>';
                    })
                    ->editColumn('ticket', function ($row) {
                        return $row->ticket_name;
                    })
                    ->editColumn('qty', function ($row) {
                        return '<input type="number" name="qty" id="' . $row->id . '" class="form-control qty" min="1" value="' . $row->qty . '" autofocus>';
                    })
                    ->editColumn('harga', function ($row) {
                        return number_format(($row->ticket_harga + $row->ticket_ppn), 0, ',', '.');
                    })
                    ->editColumn('total', function ($row) {
                        return number_format(($row->ticket_harga + $row->ticket_ppn) * $row->qty, 0, ',', '.');
                    })
                    ->rawColumns(['action', 'qty'])
                    ->make(true);
            }

            $data = DetailTransaction::where('transaction_id', $id);

            return DataTables::eloquent($data)
                ->addIndexColumn()
                ->editColumn('action', function ($row) {
                    $actionBtn = '<button type="button" data-route="' . route('detail.destroy', $row->id) . '" class="delete btn btn-danger btn-delete btn-sm"><i class="fas fa-trash"></i></button>';
                    return $actionBtn;
                })
                ->editColumn('ticket', function ($row) {
                    return $row->ticket->name;
                })
                ->editColumn('qty', function ($row) {
                    return '<input type="number" name="qty" id="' . $row->id . '" class="form-control qty" value="' . $row->qty . '" autofocus>';
                })
                ->editColumn('harga', function ($row) {
                    return number_format($row->ticket->harga + $row->ticket->ppn, 0, ',', '.');
                })
                ->editColumn('total', function ($row) {
                    return number_format(($row->ticket->harga + $row->ticket->ppn) * $row->qty, 0, ',', '.');
                })
                ->rawColumns(['action', 'qty'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        try {
            $ticket = Ticket::find($request->ticket);
            if (!$ticket) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ticket tidak ditemukan.',
                ], 404);
            }

            if ($this->isSessionMode($request->transaction)) {
                $items = $this->getCartItems();
                $items[] = [
                    'row_id' => (string) Str::uuid(),
                    'ticket_id' => (int) $ticket->id,
                    'ticket_name' => (string) $ticket->name,
                    'ticket_harga' => (int) $ticket->harga,
                    'ticket_ppn' => (int) $ticket->ppn,
                    'ticket_code' => 'TKT' . date('YmdHis') . rand(100, 999),
                    'qty' => 1,
                ];
                $this->putCartItems($items);

                $totalPrice = $this->calculateCartTotalPrice($items);

                return response()->json([
                    'status' => 'success',
                    'detail' => $items,
                    'totalPrice' => number_format($totalPrice, 0, ',', '.'),
                    'price' => $totalPrice,
                ]);
            }

            DB::beginTransaction();

            DetailTransaction::create([
                'transaction_id' => $request->transaction,
                'ticket_id' => $request->ticket,
                'ticket_code' => 'TKT' . date('YmdHis') . rand(100, 999),
                'qty' => 1,
                'total' => $ticket->harga,
                'ppn' => $ticket->ppn,
            ]);

            $amount = DetailTransaction::where(['transaction_id' => $request->transaction])->sum('qty');

            // if (!in_array($request->ticket, [11, 12])) {
            //     $asuransi = DetailTransaction::where(['transaction_id' => $request->transaction, 'ticket_id' => 13])->first();

            //     if ($asuransi) {
            //         $asuransi->update([
            //             'qty' => $amount - $asuransi->qty,
            //         ]);

            //         $asuransi->update([
            //             'total' => $asuransi->qty * Ticket::find($asuransi->ticket_id)->harga
            //         ]);
            //     }
            // }

            $detail = DetailTransaction::where('transaction_id', $request->transaction)->get();
            $totalPrice = DetailTransaction::where('transaction_id', $request->transaction)->sum('total') + DetailTransaction::where('transaction_id', $request->transaction)->sum('ppn');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'detail' => $detail,
                'totalPrice' => number_format($totalPrice, 0, ',', '.'),
                'price' => $totalPrice
            ]);
        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ]);
        }
    }

    public function destroy(DetailTransaction $detailTransaction)
    {
        try {
            DB::beginTransaction();

            $asuransi = DetailTransaction::where(['transaction_id' => $detailTransaction->transaction_id, 'ticket_id' => 13])->first();

            $amount = DetailTransaction::where(['transaction_id' => $detailTransaction->transaction_id])->count('qty');

            if ($asuransi) {
                if ($amount == 1) {
                    $asuransi->update([
                        'qty' => ($asuransi->qty - $detailTransaction->qty) + 1,
                    ]);

                    $asuransi->update([
                        'total' => $asuransi->qty * Ticket::find($asuransi->ticket_id)->harga
                    ]);
                } else {
                    $asuransi->update([
                        'qty' => $asuransi->qty - $detailTransaction->qty
                    ]);

                    $asuransi->update([
                        'total' => $asuransi->qty * Ticket::find($asuransi->ticket_id)->harga
                    ]);
                }
            }

            $detailTransaction->delete();

            DB::commit();
            return back();
        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return response()->json([
                'message' => $th->getMessage()
            ]);
        }
    }

    public function destroySession(string $rowId)
    {
        $items = $this->getCartItems();
        $items = array_values(array_filter($items, function (array $item) use ($rowId) {
            return (string) ($item['row_id'] ?? '') !== (string) $rowId;
        }));
        $this->putCartItems($items);

        return back();
    }

    public function save(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $transaction = Transaction::find($id);
            if (!$transaction) {
                $cartItems = $this->getCartItems();
                if (empty($cartItems)) {
                    DB::rollBack();
                    return back()->with('error', 'Belum ada item ticket yang dipilih.');
                }

                $transaction = Transaction::create([
                    'ticket_id' => 0,
                    'user_id' => auth()->id(),
                    'no_trx' => 0,
                    'ticket_code' => 'TMP/' . now('Asia/Jakarta')->format('YmdHis') . '/' . auth()->id(),
                    'transaction_type' => 'ticket',
                    'status' => 'open',
                    'is_active' => 0,
                ]);

                foreach ($cartItems as $item) {
                    $qty = max((int) ($item['qty'] ?? 1), 1);
                    $harga = max((int) ($item['ticket_harga'] ?? 0), 0);
                    $ppn = max((int) ($item['ticket_ppn'] ?? 0), 0);

                    DetailTransaction::create([
                        'transaction_id' => $transaction->id,
                        'ticket_id' => (int) ($item['ticket_id'] ?? 0),
                        'ticket_code' => (string) ($item['ticket_code'] ?? ('TKT' . date('YmdHis') . rand(100, 999))),
                        'qty' => $qty,
                        'total' => $harga * $qty,
                        'ppn' => $ppn * $qty,
                    ]);
                }
            }

            $tickets = [];
            $print = $transaction->detail()->sum('qty') ?? 1;
            $totalHarga = $transaction->detail()->sum('total');
            $firstTrx = $transaction->detail()->sum('qty');

            $discount = request('discount') ?? 0;
            $disc = request('discount') ? ($totalHarga * $discount) / 100 : 0;
            // $setting = Setting::first();
            $setting = Setting::asObject();
            $ticketCodeMode = in_array((string) ($setting->ticket_code_mode ?? 'unique'), ['shared', 'unique'], true)
                ? (string) $setting->ticket_code_mode
                : 'unique';

            $validatedPayment = $request->validate([
                'metode' => ['required', Rule::in(PaymentMethod::coreValidationValues())],
            ]);
            $metode = PaymentMethod::normalize($validatedPayment['metode']);
            $isCardMethod = in_array($metode, ['debit', 'kredit'], true);

            $normalizeMoney = static function ($value): int {
                $digitsOnly = preg_replace('/[^\d]/', '', (string) $value);
                return $digitsOnly === '' ? 0 : (int) $digitsOnly;
            };

            // Ambil total PBJT dari detail transaksi agar selalu sesuai qty aktual.
            $totalPpn = (float) $transaction->detail()->sum('ppn');
            $subtotalGross = (float) $transaction->detail()->sum('total') + $totalPpn;
            $subtotalAfterDiscount = max(0, $subtotalGross - (float) $disc);

            $bayarInput = $normalizeMoney($request->bayar);
            $totalPriceInput = $normalizeMoney($request->totalPrice);
            $kembaliInput = $normalizeMoney($request->kembali);

            $isCashMethod = $metode === 'cash';
            $bayarGross = $isCashMethod
                ? $bayarInput
                : ($totalPriceInput > 0 ? $totalPriceInput : (int) round($subtotalAfterDiscount));

            if ($bayarGross <= 0) {
                $bayarGross = (int) round($subtotalAfterDiscount);
            }

            if (!$isCashMethod) {
                $kembaliInput = 0;
            }

            $bayar = max(0, $bayarGross - (int) round($totalPpn));

            // $ppn = $request->ppn ?? 0;
            $print = $request->hide_print ?? 0;
            // $totalPpn = ($totalHarga - $disc) * $ppn / 100;

            if ($isCardMethod) {
                $request->validate([
                    'nama_kartu' => 'required|string|max:100',
                    'no_kartu' => 'required|string|max:100',
                    'bank' => 'required|string|max:100',
                ]);
            }

            $shouldAssignInvoiceNumber = ((int) ($transaction->no_trx ?? 0) <= 0) || ((int) ($transaction->is_active ?? 0) === 0);
            $transactionDate = $transaction->created_at
                ? Carbon::parse($transaction->created_at)->timezone('Asia/Jakarta')
                : Carbon::now('Asia/Jakarta');
            $assignedNoTrx = $shouldAssignInvoiceNumber
                ? Transaction::nextNoTrxByType('ticket', $transactionDate)
                : (int) $transaction->no_trx;
            $assignedTicketCode = $shouldAssignInvoiceNumber
                ? Transaction::buildTicketCodeByType('ticket', $transactionDate, $assignedNoTrx)
                : (string) $transaction->ticket_code;

            $transaction->update([
                'ticket_id' => 0,
                'no_trx' => $assignedNoTrx,
                'ticket_code' => $assignedTicketCode,
                'amount' => $firstTrx,
                'is_active' => 1,
                'discount' => $discount,
                'disc' => $disc,
                'bayar' => $bayar,
                'transaction_type' => 'ticket',
                'kembali' => $kembaliInput,
                'metode' => $metode,
                'nama_kartu' => $isCardMethod ? $request->nama_kartu : null,
                'no_kartu' => $isCardMethod ? $request->no_kartu : null,
                'bank' => $isCardMethod ? $request->bank : null,
                'ppn' => $totalPpn,
            ]);

            DetailTransaction::applyTicketCodeMode($transaction, $ticketCodeMode);
            $transaction->load(['detail.ticket']);
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

            DB::commit();
            $this->clearCartItems();
            $logo = null;
            if (!empty($setting->logo)) {
                $logoPath = public_path('storage/' . $setting->logo);
                if (is_file($logoPath)) {
                    $logo = asset('/storage/' . $setting->logo);
                }
            }
            if ($logo === null) {
                $fallbackLogoPath = public_path('/images/rio.png');
                if (is_file($fallbackLogoPath)) {
                    $logo = 'data:image/png;base64,' . base64_encode(file_get_contents($fallbackLogoPath));
                }
            }
            $ucapan = $setting->ucapan ?? 'Terima Kasih';
            $name = $setting->name ?? 'Ticketing';
            $deskripsi = $setting->deskripsi ?? 'qr code hanya berlaku satu kali';
            $use = $logo !== null ? 1 : 0;

            $ticketPrintOrientation = $setting->ticket_print_orientation ?? 'without_summary';
            return view('transaction.print', compact('transaction', 'logo', 'ucapan', 'deskripsi', 'use', 'name', 'tickets', 'print', 'ticketPrintOrientation'));
            // $print = $this->print($transaction);
            // if ($print["status"] == "success") {
            //     return back()->with('success', "Transaction success");
            // } else {
            //     return back()->with('error', $print["message"]);
            // }
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function remove(DetailTransaction $detailTransaction)
    {
        try {
            DB::beginTransaction();

            if (!in_array($detailTransaction->ticket_id, [11, 12])) {
                $qty = $detailTransaction->qty;

                $detailTransaction->update([
                    'qty' => $qty - 1
                ]);

                $detailTransaction->update([
                    'total' => $detailTransaction->qty * $detailTransaction->ticket->harga
                ]);

                $asuransi = DetailTransaction::where(['transaction_id' => $detailTransaction->transaction_id, 'ticket_id' => 13])->first();

                if ($asuransi) {
                    $asuransi->update([
                        'qty' => $asuransi->qty - 1
                    ]);

                    $asuransi->update([
                        'total' => $asuransi->qty * $asuransi->ticket->harga
                    ]);
                }
            }

            DB::commit();

            return back();
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function qty(Request $request)
    {
        try {
            $detailId = (string) $request->id;
            $qty = max((int) $request->qty, 1);
            $detail = ctype_digit($detailId) ? DetailTransaction::find((int) $detailId) : null;

            if (!$detail) {
                $items = $this->getCartItems();
                $updated = false;

                foreach ($items as &$item) {
                    if ((string) ($item['row_id'] ?? '') !== $detailId) {
                        continue;
                    }

                    $item['qty'] = $qty;
                    $updated = true;
                    break;
                }
                unset($item);

                if (!$updated) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Item ticket tidak ditemukan.'
                    ], 404);
                }

                $this->putCartItems($items);
                $totalPrice = $this->calculateCartTotalPrice($items);

                return response()->json([
                    'status' => 'success',
                    'totalPrice' => number_format($totalPrice, 0, ',', '.'),
                    'price' => $totalPrice
                ]);
            }

            DB::beginTransaction();

            $total = $qty * $detail->ticket->harga;
            $detail->update([
                'qty' => $qty,
                'total' => $total,
                'ppn' => $detail->ticket->ppn * $qty,
            ]);

            $totalPrice = DetailTransaction::where('transaction_id', $detail->transaction_id)
                ->sum(\DB::raw('total + ppn'));

            DB::commit();

            return response()->json([
                'status' => 'success',
                'totalPrice' => number_format($totalPrice, 0, ',', '.'),
                'price' => $totalPrice
            ]);
        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return response()->json([
                'message' => $th->getMessage()
            ]);
        }
    }

    function print($transaction)
    {
        // $transaction = Transaction::where(['is_active', 1, 'is_print' => 0, 'user_id' => auth()->user()->id])->first();

        $pathTransactions = [];
        $transactionFile = View::make('transaction.transaction')->with(['transaction' => $transaction])->render();
        $transactionPlain = strip_tags($transactionFile);

        $pathTransactions[] = [
            "invoice" => $transaction->ticket_code,
            "content" => $transactionPlain,
        ];

        foreach ($transaction->detail as $key => $detail) {
            $transactionDetailFile = View::make('transaction.detail')->with(['detail' => $detail])->render();
            $transactionDetailPlain = strip_tags($transactionDetailFile);

            $pathTransactions[] = [
                "invoice" => $detail->ticket_code,
                "content" => $transactionDetailPlain,
            ];
        }

        $print = $this->testPrint($pathTransactions);
        if ($print["status"] == "success") {
            return [
                "status" => "success"
            ];
        } else {
            return [
                "status" => "error",
                "message" => $print['message']
            ];
        }
    }

    function testPrint($pathTransactions)
    {
        try {
            $printerName = env('PRINTER');
            $connector = new WindowsPrintConnector($printerName);

            $printer = new Printer($connector);

            foreach ($pathTransactions as $path) {
                $printer->text($path["content"]);
                $printer->cut();
            }

            $printer->close();

            foreach ($pathTransactions as $path) {
                $invoiceCode = $path["invoice"];
                $transac = Transaction::where("ticket_code", $invoiceCode)->first();

                if ($transac) {
                    $transac->update(['is_print' => 1]);
                } else {
                    $detail = DetailTransaction::where('ticket_code', $invoiceCode)->first();
                    if ($detail) {
                        $detail->update(['is_print' => 1]);
                    }
                }
            }

            return [
                'status' => 'success'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
