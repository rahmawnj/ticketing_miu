<?php

namespace App\Console\Commands;

use App\Models\History;
use App\Models\HistoryMembership;
use App\Models\Member;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurgeExpiredMembers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'members:purge-expired {--dry-run : Tampilkan jumlah tanpa menghapus}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus member yang sudah melewati masa tenggang expired (sekali sehari).';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::now('Asia/Jakarta')->startOfDay();
        $setting = Setting::asObject();
        $graceDays = max((int) ($setting->member_suspend_after_days ?? 0), 0);
        $cutoffDate = $today->copy()->subDays($graceDays)->toDateString();

        $blockedIds = array_values(array_unique(array_merge(
            DB::table('topups')->pluck('member_id')->filter()->map(fn ($id) => (int) $id)->all(),
            DB::table('history_penyewaans')->pluck('member_id')->filter()->map(fn ($id) => (int) $id)->all()
        )));

        $eligibleParentIds = Member::query()
            ->where('parent_id', 0)
            ->whereDate('tgl_expired', '<', $cutoffDate)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $idsToDelete = [];
        $familyCandidateCount = 0;
        $skippedFamilies = 0;
        $skippedMembers = 0;

        foreach ($eligibleParentIds as $parentId) {
            $familyIds = Member::query()
                ->where('id', $parentId)
                ->orWhere('parent_id', $parentId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $familyCandidateCount += count($familyIds);

            if (!empty(array_intersect($familyIds, $blockedIds))) {
                $skippedFamilies++;
                $skippedMembers += count($familyIds);
                continue;
            }

            $idsToDelete = array_merge($idsToDelete, $familyIds);
        }

        $ids = collect(array_values(array_unique($idsToDelete)));
        $deletableCount = $ids->count();

        if ($this->option('dry-run')) {
            $this->info("Dry-run: {$deletableCount} member akan dihapus.");
            $this->line("Grace days: {$graceDays} hari, cutoff expired < {$cutoffDate}.");
            $this->line("Parent eligible: " . count($eligibleParentIds) . ", kandidat family member: {$familyCandidateCount}.");
            if ($skippedFamilies > 0) {
                $this->warn("{$skippedFamilies} family ({$skippedMembers} member) dilewati karena masih dipakai tabel lain.");
            }
            return Command::SUCCESS;
        }

        if ($deletableCount === 0) {
            $this->info("Tidak ada member expired untuk dihapus.");
            if ($skippedFamilies > 0) {
                $this->warn("{$skippedFamilies} family ({$skippedMembers} member) dilewati karena masih dipakai tabel lain.");
            }
            return Command::SUCCESS;
        }

        DB::transaction(function () use ($ids, $deletableCount, $graceDays, $cutoffDate, $skippedFamilies, $skippedMembers, $eligibleParentIds, $familyCandidateCount) {
            HistoryMembership::whereIn('member_id', $ids)->delete();
            History::whereIn('member_id', $ids)->delete();
            Member::whereIn('id', $ids)->delete();

            Log::info('Purge expired members', [
                'date' => now('Asia/Jakarta')->toDateString(),
                'grace_days' => $graceDays,
                'cutoff_expired_date' => $cutoffDate,
                'eligible_parent_count' => count($eligibleParentIds),
                'family_candidate_member_count' => $familyCandidateCount,
                'deleted_count' => $deletableCount,
                'skipped_family_count' => $skippedFamilies,
                'skipped_member_count' => $skippedMembers,
            ]);
        });

        $this->info("Berhasil menghapus {$deletableCount} member expired.");
        if ($skippedFamilies > 0) {
            $this->warn("{$skippedFamilies} family ({$skippedMembers} member) dilewati karena masih dipakai tabel lain.");
        }

        return Command::SUCCESS;
    }
}
