<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetailTransaction;
use App\Models\History;
use App\Models\LimitMember;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Setting;
use App\Models\Terusan;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function getCode()
    {
        $tickets = Ticket::select(['id', 'name', 'harga'])->get();

        return $this->sendResponse($tickets, 'Tickets list');
    }

    public function detailGroupLast(Request $request)
    {
        $transaction = Transaction::where('status', 'open')
            ->where('tipe', 'group')
            ->where('gate', $request->gate)
            ->select(['ticket_code', 'amount', 'amount_scanned', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->first();

        $transaction['time'] = Carbon::parse($transaction->updated_at)->format('d/m/Y H:i:s');

        return response()->json($transaction);
    }

    public function getNoTrx()
    {
        $noTrx = Transaction::nextNoTrxByType('ticket', Carbon::now('Asia/Jakarta'));

        return response()->json([
            "no_trx" => $noTrx,
        ]);
    }

    public function checkIndividualTicket($ticket)
    {
        $transScanned = DetailTransaction::with('transaction')
            ->where('ticket_code', $ticket)
            ->first();

        if (!$transScanned) {
            return response()->json([
                "status" => "Not found",
                "message" => "Ticket not found"
            ]);
        }

        if (!$this->isTicketWithinValidity($transScanned->transaction?->created_at)) {
            return response()->json([
                "status" => "close",
                "count" => 0,
                "message" => "Ticket expired"
            ]);
        }

        $maxAllowed = $this->resolveMaxScan((int) $transScanned->qty);
        if ($maxAllowed <= 0 || (int) $transScanned->scanned >= $maxAllowed) {
            return response()->json([
                "status" => "close",
                "count" => 0,
                "message" => "Ticket already fully scanned"
            ]);
        }

        $cooldown = $this->ticketScanCooldownResponse($transScanned);
        if ($cooldown) {
            return $cooldown;
        }

        if ($transScanned->status == "close") {
            return response()->json([
                "status" => $transScanned->status,
                "count" => 0,
                "message" => "Ticket closed"
            ]);
        }

        $counting = (int) $transScanned->scanned + 1;
        $payload = [
            "scanned" => $counting,
        ];

        if ($counting >= $maxAllowed) {
            $payload["status"] = "close";
            $payload["scanned_at"] = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
        }

        DetailTransaction::where('ticket_code', $ticket)->update($payload);

        return response()->json([
            "status" => $counting >= $maxAllowed ? "close" : "open",
            "count" => max(0, $maxAllowed - $counting),
            "message" => "OK"
        ]);
    }

    public function checkGroupTicket(Request $request, $ticket)
    {
        $transScanned = Transaction::where('ticket_code', $ticket)->where('tipe', 'group')
            ->first();

        if (!$transScanned) {
            return response()->json([
                "status" => "not found",
                "message" => "Ticket not found"
            ]);
        }

        if (!$this->isTicketWithinValidity($transScanned->created_at)) {
            return response()->json([
                "status" => "closed",
                "count" => 0,
                "message" => "Ticket expired"
            ]);
        }

        $maxAllowed = $this->resolveMaxScan((int) $transScanned->amount);
        if ($maxAllowed <= 0 || (int) $transScanned->amount_scanned >= $maxAllowed) {
            return response()->json([
                "status" => "closed",
                "count" => 0,
                "message" => "Ticket already fully scanned"
            ]);
        }

        $transScanned->update([
            "gate" => $request->gate,
        ]);

        if ($transScanned->status == "closed") {
            return response()->json([
                "status" => $transScanned->status,
                "count" => 0,
                "message" => "Ticket closed"
            ]);
        }

        $counting = $transScanned->amount_scanned + 1;

        if ($counting >= $maxAllowed) {
            Transaction::where('ticket_code', $ticket)
                ->update([
                    "status" => "closed",
                    "amount_scanned" => $counting
                ]);
        } else {
            Transaction::where('ticket_code', $ticket)
                ->update([
                    "amount_scanned" => $counting
                ]);
        }

        return response()->json([
            "status" => $transScanned->status,
            "count" => max(0, $maxAllowed - $counting),
            "message" => "OK"
        ]);
    }

    public function check(Request $request)
    {
        if (empty($request->ticket)) {
            return response()->json([
                "status" => "error",
                "message" => "QR Code/Ticket tidak boleh kosong!"
            ], 400);
        }

        $transScanned = DetailTransaction::with('transaction')
            ->where('ticket_code', $request->ticket)
            ->first();

        if ($transScanned) {
            if (!$this->isTicketWithinValidity($transScanned->transaction?->created_at)) {
                return response()->json([
                    "status" => "close",
                    "message" => "Ticket expired"
                ]);
            }

            $invoice = Transaction::where('id', $transScanned->transaction_id)->first();

            DetailTransaction::where('ticket_code', $request->ticket)
                ->update([
                    "gate" => $request->gate,
                ]);

            if ($this->shouldCloseInvoice($invoice)) {
                $invoice->status = "closed";
                $invoice->amount_scanned = $invoice->detail()->sum('scanned');
                $invoice->save();

                return response()->json([
                    "status" => "close",
                    "count" => 0,
                    "message" => "Ticket closed"
                ]);
            }

            if ($transScanned->status == "close") {
                return response()->json([
                    "status" => $transScanned->status,
                    "count" => 0,
                    "message" => "Ticket closed"
                ]);
            }

            $maxAllowed = $this->resolveMaxScan((int) $transScanned->qty);
            if ($maxAllowed <= 0 || (int) $transScanned->scanned >= $maxAllowed) {
                return response()->json([
                    "status" => "close",
                    "count" => 0,
                    "message" => "Ticket already fully scanned"
                ]);
            }

            $cooldown = $this->ticketScanCooldownResponse($transScanned);
            if ($cooldown) {
                return $cooldown;
            }

            $counting = (int) $transScanned->scanned + 1;
            $payload = [
                "scanned" => $counting,
            ];

            if ($counting >= $maxAllowed) {
                $payload["status"] = "close";
                $payload["scanned_at"] = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
            }

            DetailTransaction::where('ticket_code', $request->ticket)
                ->update($payload);

            if ($this->shouldCloseInvoice($invoice)) {
                $invoice->status = "closed";
                $invoice->amount_scanned = $invoice->detail()->sum('scanned');
                $invoice->save();
            }

            return response()->json([
                "status" => $counting >= $maxAllowed ? "close" : "open",
                "count" => max(0, $maxAllowed - $counting),
                "message" => "OK"
            ]);
        } else {
            $now = now('Asia/Jakarta')->format('Y-m-d');

            $member = Member::where('rfid', $request->ticket)->orWhere('qr_code', $request->ticket)->first();
            $employe = User::where('uid', $request->ticket)->first();

            if ($member) {
                if ($now >= $member->tgl_register && $now <= $member->tgl_expired) {
                    $membership = Membership::find($member->membership_id);
                    if (!$membership) {
                        return response()->json([
                            "status" => "error",
                            "message" => "Member not subcribed"
                        ], 500);
                    }

                    $maxAccess = max((int) ($membership->max_access ?? 0), 0);
                    $accessUsed = max((int) ($member->access_used ?? 0), 0);
                    $isUnlimitedAccess = $maxAccess === 0;
                    if (!$isUnlimitedAccess && $accessUsed >= $maxAccess) {
                        return response()->json([
                            "status" => 'close',
                            "message" => "Kuota akses habis",
                            "count" => 0,
                            "membership_access" => [
                                "type" => "limited",
                                "limit" => $maxAccess,
                                "used" => $accessUsed,
                                "remaining" => 0,
                            ]
                        ], 500);
                    }

                    $gates = $membership->gates()->pluck('id')->toArray();

                    if (in_array($request->gate, $gates)) {
                        History::create([
                            'member_id' => $member->id,
                            'gate' => $request->gate,
                            'user_id' => 0,
                            'waktu' => now('Asia/Jakarta')->format('Y-m-d H:i:s')
                        ]);

                        $member->increment('access_used');
                        $accessUsed += 1;
                        $remainingAccess = $isUnlimitedAccess ? null : max($maxAccess - $accessUsed, 0);

                        return response()->json([
                            "status" => 'open',
                            "message" => "Success open gate",
                            "count" => $remainingAccess,
                            "membership_access" => [
                                "type" => $isUnlimitedAccess ? "unlimited" : "limited",
                                "limit" => $isUnlimitedAccess ? null : $maxAccess,
                                "used" => $accessUsed,
                                "remaining" => $remainingAccess,
                            ]
                        ], 200);
                    } else {
                        $remainingAccess = $isUnlimitedAccess ? null : max($maxAccess - $accessUsed, 0);
                        return response()->json([
                            "status" => 'close',
                            "message" => "Cannot access gate",
                            "count" => $remainingAccess,
                            "membership_access" => [
                                "type" => $isUnlimitedAccess ? "unlimited" : "limited",
                                "limit" => $isUnlimitedAccess ? null : $maxAccess,
                                "used" => $accessUsed,
                                "remaining" => $remainingAccess,
                            ]
                        ], 500);
                    }
                } else {
                    return response()->json([
                        "status" => 'close',
                        "message" => "Member expired"
                    ]);
                }
            } else if ($employe) {
                if ($employe->is_active == 0) {
                    return response()->json([
                        "status" => 'close',
                        "message" => "Karyawan sudah tidak aktif"
                    ]);
                }

                History::create([
                    'member_id' => 0,
                    'gate' => $request->gate,
                    'user_id' => $employe->id,
                    'waktu' => now('Asia/Jakarta')->format('Y-m-d H:i:s')
                ]);

                return response()->json([
                    "status" => 'open',
                    "message" => "Gate user"
                ]);
            } else {
                return response()->json([
                    "status" => 'close',
                    "message" => "Card tidak terdaftar"
                ]);
            }
        }
    }

    public function gateTerusan(Request $request)
    {
        $ticket = Transaction::where('ticket_code', $request->ticket)->first();

        if ($ticket) {
            if ($ticket->ticket->jenis_ticket_id == 2 && $this->isTicketWithinValidity($ticket->created_at)) {
                $maxAllowed = $this->resolveMaxScan((int) $ticket->amount);
                if ($maxAllowed <= 0 || (int) $ticket->amount_scanned >= $maxAllowed) {
                    return response()->json([
                        "status" => 'close',
                    ]);
                }

                $terusan = Terusan::where('tripod', $request->tripod)->first();

                if ($terusan) {
                    $nextCount = (int) $ticket->amount_scanned + 1;
                    $payload = [
                        'amount_scanned' => $nextCount,
                    ];

                    if ($nextCount >= $maxAllowed) {
                        $payload['status'] = 'closed';
                    }

                    $ticket->update($payload);

                    return response()->json([
                        "status" => 'open',
                    ]);
                } else {
                    return response()->json([
                        "status" => 'close',
                    ]);
                }
            } else {
                return response()->json([
                    "status" => 'close',
                ]);
            }
        } else {
            return response()->json([
                "status" => 'close',
            ]);
        }
    }

    private function isTicketWithinValidity(mixed $createdAt): bool
    {
        $validDays = (int) Setting::valueOf('ticket_valid_days', 1);
        if ($validDays <= 0) {
            return true;
        }

        if (empty($createdAt)) {
            return false;
        }

        $now = Carbon::now('Asia/Jakarta')->startOfDay();
        $created = Carbon::parse($createdAt)->timezone('Asia/Jakarta')->startOfDay();
        $diffDays = $created->diffInDays($now, false);

        return $diffDays >= 0 && $diffDays < $validDays;
    }

    private function resolveMaxScan(int $qty): int
    {
        $limit = (int) Setting::valueOf('ticket_scan_limit', 0);
        $qty = max($qty, 0);

        if ($limit <= 0) {
            return $qty;
        }

        if ($qty <= 0) {
            return $limit;
        }

        return $qty * $limit;
    }

    private function ticketScanCooldownResponse(DetailTransaction $ticket): ?\Illuminate\Http\JsonResponse
    {
        $cooldownSeconds = max((int) Setting::valueOf('ticket_scan_cooldown_seconds', 0), 0);
        if ($cooldownSeconds <= 0) {
            return null;
        }

        // Use the existing updated_at field as the last successful scan time.
        // Gate updates happen before this check, so refresh the model from DB
        // to get the timestamp from the previous successful scan.
        $lastUpdatedAt = DetailTransaction::where('id', $ticket->id)->value('updated_at');
        if (empty($lastUpdatedAt)) {
            return null;
        }

        $lastScannedAt = Carbon::parse($lastUpdatedAt)->timezone('Asia/Jakarta');
        $now = Carbon::now('Asia/Jakarta');
        $elapsedSeconds = $lastScannedAt->diffInSeconds($now);
        $remainingSeconds = $cooldownSeconds - $elapsedSeconds;

        if ($remainingSeconds <= 0) {
            return null;
        }

        return response()->json([
            "status" => "wait",
            "count" => max(0, (int) $ticket->scanned),
            "cooldown_seconds" => $cooldownSeconds,
            "remaining_seconds" => $remainingSeconds,
            "message" => "Ticket belum bisa discan lagi. Tunggu {$remainingSeconds} detik."
        ], 429);
    }
}
