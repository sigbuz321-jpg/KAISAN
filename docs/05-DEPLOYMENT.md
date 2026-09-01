# Deployment & Operasional

Target: VPS 4 vCPU, 8 GB RAM, 60 GB storage, Ubuntu 24.04.

## Susunan container

| Service | Image | Batas memori |
|---|---|---|
| app (PHP-FPM 8.3) | build lokal | 2.5 GB |
| caddy | caddy:2-alpine | 256 MB |
| postgres | postgres:17-alpine | 3 GB |
| redis | redis:7-alpine | 640 MB |
| horizon | sama dgn app | 640 MB |
| scheduler | sama dgn app | 256 MB |

Set `mem_limit` di `docker-compose.yml`. Tanpa batas, PostgreSQL akan mengambil
apa pun yang tersedia dan Horizon akan kelaparan saat ujian berlangsung.

## Setelan penting

**PostgreSQL** (`postgresql.conf`)
```
shared_buffers = 2GB
effective_cache_size = 4GB
work_mem = 16MB
max_connections = 100
```

**Redis**
```
maxmemory 512mb
maxmemory-policy allkeys-lru
```

**PHP-FPM**
```
pm = dynamic
pm.max_children = 20
pm.start_servers = 6
```
20 anak × ~120 MB ≈ 2.4 GB. Menaikkan angka ini tanpa menaikkan RAM akan memicu
OOM killer tepat saat 150 murid submit bersamaan.

## Langkah deploy pertama

1. Arahkan domain ke IP VPS
2. `git clone` repo ke `/opt/kaisan`
3. Salin `.env.example` → `.env`, isi semua nilai
4. `docker compose up -d --build`
5. `docker compose exec app php artisan migrate --force`
6. `docker compose exec app php artisan storage:link`
7. Buka `https://domain/setup` untuk membuat akun admin pertama
8. Hapus rute setup: set `SETUP_ENABLED=false` di `.env`, lalu
   `docker compose restart app`

Caddy mengurus sertifikat TLS otomatis. Tidak ada langkah manual.

## Variabel `.env` yang wajib diisi

```
APP_ENV=production
APP_DEBUG=false            # JANGAN pernah true di produksi
APP_URL=https://...
DB_CONNECTION=pgsql
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
CACHE_STORE=redis

AI_ROUTER_URL=
AI_ROUTER_KEY=
AI_ROUTER_MODEL=
AI_MONTHLY_BUDGET=          # peringatan kalau terlampaui
```

## Setelah setiap deploy

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan horizon:terminate    # worker restart dgn kode baru
npm run build
```

Ingat: setelah `config:cache`, panggilan `env()` di luar `config/` akan
mengembalikan null. Ini penyebab bug produksi paling umum di Laravel.

## Backup

Cron harian jam 02:00:
```bash
docker compose exec -T postgres pg_dump -U kaisan kaisan | gzip > /backup/kaisan-$(date +%F).sql.gz
find /backup -name 'kaisan-*.sql.gz' -mtime +7 -delete
```

**Uji restore-nya minimal sekali sebelum serah terima.** Backup yang belum pernah
direstore bukan backup.

Salin backup ke penyimpanan di luar VPS. Kalau VPS hilang, backup di dalamnya
ikut hilang.

## Rotasi log

`storage/logs` dibersihkan setelah 14 hari. Docker log dibatasi:
```yaml
logging:
  driver: json-file
  options: { max-size: "10m", max-file: "3" }
```
60 GB terdengar banyak sampai log memakannya diam-diam.

## Pemantauan minimum

- Horizon dashboard (dibatasi admin) untuk melihat job gagal
- Peringatan email kalau job gagal > 10 dalam sejam
- Peringatan kalau disk > 80%
- Peringatan kalau biaya AI bulan berjalan melewati `AI_MONTHLY_BUDGET`

## Biaya berjalan yang ditanggung klien

| Pos | Sifat |
|---|---|
| VPS | tetap, bulanan |
| Domain | tetap, tahunan |
| Penggunaan AI | **variabel**, tergantung berapa banyak soal dibuat |

Biaya AI harus disampaikan sebagai variabel, bukan angka tetap. Panel admin
menyediakan rekap bulanan supaya klien bisa memantaunya sendiri.

## Prosedur saat ujian berlangsung

Jangan deploy saat ada ujian aktif. Cek dulu:
```sql
SELECT count(*) FROM exams WHERE status = 'active';
```
Kalau > 0, tunda.
