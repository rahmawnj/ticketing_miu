<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\Setting;
use App\Models\WhatsappNotificationLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyRenewalH1Members extends Command
{
    protected $signature = 'members:notify-renewal-h1 {--dry-run : Tampilkan kandidat tanpa membuat log WA}';

    protected $description = 'Buat log notifikasi pengingat renewal berdasarkan setting reminder hari sebelum expired.';

    public function handle(): int
    {
        $timezone = 'Asia/Jakarta';
        $today = Carbon::now($timezone)->startOfDay();
        $setting = Setting::asObject();

        if (!$setting || !(bool) $setting->whatsapp_enabled) {
            $this->info('WhatsApp nonaktif di setting. Tidak membuat log pengingat.');
            return Command::SUCCESS;
        }

        $reminderDays = max((int) ($setting->member_suspend_before_days ?? 7), 1);
        $limitDate = $today->copy()->addDays($reminderDays);

        $members = Member::query()
            ->where('parent_id', 0)
            ->whereDate('tgl_expired', '>=', $today->toDateString())
            ->whereDate('tgl_expired', '<=', $limitDate->toDateString())
            ->get(['id', 'nama', 'no_hp', 'tgl_expired', 'membership_id']);

        $count = $members->count();

        if ($this->option('dry-run')) {
            $this->info("Dry-run: {$count} member kandidat pengingat renewal ({$today->toDateString()} s/d {$limitDate->toDateString()}).");
            return Command::SUCCESS;
        }

        if ($count === 0) {
            $this->info('Tidak ada member kandidat pengingat renewal.');
            return Command::SUCCESS;
        }

        $foNumbers = $this->parseFoNumbers((string) env('WA_FO_NUMBERS', ''));
        $createdLogs = 0;

        foreach ($members as $member) {
            $memberPhone = $this->normalizePhone($member->no_hp);
            $daysLeft = max($today->diffInDays(Carbon::parse($member->tgl_expired, $timezone), false), 0);
            $expiredDate = Carbon::parse($member->tgl_expired, $timezone)->format('d/m/Y');
            $memberName = trim((string) $member->nama) !== '' ? $member->nama : 'Member';

            $memberMessage = "Halo {$memberName}, masa berlaku membership Anda akan berakhir pada {$expiredDate} ({$daysLeft} hari lagi). Silakan lakukan renewal sebelum jatuh tempo.";
            if ($this->createReminderLog($member->id, $memberPhone, $memberMessage)) {
                $createdLogs++;
            }

            $foMessage = "Reminder renewal: {$memberName} ({$memberPhone}) akan expired pada {$expiredDate} ({$daysLeft} hari lagi).";
            foreach ($foNumbers as $foPhone) {
                if ($this->createReminderLog($member->id, $foPhone, $foMessage)) {
                    $createdLogs++;
                }
            }
        }

        $this->info("Pengingat renewal diproses untuk {$count} member. Log WA baru: {$createdLogs}.");
        return Command::SUCCESS;
    }

    private function createReminderLog(int $memberId, ?string $phone, string $message): bool
    {
        if (!$phone) {
            return false;
        }

        $alreadyExists = WhatsappNotificationLog::query()
            ->where('type', 'renewal_reminder')
            ->where('member_id', $memberId)
            ->where('recipient_phone', $phone)
            ->whereDate('created_at', now('Asia/Jakarta')->toDateString())
            ->exists();

        if ($alreadyExists) {
            return false;
        }

        WhatsappNotificationLog::create([
            'type' => 'renewal_reminder',
            'member_id' => $memberId,
            'transaction_id' => null,
            'recipient_phone' => $phone,
            'message' => $message,
            'status' => 'pending',
            'retry_count' => 0,
        ]);

        return true;
    }

    private function parseFoNumbers(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $numbers = [];
        foreach (explode(',', $raw) as $item) {
            $phone = $this->normalizePhone($item);
            if ($phone) {
                $numbers[] = $phone;
            }
        }

        return array_values(array_unique($numbers));
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', (string) $phone);
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
