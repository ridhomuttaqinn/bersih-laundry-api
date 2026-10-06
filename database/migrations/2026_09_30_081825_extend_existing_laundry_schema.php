<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('layanans')) {
            Schema::create('layanans', function (Blueprint $table): void {
                $table->id();
                $table->string('nama_layanan');
                $table->double('harga');
                $table->string('satuan');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pelanggans')) {
            Schema::create('pelanggans', function (Blueprint $table): void {
                $table->id();
                $table->string('nama_lengkap');
                $table->text('alamat');
                $table->string('nomor_telepon');
                $table->string('email')->unique();
                $table->string('password');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pesanans')) {
            Schema::create('pesanans', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('pelanggan_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->text('alamat_penjemputan');
                $table->dateTime('jadwal_penjemputan');
                $table->dateTime('tanggal_selesai')->nullable();
                $table->string('status')->default('diterima');
                $table->text('catatan_penolakan')->nullable();
                $table->decimal('total_harga', 12, 2)->default(0);
                $table->string('metode_bayar_pilihan')->nullable();
                $table->boolean('berat_dikonfirmasi')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('detail_pesanans')) {
            Schema::create('detail_pesanans', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('pesanan_id');
                $table->unsignedBigInteger('layanan_id');
                $table->decimal('jumlah', 10, 2);
                $table->decimal('subtotal', 12, 2);
                $table->timestamps();
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique();
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('kasir');
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });

        Schema::table('pesanans', function (Blueprint $table): void {
            if (! Schema::hasColumn('pesanans', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable();
            }
            if (! Schema::hasColumn('pesanans', 'tanggal_selesai')) {
                $table->dateTime('tanggal_selesai')->nullable();
            }
            if (! Schema::hasColumn('pesanans', 'catatan_penolakan')) {
                $table->text('catatan_penolakan')->nullable();
            }
            if (! Schema::hasColumn('pesanans', 'metode_bayar_pilihan')) {
                $table->string('metode_bayar_pilihan')->nullable();
            }
            if (! Schema::hasColumn('pesanans', 'berat_dikonfirmasi')) {
                $table->boolean('berat_dikonfirmasi')->default(true);
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE detail_pesanans MODIFY jumlah DECIMAL(10, 2) NOT NULL');
        }

        if (! Schema::hasTable('pembayarans')) {
            Schema::create('pembayarans', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('pesanan_id')->unique();
                $table->dateTime('tanggal_bayar');
                $table->string('metode_bayar');
                $table->decimal('jumlah_bayar', 12, 2);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('notifikasis')) {
            Schema::create('notifikasis', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('pelanggan_id');
                $table->unsignedBigInteger('pesanan_id');
                $table->text('pesan');
                $table->boolean('dibaca')->default(false);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasis');
        Schema::dropIfExists('pembayarans');

        Schema::table('pesanans', function (Blueprint $table): void {
            foreach (['user_id', 'tanggal_selesai', 'catatan_penolakan', 'metode_bayar_pilihan', 'berat_dikonfirmasi'] as $column) {
                if (Schema::hasColumn('pesanans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            foreach (['username', 'role', 'is_active'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
