<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class LaundryApiController extends Controller
{
    public function health(): JsonResponse
    {
        DB::connection()->getPdo();

        return $this->respond(['database' => 'connected']);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['required', 'string'],
            'no_telepon' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255', Rule::unique('pelanggans', 'email')],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $id = DB::table('pelanggans')->insertGetId([
            'nama_lengkap' => $data['nama'],
            'alamat' => $data['alamat'],
            'nomor_telepon' => $data['no_telepon'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->respond($this->customerData($id), 'Registrasi berhasil', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
        $identifier = trim($credentials['identifier']);

        $customer = DB::table('pelanggans')->where('email', strtolower($identifier))->first();
        if ($customer && Hash::check($credentials['password'], $customer->password)) {
            return $this->respond([
                'id' => (int) $customer->id,
                'nama' => $customer->nama_lengkap,
                'role' => 'pelanggan',
                'pelanggan' => $this->customerMap($customer),
                'user' => null,
            ]);
        }

        $user = DB::table('users')
            ->where('username', $identifier)
            ->orWhere('email', strtolower($identifier))
            ->first();

        if ($user && (bool) $user->is_active && Hash::check($credentials['password'], $user->password)) {
            return $this->respond([
                'id' => (int) $user->id,
                'nama' => $user->name,
                'role' => $user->role,
                'pelanggan' => null,
                'user' => $this->userMap($user),
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Email, username, atau kata sandi tidak sesuai'], 401);
    }

    public function services(Request $request): JsonResponse
    {
        $query = DB::table('layanans')->orderBy('nama_layanan');
        if ($request->boolean('only_active')) {
            $query->where('is_active', true);
        }

        return $this->respond($query->get()->map(fn (object $service): array => $this->serviceMap($service))->all());
    }

    public function service(int $id): JsonResponse
    {
        return $this->respond($this->serviceData($id));
    }

    public function createService(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama_layanan' => ['required', 'string', 'max:255'],
            'harga_per_unit' => ['required', 'numeric', 'min:0'],
            'satuan' => ['required', 'string', 'max:40'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $id = DB::table('layanans')->insertGetId([
            'nama_layanan' => $data['nama_layanan'],
            'harga' => $data['harga_per_unit'],
            'satuan' => $data['satuan'],
            'is_active' => $data['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->respond($this->serviceData($id), 'Layanan berhasil ditambahkan', 201);
    }

    public function updateService(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'nama_layanan' => ['required', 'string', 'max:255'],
            'harga_per_unit' => ['required', 'numeric', 'min:0'],
            'satuan' => ['required', 'string', 'max:40'],
            'is_active' => ['required', 'boolean'],
        ]);
        abort_unless(DB::table('layanans')->where('id', $id)->exists(), 404, 'Layanan tidak ditemukan');

        DB::table('layanans')->where('id', $id)->update([
            'nama_layanan' => $data['nama_layanan'],
            'harga' => $data['harga_per_unit'],
            'satuan' => $data['satuan'],
            'is_active' => $data['is_active'],
            'updated_at' => now(),
        ]);

        return $this->respond($this->serviceData($id), 'Layanan berhasil diperbarui');
    }

    public function deleteService(int $id): JsonResponse
    {
        abort_unless(DB::table('layanans')->where('id', $id)->exists(), 404, 'Layanan tidak ditemukan');
        if (DB::table('detail_pesanans')->where('layanan_id', $id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Layanan yang sudah digunakan dalam pesanan tidak dapat dihapus'], 409);
        }
        DB::table('layanans')->where('id', $id)->delete();

        return $this->respond(null, 'Layanan berhasil dihapus');
    }

    public function customers(Request $request): JsonResponse
    {
        $query = DB::table('pelanggans')->orderBy('nama_lengkap');
        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->query('search')).'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('nama_lengkap', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('nomor_telepon', 'like', $search);
            });
        }

        return $this->respond($query->get()->map(fn (object $customer): array => $this->customerMap($customer))->all());
    }

    public function customer(int $id): JsonResponse
    {
        return $this->respond($this->customerData($id));
    }

    public function updateCustomer(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['required', 'string'],
            'no_telepon' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255', Rule::unique('pelanggans', 'email')->ignore($id)],
        ]);
        abort_unless(DB::table('pelanggans')->where('id', $id)->exists(), 404, 'Pelanggan tidak ditemukan');

        DB::table('pelanggans')->where('id', $id)->update([
            'nama_lengkap' => $data['nama'],
            'alamat' => $data['alamat'],
            'nomor_telepon' => $data['no_telepon'],
            'email' => strtolower($data['email']),
            'updated_at' => now(),
        ]);

        return $this->respond($this->customerData($id), 'Data pelanggan berhasil diperbarui');
    }

    public function deleteCustomer(int $id): JsonResponse
    {
        abort_unless(DB::table('pelanggans')->where('id', $id)->exists(), 404, 'Pelanggan tidak ditemukan');
        if (DB::table('pesanans')->where('pelanggan_id', $id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Pelanggan yang memiliki riwayat pesanan tidak dapat dihapus'], 409);
        }
        DB::table('pelanggans')->where('id', $id)->delete();

        return $this->respond(null, 'Pelanggan berhasil dihapus');
    }

    public function users(): JsonResponse
    {
        $users = DB::table('users')->whereNotNull('username')->orderBy('name')->get();

        return $this->respond($users->map(fn (object $user): array => $this->userMap($user))->all());
    }

    public function user(int $id): JsonResponse
    {
        $user = DB::table('users')->where('id', $id)->first();
        abort_unless($user, 404, 'Pengguna tidak ditemukan');

        return $this->respond($this->userMap($user));
    }

    public function createUser(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash', 'max:80', Rule::unique('users', 'username')],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(['kasir', 'pemilik'])],
        ]);
        $id = DB::table('users')->insertGetId([
            'name' => $data['nama'],
            'username' => $data['username'],
            'email' => strtolower($data['username']).'@bersih.local',
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->respond($this->userMap(DB::table('users')->where('id', $id)->first()), 'Akun berhasil dibuat', 201);
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'role' => ['sometimes', Rule::in(['kasir', 'pemilik'])],
        ]);
        abort_unless(DB::table('users')->where('id', $id)->whereNotNull('username')->exists(), 404, 'Pengguna tidak ditemukan');

        if ($data !== []) {
            DB::table('users')->where('id', $id)->update([...$data, 'updated_at' => now()]);
        }

        return $this->respond($this->userMap(DB::table('users')->where('id', $id)->first()), 'Akun berhasil diperbarui');
    }

    public function deleteUser(int $id): JsonResponse
    {
        abort_unless(DB::table('users')->where('id', $id)->whereNotNull('username')->exists(), 404, 'Pengguna tidak ditemukan');
        DB::table('pesanans')->where('user_id', $id)->update(['user_id' => null]);
        DB::table('users')->where('id', $id)->delete();

        return $this->respond(null, 'Akun berhasil dihapus');
    }

    public function orders(Request $request): JsonResponse
    {
        $query = DB::table('pesanans as p')
            ->leftJoin('pelanggans as pl', 'pl.id', '=', 'p.pelanggan_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->select('p.*', 'pl.nama_lengkap as nama_pelanggan', 'u.name as nama_kasir')
            ->orderByDesc('p.created_at');
        $customerId = $request->query('id_pelanggan', $request->query('pelanggan_id'));
        if ($customerId !== null) {
            $query->where('p.pelanggan_id', (int) $customerId);
        }
        if ($request->filled('status')) {
            $query->where('p.status', $request->query('status'));
        }

        return $this->respond($query->get()->map(fn (object $order): array => $this->orderMap($order))->all());
    }

    public function order(int $id): JsonResponse
    {
        return $this->respond($this->orderData($id));
    }

    public function createOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_pelanggan' => ['required', 'integer', 'exists:pelanggans,id'],
            'alamat_jemput' => ['required', 'string'],
            'jadwal_jemput' => ['required', 'date'],
            'metode_bayar_pilihan' => ['nullable', Rule::in(['tunai', 'transfer', 'e-wallet'])],
            'is_estimasi_berat' => ['sometimes', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_layanan' => ['required', 'integer', 'exists:layanans,id'],
            'items.*.berat_qty' => ['required', 'numeric', 'min:0.01'],
        ]);

        $id = DB::transaction(function () use ($data): int {
            $now = now();
            $orderId = DB::table('pesanans')->insertGetId([
                'pelanggan_id' => $data['id_pelanggan'],
                'user_id' => null,
                'alamat_penjemputan' => $data['alamat_jemput'],
                'jadwal_penjemputan' => Carbon::parse($data['jadwal_jemput'])->format('Y-m-d H:i:s'),
                'status' => 'diterima',
                'catatan_penolakan' => null,
                'total_harga' => 0,
                'metode_bayar_pilihan' => $data['metode_bayar_pilihan'] ?? null,
                'berat_dikonfirmasi' => ! ($data['is_estimasi_berat'] ?? false),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $total = 0.0;

            foreach ($data['items'] as $item) {
                $service = DB::table('layanans')->where('id', $item['id_layanan'])->first();
                abort_unless($service && (bool) $service->is_active, 422, 'Layanan tidak aktif');
                $subtotal = (float) $service->harga * (float) $item['berat_qty'];
                $total += $subtotal;
                DB::table('detail_pesanans')->insert([
                    'pesanan_id' => $orderId,
                    'layanan_id' => $service->id,
                    'jumlah' => $item['berat_qty'],
                    'subtotal' => $subtotal,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('pesanans')->where('id', $orderId)->update(['total_harga' => $total]);

            return $orderId;
        });

        return $this->respond($this->orderData($id), 'Pesanan berhasil dibuat', 201);
    }

    public function updateOrderStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status_pesanan' => ['required', Rule::in(['diterima', 'diproses', 'selesai', 'diambil', 'ditolak'])],
            'id_user' => ['nullable', 'integer', 'exists:users,id'],
            'catatan_penolakan' => ['nullable', 'string'],
        ]);
        abort_unless(DB::table('pesanans')->where('id', $id)->exists(), 404, 'Pesanan tidak ditemukan');
        $values = ['status' => $data['status_pesanan'], 'updated_at' => now()];
        if (array_key_exists('id_user', $data)) {
            $values['user_id'] = $data['id_user'];
        }
        if (array_key_exists('catatan_penolakan', $data)) {
            $values['catatan_penolakan'] = $data['catatan_penolakan'];
        }
        if ($data['status_pesanan'] === 'selesai') {
            $values['tanggal_selesai'] = now();
        }
        DB::table('pesanans')->where('id', $id)->update($values);

        return $this->respond($this->orderData($id), 'Status pesanan berhasil diperbarui');
    }

    public function confirmOrderWeight(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'berat_aktual_per_detail' => ['required', 'array', 'min:1'],
            'berat_aktual_per_detail.*' => ['required', 'numeric', 'min:0.01'],
        ]);
        abort_unless(DB::table('pesanans')->where('id', $id)->exists(), 404, 'Pesanan tidak ditemukan');

        DB::transaction(function () use ($data, $id): void {
            foreach ($data['berat_aktual_per_detail'] as $detailId => $weight) {
                $detail = DB::table('detail_pesanans as d')
                    ->join('layanans as l', 'l.id', '=', 'd.layanan_id')
                    ->where('d.id', (int) $detailId)
                    ->where('d.pesanan_id', $id)
                    ->select('d.id', 'l.harga')
                    ->first();
                abort_unless($detail, 422, 'Rincian tidak sesuai dengan pesanan');
                DB::table('detail_pesanans')->where('id', $detail->id)->update([
                    'jumlah' => $weight,
                    'subtotal' => (float) $detail->harga * (float) $weight,
                    'updated_at' => now(),
                ]);
            }
            $total = DB::table('detail_pesanans')->where('pesanan_id', $id)->sum('subtotal');
            DB::table('pesanans')->where('id', $id)->update([
                'total_harga' => $total,
                'berat_dikonfirmasi' => true,
                'updated_at' => now(),
            ]);
        });

        return $this->respond($this->orderData($id), 'Berat pesanan berhasil dikonfirmasi');
    }

    public function recordPayment(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'metode_bayar' => ['required', Rule::in(['tunai', 'transfer', 'e-wallet'])],
            'jumlah_bayar' => ['required', 'numeric', 'min:0'],
        ]);
        abort_unless(DB::table('pesanans')->where('id', $id)->exists(), 404, 'Pesanan tidak ditemukan');
        if (DB::table('pembayarans')->where('pesanan_id', $id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Pesanan ini sudah memiliki data pembayaran'], 409);
        }
        $paymentId = DB::table('pembayarans')->insertGetId([
            'pesanan_id' => $id,
            'tanggal_bayar' => now(),
            'metode_bayar' => $data['metode_bayar'],
            'jumlah_bayar' => $data['jumlah_bayar'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->respond($this->paymentMap(DB::table('pembayarans')->where('id', $paymentId)->first()), 'Pembayaran berhasil dicatat', 201);
    }

    public function payment(int $id): JsonResponse
    {
        $payment = DB::table('pembayarans')->where('pesanan_id', $id)->first();

        return $this->respond($payment ? $this->paymentMap($payment) : null);
    }

    public function notifications(Request $request): JsonResponse
    {
        $customerId = (int) $request->query('id_pelanggan');
        $items = DB::table('notifikasis')->where('pelanggan_id', $customerId)->orderByDesc('created_at')->get();

        return $this->respond($items->map(fn (object $item): array => $this->notificationMap($item))->all());
    }

    public function unreadNotificationCount(Request $request): JsonResponse
    {
        $data = $request->validate(['id_pelanggan' => ['required', 'integer', 'exists:pelanggans,id']]);
        $count = DB::table('notifikasis')->where('pelanggan_id', $data['id_pelanggan'])->where('dibaca', false)->count();

        return $this->respond($count);
    }

    public function createNotification(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_pelanggan' => ['required', 'integer', 'exists:pelanggans,id'],
            'id_pesanan' => ['required', 'integer', 'exists:pesanans,id'],
            'pesan' => ['required', 'string'],
        ]);
        $id = DB::table('notifikasis')->insertGetId([
            'pelanggan_id' => $data['id_pelanggan'],
            'pesanan_id' => $data['id_pesanan'],
            'pesan' => $data['pesan'],
            'dibaca' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->respond($this->notificationMap(DB::table('notifikasis')->where('id', $id)->first()), 'Notifikasi berhasil disimpan', 201);
    }

    public function markNotificationRead(int $id): JsonResponse
    {
        abort_unless(DB::table('notifikasis')->where('id', $id)->exists(), 404, 'Notifikasi tidak ditemukan');
        DB::table('notifikasis')->where('id', $id)->update(['dibaca' => true, 'updated_at' => now()]);

        return $this->respond(null, 'Notifikasi ditandai sudah dibaca');
    }

    public function totalIncome(Request $request): JsonResponse
    {
        [$start, $end] = $this->reportRange($request);
        $total = DB::table('pembayarans')->where('tanggal_bayar', '>=', $start)->where('tanggal_bayar', '<', $end)->sum('jumlah_bayar');

        return $this->respond((float) $total);
    }

    public function orderCount(Request $request): JsonResponse
    {
        [$start, $end] = $this->reportRange($request);
        $count = DB::table('pesanans')->where('created_at', '>=', $start)->where('created_at', '<', $end)->where('status', '!=', 'ditolak')->count();

        return $this->respond($count);
    }

    public function reportSummary(Request $request): JsonResponse
    {
        [$start, $end] = $this->reportRange($request);
        $data = $request->validate(['periode' => ['required', Rule::in(['harian', 'mingguan', 'bulanan'])]]);
        $payments = DB::table('pembayarans')
            ->where('tanggal_bayar', '>=', $start)
            ->where('tanggal_bayar', '<', $end)
            ->orderBy('tanggal_bayar')
            ->get();
        $groups = [];

        foreach ($payments as $payment) {
            $date = Carbon::parse($payment->tanggal_bayar);
            $key = match ($data['periode']) {
                'harian' => $date->format('Y-m-d'),
                'mingguan' => $date->format('o-W'),
                'bulanan' => $date->format('Y-m'),
            };
            if (! isset($groups[$key])) {
                $groups[$key] = ['date' => $date, 'total' => 0.0, 'orders' => []];
            }
            $groups[$key]['total'] += (float) $payment->jumlah_bayar;
            $groups[$key]['orders'][$payment->pesanan_id] = true;
        }

        $summary = [];
        foreach ($groups as $group) {
            $date = $group['date'];
            $label = match ($data['periode']) {
                'harian' => $date->format('d/m/Y'),
                'mingguan' => 'Minggu '.$date->isoWeek.', '.$date->year,
                'bulanan' => $this->monthName($date->month),
            };
            $summary[] = [
                'label' => $label,
                'periode_awal' => $date->format('Y-m-d H:i:s'),
                'total_pendapatan' => $group['total'],
                'jumlah_pesanan' => count($group['orders']),
            ];
        }

        return $this->respond($summary);
    }

    private function respond(mixed $data, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    private function serviceData(int $id): array
    {
        $service = DB::table('layanans')->where('id', $id)->first();
        abort_unless($service, 404, 'Layanan tidak ditemukan');

        return $this->serviceMap($service);
    }

    private function serviceMap(object $service): array
    {
        return [
            'id_layanan' => (int) $service->id,
            'nama_layanan' => $service->nama_layanan,
            'harga_per_unit' => (float) $service->harga,
            'satuan' => $service->satuan,
            'is_active' => (int) $service->is_active,
        ];
    }

    private function customerData(int $id): array
    {
        $customer = DB::table('pelanggans')->where('id', $id)->first();
        abort_unless($customer, 404, 'Pelanggan tidak ditemukan');

        return $this->customerMap($customer);
    }

    private function customerMap(object $customer): array
    {
        return [
            'id_pelanggan' => (int) $customer->id,
            'nama' => $customer->nama_lengkap,
            'alamat' => $customer->alamat,
            'no_telepon' => $customer->nomor_telepon,
            'email' => $customer->email,
            'password' => '',
            'created_at' => $this->dateString($customer->created_at),
        ];
    }

    private function userMap(object $user): array
    {
        return [
            'id_user' => (int) $user->id,
            'nama' => $user->name,
            'username' => $user->username,
            'role' => $user->role,
            'is_active' => (int) $user->is_active,
            'password' => '',
            'created_at' => $this->dateString($user->created_at),
        ];
    }

    private function orderData(int $id): array
    {
        $order = DB::table('pesanans as p')
            ->leftJoin('pelanggans as pl', 'pl.id', '=', 'p.pelanggan_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->where('p.id', $id)
            ->select('p.*', 'pl.nama_lengkap as nama_pelanggan', 'u.name as nama_kasir')
            ->first();
        abort_unless($order, 404, 'Pesanan tidak ditemukan');

        return $this->orderMap($order);
    }

    private function orderMap(object $order): array
    {
        $items = DB::table('detail_pesanans as d')
            ->join('layanans as l', 'l.id', '=', 'd.layanan_id')
            ->where('d.pesanan_id', $order->id)
            ->select('d.id', 'd.pesanan_id', 'd.layanan_id', 'd.jumlah', 'd.subtotal', 'l.nama_layanan', 'l.harga', 'l.satuan')
            ->get()
            ->map(fn (object $item): array => [
                'id_detail' => (int) $item->id,
                'id_pesanan' => (int) $item->pesanan_id,
                'id_layanan' => (int) $item->layanan_id,
                'berat_qty' => (float) $item->jumlah,
                'subtotal' => (float) $item->subtotal,
                'nama_layanan' => $item->nama_layanan,
                'harga_per_unit' => (float) $item->harga,
                'satuan' => $item->satuan,
            ])->all();

        return [
            'id_pesanan' => (int) $order->id,
            'id_pelanggan' => (int) $order->pelanggan_id,
            'nama_pelanggan' => $order->nama_pelanggan,
            'id_user' => $order->user_id === null ? null : (int) $order->user_id,
            'nama_kasir' => $order->nama_kasir,
            'tanggal_masuk' => $this->dateString($order->created_at),
            'tanggal_selesai' => $this->dateString($order->tanggal_selesai),
            'alamat_jemput' => $order->alamat_penjemputan,
            'jadwal_jemput' => $this->dateString($order->jadwal_penjemputan),
            'status_pesanan' => $order->status,
            'catatan_penolakan' => $order->catatan_penolakan,
            'total_bayar' => (float) $order->total_harga,
            'metode_bayar_pilihan' => $order->metode_bayar_pilihan,
            'berat_dikonfirmasi' => (int) $order->berat_dikonfirmasi,
            'items' => $items,
        ];
    }

    private function paymentMap(object $payment): array
    {
        return [
            'id_pembayaran' => (int) $payment->id,
            'id_pesanan' => (int) $payment->pesanan_id,
            'tanggal_bayar' => $this->dateString($payment->tanggal_bayar),
            'metode_bayar' => $payment->metode_bayar,
            'jumlah_bayar' => (float) $payment->jumlah_bayar,
        ];
    }

    private function notificationMap(object $notification): array
    {
        return [
            'id_notifikasi' => (int) $notification->id,
            'id_pelanggan' => (int) $notification->pelanggan_id,
            'id_pesanan' => (int) $notification->pesanan_id,
            'pesan' => $notification->pesan,
            'dibaca' => (int) $notification->dibaca,
            'created_at' => $this->dateString($notification->created_at),
        ];
    }

    private function reportRange(Request $request): array
    {
        $range = $request->validate([
            'range_start' => ['required', 'date'],
            'range_end' => ['required', 'date', 'after:range_start'],
        ]);

        return [
            Carbon::parse($range['range_start'])->format('Y-m-d H:i:s'),
            Carbon::parse($range['range_end'])->format('Y-m-d H:i:s'),
        ];
    }

    private function dateString(mixed $value): ?string
    {
        return $value === null ? null : Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    private function monthName(int $month): string
    {
        return [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ][$month];
    }
}
