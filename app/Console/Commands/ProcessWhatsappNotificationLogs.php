<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\WhatsappNotificationLog;
use App\Services\KirimiWhatsappService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessWhatsappNotificationLogs extends Command
{
    protected $signature = 'wa:process-logs {--limit=20 : Maksimal jumlah log diproses per run} {--dry-run : Simulasi tanpa kirim ke provider}';

    protected $description = 'Proses pengiriman WhatsApp dari tabel whatsapp_notification_logs.';

    public function handle(KirimiWhatsappService $kirimi): int
    {
        $setting = Setting::asObject();
        if (!$setting || !(bool) $setting->whatsapp_enabled) {
            $this->info('WhatsApp nonaktif di setting. Proses dihentikan.');
            return Command::SUCCESS;
        }

        $timezone = 'Asia/Jakarta';
        $now = Carbon::now($timezone);
        $startHour = (int) config('services.kirimi.send_start_hour', 8);
        $endHour = (int) config('services.kirimi.send_end_hour', 20);
        $currentHour = (int) $now->format('G');

        if ($currentHour < $startHour || $currentHour >= $endHour) {
            $this->info("Di luar jam kirim ({$startHour}:00-{$endHour}:00). Proses dihentikan.");
            return Command::SUCCESS;
        }

        $limit = max((int) $this->option('limit'), 1);
        $logs = WhatsappNotificationLog::query()
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($logs->isEmpty()) {
            $this->info('Tidak ada log WA pending.');
            return Command::SUCCESS;
        }

        $delayMin = (int) config('services.kirimi.delay_min_seconds', 5);
        $delayMax = (int) config('services.kirimi.delay_max_seconds', 10);
        if ($delayMin < 0) {
            $delayMin = 0;
        }
        if ($delayMax < $delayMin) {
            $delayMax = $delayMin;
        }

        foreach ($logs as $index => $log) {
            if ($index > 0 && $delayMax > 0) {
                sleep(random_int($delayMin, $delayMax));
            }

            if ($this->option('dry-run')) {
                $this->line("[DRY-RUN] {$log->type} => {$log->recipient_phone}");
                continue;
            }

            $result = $kirimi->sendMessage($log->recipient_phone, $log->message);
            $log->retry_count = (int) $log->retry_count + 1;
            $log->provider_response = json_encode($result);

            if (!empty($result['ok'])) {
                $log->status = 'sent';
                $log->sent_at = now();
            } else {
                $log->status = 'failed';
            }

            $log->save();
        }

        $this->info('Proses log WA selesai.');
        return Command::SUCCESS;
    }
}
