<?php

namespace App\Exports;

use App\Models\Member;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MemberExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private string $filter;
    private int $membershipId;
    private string $statusFilter;

    public function __construct(string $filter = 'all', int $membershipId = 0, string $statusFilter = 'all')
    {
        $this->filter = $filter;
        $this->membershipId = $membershipId;
        $this->statusFilter = in_array($statusFilter, ['all', 'active', 'expired'], true) ? $statusFilter : 'all';
    }

    public function collection()
    {
        $query = Member::with('membership')->orderBy('nama', 'asc');

        if ($this->filter === 'member') {
            $query->where('parent_id', 0);
        } elseif ($this->filter === 'submember') {
            $query->where('parent_id', '!=', 0);
        }

        if ($this->membershipId > 0) {
            $query->where('membership_id', $this->membershipId);
        }

        $today = Carbon::now('Asia/Jakarta')->startOfDay();
        if ($this->statusFilter === 'active') {
            $query
                ->whereDate('tgl_expired', '>=', $today->toDateString())
                ->where('is_active', 1);
        } elseif ($this->statusFilter === 'expired') {
            $query->where(function ($builder) use ($today) {
                $builder
                    ->where('is_active', '!=', 1)
                    ->orWhereDate('tgl_expired', '<', $today->toDateString());
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Member Code',
            'Nama',
            'No HP',
            'No Identitas',
            'RFID',
            'Tipe',
            'Membership',
            'Tanggal Register',
            'Tanggal Expired',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->display_member_code ?? '-',
            $row->nama ?? '-',
            $row->no_hp ?? '-',
            $row->no_ktp ?? '-',
            $row->rfid ?? '-',
            ((int) $row->parent_id === 0) ? 'Member' : 'Submember',
            optional($row->membership)->name ?? '-',
            $row->tgl_register ? Carbon::parse($row->tgl_register)->format('d/m/Y') : '-',
            $row->tgl_expired ? Carbon::parse($row->tgl_expired)->format('d/m/Y') : '-',
            $this->toStatusLabel($row),
        ];
    }

    private function toStatusLabel(Member $row): string
    {
        $today = Carbon::now('Asia/Jakarta')->startOfDay();
        $expiredAt = Carbon::parse($row->tgl_expired)->startOfDay();
        $isActive = ((int) $row->is_active === 1) && $expiredAt->greaterThanOrEqualTo($today);

        return $isActive ? 'Active' : 'Expired';
    }
}
