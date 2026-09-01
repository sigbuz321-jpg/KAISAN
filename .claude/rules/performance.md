# Rule: Performa & Batas Resource

## Anggaran server

VPS produksi: **4 vCPU, 8 GB RAM, 60 GB storage**. Beban puncak: ~150 murid ujian
serentak (dari total 500). Alokasi kasar:

| Komponen | RAM |
|---|---|
| PHP-FPM (pm.max_children ~20) | ~2.5 GB |
| PostgreSQL (shared_buffers 2GB) | ~3 GB |
| Redis (maxmemory 512MB) | ~0.6 GB |
| Horizon worker (3 proses) | ~0.5 GB |
| Caddy + OS | ~0.5 GB |
| Cadangan | ~0.9 GB |

Kalau sebuah perubahan mendorong salah satu melewati anggarannya, bahas dulu
sebelum menulis kode.

## Aturan praktis

- **Jangan sinkron ke AI router.** Setiap panggilan lewat queue. Request HTTP yang
  menunggu AI akan menghabiskan PHP-FPM worker dan menjatuhkan situs saat ujian.
- Leaderboard dihitung lewat job terjadwal dan disimpan di tabel
  `leaderboard_entries`, lalu di-cache di Redis 60 detik. Jangan hitung on-the-fly
  saat halaman dibuka.
- Halaman ujian: query harus < 10 per request. Cek dengan Laravel Debugbar di lokal.
- Soal untuk satu sesi ujian di-load sekali di awal, lalu disimpan di session/state
  Livewire. Jangan query ulang tiap pindah nomor.
- Storage 60 GB: rotasi log 14 hari, backup database harian disimpan 7 hari,
  sisanya ke storage eksternal.

## Manajemen konteks Claude Code

- Baca file secara selektif. Jangan `cat` seluruh direktori.
- Untuk tugas besar, delegasikan ke subagent (lihat `agents.md`) supaya konteks
  utama tetap bersih.
- Kalau konteks sudah panjang dan tugasnya berganti topik, sarankan `/checkpoint`
  lalu compact.
- Gunakan model yang sesuai: tugas mekanis (rename, format, boilerplate test) tidak
  butuh model terkuat.

## Indeks database yang wajib ada

- `exam_attempts (exam_id, user_id)` unik
- `attempt_answers (attempt_id)`
- `questions (subject_id, difficulty, status)`
- `student_abilities (user_id, subject_id)` unik
- `leaderboard_entries (season_id, subject_id, points DESC)`

Setiap migration yang menambah kolom untuk filter/urut wajib menambah indeksnya
di migration yang sama.
