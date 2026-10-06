# Bersih Laundry — backend untuk uji coba Render + Neon

Paket ini berasal dari backend `bersih-laundry-ap`. File .env, database lokal, cache, log, vendor dan node_modules tidak disertakan.

## Upload ke GitHub
Ekstrak ZIP. Unggah ISI folder bersih-laundry-api ke root repository (bukan ZIP). Dockerfile, artisan, composer.json, app, routes, config, database, public, bootstrap, storage dan deploy harus berada di root. Aktifkan Situs desktop jika tombol Upload files tidak terlihat di HP. File tersembunyi .gitignore dan .dockerignore juga perlu ikut diunggah. Jangan unggah RENDER_ENV.example yang sudah diisi rahasia.

## Database
Buat project Free di Neon. Ambil connection string PostgreSQL, termasuk sslmode=require, lalu masukkan langsung ke Render sebagai DB_URL. Jangan kirim password database di chat atau GitHub. Database uji ini kosong; data MySQL lama belum dipindahkan.

## Render
New Web Service → Git Provider → hubungkan GitHub → pilih repository.
Language: Docker. Branch: main. Root Directory: kosong. Dockerfile Path: ./Dockerfile. Instance: Free. Health Check Path: /up. Docker Command: kosong.
Isi variabel Environment berdasarkan RENDER_ENV.example. Buat APP_KEY di komputer dengan:
`php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"`
Buat DEPLOY_TEST_TOKEN berbeda dengan:
`php -r "echo bin2hex(random_bytes(32)).PHP_EOL;"`
Jangan gunakan contoh placeholder sebagai nilai sebenarnya.

Deploy menjalankan migrasi otomatis. SEED_DEMO=false secara default. Jika butuh akun contoh, atur OWNER_DEMO_PASSWORD dan CASHIER_DEMO_PASSWORD minimal 12 karakter, lalu SEED_DEMO=true. Akun contoh hanya dibuat jika belum ada; mengubah environment tidak mengganti password akun yang sudah dibuat.

## Akses pengujian
/up tersedia sebagai health check aplikasi. /api/v1/health memeriksa koneksi database dan memerlukan header X-Deploy-Test-Token. Semua endpoint API menggunakan header ini. Token adalah pembatas akses bersama untuk percobaan, bukan sistem autentikasi pengguna/peran. Gunakan hanya data fiktif. Sebelum penggunaan nyata, diperlukan autentikasi per pengguna dan pemeriksaan hak akses serta kepemilikan data.
Flutter asli belum diubah: alamat API dan header akses masih perlu disesuaikan setelah URL Render tersedia. APK belum dibuat.

## Validasi dan batasan
Struktur paket, JSON Composer, shell startup dan pengecualian file rahasia telah diperiksa. PHP/Composer/Docker tidak tersedia di lingkungan penyusunan, sehingga build container dan migrasi PostgreSQL belum dijalankan. Konfirmasi hasil melalui log build Render dan endpoint database. Render Free tidur saat tidak aktif; file unggahan lokal tidak persisten. Jangan pakai database SQLite untuk data permanen di Render Free.
