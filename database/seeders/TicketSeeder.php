<?php

namespace Database\Seeders;

use App\Models\JenisTicket;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        JenisTicket::create([
            'nama_jenis' => 'Reguler'
        ]);

        JenisTicket::create([
            'nama_jenis' => 'Terusan'
        ]);

        $tickets = [
            ["name" =>  "HTM Reguler Weekday", "harga" => 20000, 'jenis_ticket_id' => 1],
            ["name" =>  "HTM Reguler Weekend", "harga" => 30000, 'jenis_ticket_id' => 1],
            ["name" =>  "HTM Terusan Weekday", "harga" => 40000, 'jenis_ticket_id' => 1],
            ["name" =>  "HTM Terusan Weekend", "harga" => 50000, 'jenis_ticket_id' => 1],
            // ["name" =>  "HTM Group Character Building", "harga" => 70000, 'jenis_ticket_id' => 1],
            // ["name" =>  "HTM Olahraga Sekolah", "harga" => 15000, 'jenis_ticket_id' => 1],
            // ["name" =>  "HTM Club Renang", "harga" => 15000, 'jenis_ticket_id' => 1],
            // ["name" =>  "HTM Rainbow Slider", "harga" => 35000, 'jenis_ticket_id' => 1],
            // ["name" =>  "HTM Kereta", "harga" => 25000, 'jenis_ticket_id' => 1],
            // ["name" =>  "HTM Adrenaline Slider Air Terjun", "harga" => 25000, 'jenis_ticket_id' => 1],
            // ["name" =>  "Parkir Mobil", "harga" => 5000, 'jenis_ticket_id' => 1],
            // ["name" =>  "Parkir Motor", "harga" => 2000, 'jenis_ticket_id' => 1],
            // ["name" =>  "Asuransi Jasa Raharja", "harga" => 2000, 'jenis_ticket_id' => 1],
        ];

        $no = 1;

        foreach ($tickets as $ticket) {
            Ticket::create([
                'name' => $ticket['name'],
                'harga' => $ticket['harga'],
                'jenis_ticket_id' => $ticket['jenis_ticket_id'],
                'tripod' => $no++
            ]);
        }
    }
}
