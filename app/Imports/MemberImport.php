<?php

namespace App\Imports;

use App\Models\Member;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Str;

class MemberImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Member([
            'nama'     => $row['nama'],
            'rfid'     => $row['rfid'],
            'no_ktp'    => $row['no_identitas'],
            'no_hp'    => $row['no_hp'],
            'tgl_lahir'    => $this->transformDate($row['tanggal_lahir']),
            'jenis_kelamin'    => $row['jenis_kelamin'],
            'alamat'    => $row['alamat'],
            'tgl_register' => now(),
            'tgl_expired' => now(),
            'qr_code' => "MBR" . strtoupper(Str::random(13))
        ]);
    }

    private function transformDate($value)
    {
        if (empty($value)) {
            return null;
        }

        // 4. JIKA nilainya angka (Excel Serial Date seperti 36526)
        if (is_numeric($value)) {
            // Konversi dari Excel serial date
            return Date::excelToDateTimeObject($value);
        }

        // 5. JIKA nilainya sudah string (misal: '25/12/2023' atau '2023-12-25')
        // Coba parse dengan Carbon
        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            // Jika format string tidak dikenali, kembalikan null
            return null;
        }
    }
}
