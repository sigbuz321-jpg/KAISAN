# KAISAN — Platform Bimbel Digital

Konteks utama untuk Claude Code. Baca file ini lebih dulu di setiap sesi.

## Ringkasan Proyek

Aplikasi web untuk **KAISAN Bimbel**, lembaga bimbingan belajar lokal. Skala target
~500 murid aktif. Model bisnis: **jual putus** ke klien — bukan SaaS. Artinya kode
harus bisa diserahterimakan dan dirawat oleh orang lain, dan klien (guru, non-teknis)
harus bisa mengoperasikannya tanpa developer.

### Fitur inti
1. **Bank soal pilihan ganda dengan generasi AI** — guru minta soal, AI router membuat
   draf, guru me-review sebelum publish. AI tidak pernah langsung ke murid.
2. **Latihan adaptif per mata pelajaran** — tingkat kesulitan menyesuaikan kemampuan murid.
3. **Ujian terjadwal** — jadwal mulai/selesai, durasi, koreksi otomatis.
4. **Leaderboard + season** — peringkat per season, bisa di-reset tiap periode.
5. **Dua panel terpisah** — panel guru (kelola) dan panel murid (belajar/ujian).

## Stack

| Lapisan | Pilihan | Catatan |
|---|---|---|
| Bahasa | PHP 8.3 | |
| Framework | Laravel 12 | monolith, server-rendered |
| UI dinamis | Livewire 3 + Alpine.js | tanpa build SPA terpisah |
| Panel guru/admin | Filament 4 | CRUD, jadwal, bank soal |
| Styling | Tailwind CSS 4 | |
| Database | PostgreSQL 17 | JSONB + window function |
| Cache/Queue/Session | Redis 7 | |
| Queue worker | Laravel Horizon | wajib untuk job AI |
| Test | Pest 3 | |
| Web server | Caddy (auto-HTTPS) | |
| Deploy | Docker Compose di VPS | 4 vCPU / 8 GB RAM / 60 GB |

**Bahasa antarmuka: Bahasa Indonesia.** Semua label, pesan validasi, dan email yang
dilihat guru/murid harus Bahasa Indonesia. Kode, nama variabel, komentar, dan commit
message tetap Bahasa Inggris.

## Peran pengguna

- `admin` — pemilik bimbel. Akses penuh, kelola akun guru, setting, season.
- `guru` — buat soal, jadwalkan ujian, lihat nilai kelasnya.
- `murid` — latihan adaptif, ikut ujian, lihat nilai & peringkat sendiri.

## Aturan wajib

Aturan lengkap ada di `.claude/rules/`. Semuanya berlaku selalu, tanpa diminta:

- `security.md` — otorisasi, PII murid (banyak di bawah umur), rate limit
- `coding-style.md` — konvensi Laravel, organisasi file
- `testing.md` — TDD, coverage minimum
- `git-workflow.md` — format commit, alur PR
- `agents.md` — kapan mendelegasikan ke subagent
- `performance.md` — batas resource VPS, manajemen konteks
- `domain-kaisan.md` — istilah domain & aturan bisnis

## Perintah yang sering dipakai

```bash
# Dev
docker compose up -d
php artisan migrate:fresh --seed
php artisan horizon

# Verifikasi (jalankan SEBELUM bilang "selesai")
composer test              # Pest
composer lint              # Pint --test
composer analyse           # PHPStan level 6
npm run build
```

## Yang TIDAK boleh dilakukan

- Jangan pakai paket berbayar (Nova, Vapor, Forge). Klien beli sekali, tanpa lisensi berulang.
- Jangan panggil AI router secara sinkron di dalam request HTTP. Selalu lewat queue.
- Jangan simpan API key AI router di repo atau di database. Hanya `.env`.
- Jangan tambah fitur di luar 5 fitur inti tanpa konfirmasi eksplisit. Scope creep
  adalah risiko utama proyek ini — kontraknya membatasi revisi 2x per modul.
- Jangan bikin microservice, Kubernetes, atau message broker eksternal. Satu VPS, monolith.

## Dokumen pendukung

- `docs/01-PRD.md` — spesifikasi produk & user story
- `docs/02-ARCHITECTURE.md` — keputusan arsitektur
- `docs/03-DATABASE.md` — skema database
- `docs/04-ROADMAP.md` — urutan pengerjaan per modul
- `docs/05-DEPLOYMENT.md` — deploy & serah terima ke klien
