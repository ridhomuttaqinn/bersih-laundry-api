<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LaundryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_and_login(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'nama' => 'Ayu Laundry',
            'alamat' => 'Jalan Melati 1',
            'no_telepon' => '081234567890',
            'email' => 'ayu@example.test',
            'password' => 'password123',
        ])->assertCreated()
            ->assertJsonPath('data.nama', 'Ayu Laundry');

        $this->assertDatabaseHas('pelanggans', [
            'email' => 'ayu@example.test',
            'nama_lengkap' => 'Ayu Laundry',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'ayu@example.test',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('data.role', 'pelanggan');
    }

    public function test_order_payment_notification_and_report_use_database_records(): void
    {
        $customerId = DB::table('pelanggans')->insertGetId([
            'nama_lengkap' => 'Budi Laundry',
            'alamat' => 'Jalan Kenanga 2',
            'nomor_telepon' => '081234567891',
            'email' => 'budi@example.test',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $serviceResponse = $this->postJson('/api/v1/layanan', [
            'nama_layanan' => 'Cuci Express',
            'harga_per_unit' => 8000,
            'satuan' => 'kg',
            'is_active' => true,
        ])->assertCreated();
        $serviceId = $serviceResponse->json('data.id_layanan');

        $orderResponse = $this->postJson('/api/v1/pesanan', [
            'id_pelanggan' => $customerId,
            'alamat_jemput' => 'Jalan Kenanga 2',
            'jadwal_jemput' => now()->addDay()->toDateTimeString(),
            'metode_bayar_pilihan' => 'tunai',
            'is_estimasi_berat' => false,
            'items' => [[
                'id_layanan' => $serviceId,
                'berat_qty' => 1.5,
            ]],
        ])->assertCreated();
        $orderId = $orderResponse->json('data.id_pesanan');

        $this->assertDatabaseHas('detail_pesanans', [
            'pesanan_id' => $orderId,
            'layanan_id' => $serviceId,
            'jumlah' => 1.5,
            'subtotal' => 12000,
        ]);

        $this->patchJson("/api/v1/pesanan/{$orderId}/status", [
            'status_pesanan' => 'diproses',
        ])->assertOk();
        $this->postJson("/api/v1/pesanan/{$orderId}/pembayaran", [
            'metode_bayar' => 'tunai',
            'jumlah_bayar' => 12000,
        ])->assertCreated();
        $this->postJson('/api/v1/notifikasi', [
            'id_pelanggan' => $customerId,
            'id_pesanan' => $orderId,
            'pesan' => 'Pesanan sedang diproses',
        ])->assertCreated();

        $this->assertDatabaseHas('pesanans', [
            'id' => $orderId,
            'status' => 'diproses',
            'total_harga' => 12000,
        ]);
        $this->assertDatabaseHas('pembayarans', [
            'pesanan_id' => $orderId,
            'jumlah_bayar' => 12000,
        ]);
        $this->assertDatabaseHas('notifikasis', [
            'pesanan_id' => $orderId,
            'dibaca' => false,
        ]);

        $this->getJson('/api/v1/laporan/total-pendapatan?range_start='.urlencode(now()->subDay()->toDateTimeString()).'&range_end='.urlencode(now()->addDay()->toDateTimeString()))
            ->assertOk()
            ->assertJsonPath('data', 12000);
    }
}
