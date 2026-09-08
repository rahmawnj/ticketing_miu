<?php

namespace Database\Seeders;

use App\Models\Sewa;
use Illuminate\Database\Seeder;

class SewaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tickets = [
            ["name" =>  "Sewa Ban", "harga" => 10000],
            ["name" =>  "Sewa Baju Renang", "harga" => 10000],
            ["name" =>  "Sewa Pelampung", "harga" => 10000],
            ["name" =>  "Sewa Tikar", "harga" => 10000],
            // ["name" =>  "Tiket Balon Udara", "harga" => 10000, 'jenis_ticket_id' => 1],
            // ["name" =>  "Tiket Adrenaline Swing", "harga" => 10000, 'jenis_ticket_id' => 1],
            // ["name" =>  "Tiket Sepeda Terbang", "harga" => 10000, 'jenis_ticket_id' => 1],
        ];

        $no = 1;

        foreach ($tickets as $ticket) {
            Sewa::create([
                'name' => $ticket['name'],
                'harga' => $ticket['harga'],
                'device' => $no++
            ]);
        }
    }
}
