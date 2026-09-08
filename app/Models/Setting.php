<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    protected $table = 'settings';

    protected $fillable = ['key', 'value'];

    private const DEFAULTS = [
        'name' => null,
        'logo' => null,
        'ucapan' => null,
        'deskripsi' => null,
        'ppn' => 0,
        'member_suspend_before_days' => 7,
        'member_suspend_after_days' => 0,
        'ticket_code_mode' => 'unique',
        'ticket_print_orientation' => 'without_summary',
        'ticket_valid_days' => 1,
        'ticket_scan_limit' => 0,
        'dashboard_metric_mode' => 'amount',
        'whatsapp_enabled' => 0,
        'use_logo' => 0,
        'website_status' => 1,
        'renewal_notice_club_name' => 'MIU',
        'renewal_notice_bank_account' => 'TRANSFER BANK: BCA 0289011155 A/N PT KARTUNINDO PERKASA ABADI',
        'renewal_notice_admin_phone' => '0821 2222 9358',
        'renewal_notice_body_template' => "Yth. Bapak/Ibu :member_name,\n\nKami informasikan bahwa masa aktif membership Anda akan berakhir pada :expired_date.\n\nAgar tetap dapat menikmati seluruh fasilitas, mohon melakukan perpanjangan dengan rincian:\n\nTipe Member: :membership_name\nBiaya: :total_price\nJatuh tempo: :due_date\n:note_block\nSilakan melakukan pembayaran sebelum jatuh tempo agar membership tetap aktif.\n\n:bank_account\n\nJika sudah melakukan pembayaran, harap informasi dan kirim bukti pembayaran ke nomor Admin.\nTerima kasih.\n\nAdmin\n:club_name\nNo.Hp: :admin_phone",
        'renewal_notice_greeting_template' => 'Yth. Bapak/Ibu :member_name,',
        'renewal_notice_intro_template' => 'Kami informasikan bahwa masa aktif membership Anda akan berakhir pada :expired_date.',
        'renewal_notice_detail_intro_template' => 'Agar tetap dapat menikmati seluruh fasilitas, mohon melakukan perpanjangan dengan rincian:',
        'renewal_notice_member_type_label' => 'Tipe Member',
        'renewal_notice_fee_label' => 'Biaya',
        'renewal_notice_due_date_label' => 'Jatuh tempo',
        'renewal_notice_note_label' => 'Catatan',
        'renewal_notice_note_template' => 'Perpanjangan baru (termasuk biaya admin :admin_fee)',
        'renewal_notice_payment_reminder_template' => 'Silakan melakukan pembayaran sebelum jatuh tempo agar membership tetap aktif.',
        'renewal_notice_proof_payment_template' => 'Jika sudah melakukan pembayaran, harap informasi dan kirim bukti pembayaran ke nomor Admin.',
        'renewal_notice_closing_template' => 'Terima kasih.',
        'renewal_notice_admin_label' => 'Admin',
        'renewal_notice_admin_phone_label' => 'No.Hp',
    ];

    public static function asObject(): object
    {
        return (object) static::allAsArray();
    }

    public static function allAsArray(): array
    {
        $pairs = static::query()->pluck('value', 'key')->toArray();
        $merged = array_merge(self::DEFAULTS, $pairs);

        $merged['ppn'] = (int) $merged['ppn'];
        $merged['member_suspend_before_days'] = max((int) $merged['member_suspend_before_days'], 1);
        $merged['member_suspend_after_days'] = max((int) $merged['member_suspend_after_days'], 0);
        $merged['ticket_valid_days'] = max((int) $merged['ticket_valid_days'], 0);
        $merged['ticket_scan_limit'] = max((int) $merged['ticket_scan_limit'], 0);
        $merged['whatsapp_enabled'] = (int) $merged['whatsapp_enabled'];
        $merged['use_logo'] = (int) $merged['use_logo'];
        $merged['website_status'] = (int) $merged['website_status'];

        return $merged;
    }

    public static function valueOf(string $key, mixed $default = null): mixed
    {
        $settings = static::allAsArray();
        return $settings[$key] ?? $default;
    }

    public static function putMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            static::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? (int) $value : (string) $value]
            );
        }
    }
}

