<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([
            ['Owner Bersih Laundry', 'owner', 'pemilik', env('OWNER_DEMO_PASSWORD')],
            ['Kasir Bersih Laundry', 'kasir', 'kasir', env('CASHIER_DEMO_PASSWORD')],
        ] as [$name, $username, $role, $password]) {
            if (! is_string($password) || strlen($password) < 12) {
                throw new \RuntimeException('Set OWNER_DEMO_PASSWORD and CASHIER_DEMO_PASSWORD, minimum 12 characters.');
            }
            if (! DB::table('users')->where('username', $username)->exists()) {
                DB::table('users')->insert([
                    'name' => $name,
                    'username' => $username,
                    'email' => $username.'@bersih.local',
                    'password' => Hash::make($password),
                    'role' => $role,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (DB::table('layanans')->count() === 0) {
            foreach ([
                ['Cuci Kering', 6000, 'kg'],
                ['Cuci Setrika', 8000, 'kg'],
                ['Setrika Saja', 5000, 'kg'],
                ['Cuci Sepatu', 20000, 'pasang'],
            ] as [$service, $price, $unit]) {
                DB::table('layanans')->insert([
                    'nama_layanan' => $service,
                    'harga' => $price,
                    'satuan' => $unit,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
