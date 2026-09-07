# AGENTS.md — Aturan untuk asisten AI di KAISAN

Berkas ini adalah cerminan HANDOVER.md yang dipakai sebagai aturan kerja oleh
asisten AI (agen). Jika isinya bertentangan dengan `CLAUDE.md` atau
`.claude/rules/*.md`, **aturan yang menang tetap yang di `CLAUDE.md` dan
`.claude/rules/`**. Laporkan konfliknya ke developer, jangan memilih salah satu
diam-diam.

Aturan mengikat yang wajib dibaca sebelum menulis kode apa pun:

- `CLAUDE.md` — ringkasan proyek
- `.claude/rules/security.md`
- `.claude/rules/coding-style.md`
- `.claude/rules/testing.md`
- `.claude/rules/git-workflow.md`
- `.claude/rules/agents.md` (kapan delegasi ke subagent)
- `.claude/rules/performance.md`
- `.claude/rules/domain-kaisan.md`

---

## 1. Aplikasi apa ini

Platform bimbel untuk **KAISAN Bimbel**, ~500 murid. Laravel 12 + Livewire 3 +
Filament 4 + PostgreSQL 17 + Redis 7, monolith, satu VPS, Docker Compose.

**Lima fitur inti.** Jangan menambah di luar ini tanpa persetujuan eksplisit:

1. Bank soal pilihan ganda dengan bantuan AI (guru selalu me-review sebelum terbit).
2. Latihan adaptif per mata pelajaran.
3. Ujian terjadwal dengan koreksi otomatis.
4. Leaderboard dan season yang bisa di-reset.
5. Dua panel terpisah: guru dan murid.

**Model bisnis: jual putus.** Kode harus bisa diserahterimakan dan dirawat
orang lain; klien non-teknis harus bisa mengoperasikannya tanpa developer.
Kontrak membatasi revisi **2x per modul**, jadi penambahan cakupan adalah
risiko nyata — kalau permintaan terdengar seperti fitur baru, tandai ke
developer dulu, jangan langsung kerjakan.

**Antarmuka: Bahasa Indonesia.** Label, pesan validasi, email yang dilihat
guru/murid wajib Bahasa Indonesia. Kode, nama variabel, komentar, dan commit
message tetap **Bahasa Inggris**.

Peran pengguna: `admin`, `guru`, `murid`. Detail di `CLAUDE.md`.

---

## 2. Definisi "selesai"

Tidak ada pekerjaan yang selesai sebelum tiga perintah ini hijau:

```bash
docker compose exec -T app php artisan config:clear   # WAJIB - lihat Jebakan 1
docker compose exec -T app composer lint              # Pint
docker compose exec -T app composer analyse           # PHPStan level 6
docker compose exec -T app composer test              # Pest
```

Patokan serah terima saat ini: **lint PASS 248 berkas, analyse no errors,
test 459 passed**. Kalau ada yang merah, pekerjaan belum selesai — jangan
melapor sukses berdasarkan "seharusnya jalan".

**TDD wajib** untuk skoring, adaptive difficulty, leaderboard, dan aturan
jadwal ujian. Setiap laporan bug menghasilkan **satu test regresi lebih
dulu**, dibuktikan gagal, baru diperbaiki. Bukan formalitas: setiap bug di
bagian 6 lolos justru karena test yang ada tidak memeriksa hal yang benar.

---

## 3. Aturan yang paling sering dilanggar AI

Kalau ragu, pilih yang lebih ketat.

1. **Kunci jawaban tidak pernah sampai ke klien selama ujian.** Properti
   komponen Livewire ikut terkirim ke browser. `ExamPaper` hanya
   mengeluarkan `id`, `number`, `stem`, `options`.
2. **Skor dihitung di server saat submit.** Jangan menerima skor dari klien.
3. **Waktu selesai ditentukan server** (`started_at + duration`), bukan
   timer browser.
4. **Soal AI selalu berstatus `review` dulu.** Tidak ada jalur ke
   `published` tanpa persetujuan guru.
5. **Data murid adalah data anak di bawah umur.** Jangan log nama, email,
   atau jawaban — log ID saja. Jangan kirim data murid ke AI router.
6. **Jangan andalkan menyembunyikan tombol sebagai otorisasi.** Setiap
   model punya Policy, server tetap harus menolak.
7. **Jangan panggil AI router sinkron di request HTTP.** Selalu lewat queue.
8. **Angka rating Elo tidak pernah diperlihatkan ke murid** — hanya band
   level.
9. **Latihan tidak menambah poin peringkat.** Hanya ujian.
10. **Nilai tidak pernah dihapus** — pembatalan pakai `voided_at` + alasan.

Batas mekanis: file 300 baris, method 40 baris, logika bisnis di `Actions/`
atau `Services/` — bukan di Blade atau Controller.

---

## 4. Jebakan yang sudah memakan waktu

Tujuh hal ini sudah pernah menggigit. Kenali gejalanya.

1. **`config:cache` membuat test menyerang database dev.** `phpunit.xml`
   menetapkan `kaisan_test`, tapi config yang di-cache mengalahkan `<env>`
   PHPUnit. *Gejala:* error unique-constraint pada data seeder saat test.
   **Selalu `config:clear` sebelum `composer test`.**
2. **Default kolom wajib dicerminkan di `$attributes` model.** Sudah
   menggigit empat kali. *Gejala:* `Call to a member function X() on null`
   atau `must be of type int, null given` tepat setelah sesuatu dibuat.
   Dijaga `tests/Unit/ModelDefaultsTest.php`.
3. **Layout melayani dua jalur render.** Halaman Blade lewat
   `@yield('content')`, komponen Livewire full-page lewat `$slot`.
   *Gejala:* halaman 200 tapi `<main>` kosong — blank di browser. Jangan
   hapus cabang `@isset($slot)` di `resources/views/layouts/app.blade.php`.
4. **`wire:model` deferred pada kontrol tersembunyi.** Radio jawaban itu
   `sr-only` dan gaya "terpilih" dirender server. *Gejala:* klik tidak
   terasa, tombol lanjut terkunci selamanya. Pakai `.live` di sana.
5. **Jangan `wire:poll` di halaman ujian.** 150 murid × polling = beban
   sia-sia (lihat `performance.md`). Timer pakai Alpine; server tetap
   pemegang kebenaran waktu.
6. **Caddy menimpa `X-Forwarded-Proto`.** *Gejala:* CSS dan JS diblokir
   sebagai mixed content di halaman https. Dijaga
   `trusted_proxies static private_ranges` di `docker/caddy/Caddyfile`.
7. **Tipe properti Filament harus sama persis dengan induknya.**
   `$navigationGroup` itu `UnitEnum|string|null`, bukan `?string`.
   *Gejala:* fatal error saat `composer install` menjalankan
   `package:discover`.

---

## 5. Peta kode singkat

```
app/
  Actions/          aksi tunggal - di sinilah logika bisnis
  Enums/            status sebagai enum PHP, bukan string bebas
  Filament/         panel guru & admin (Resource dipakai bersama dua panel)
  Jobs/             kerja async: generasi AI, hitung leaderboard
  Livewire/Murid/   layar latihan & pengerjaan ujian
  Policies/         otorisasi - setiap model punya satu
  Services/         AiRouter, Adaptive, Scoring, Exams, Leaderboard
resources/views/
  components/ui/    18 komponen design system
  components/icon/  ikon SVG inline, tanpa library
  layouts/app.blade.php
docs/               spesifikasi, design system, onboarding
.claude/            aturan, skill, subagent
```

Alur yang paling perlu dipahami sebelum mengubah apa pun:

- `app/Services/Exams/ExamPaper.php` — apa yang murid boleh lihat.
- `app/Actions/GradeExamAttempt.php` — penilaian.
- `app/Services/Adaptive/EloRating.php` — rumus adaptif.

---

## 6. Alur kerja git

- Satu branch = satu modul: `feat/<modul>-<ringkas>`, `fix/<ringkas>`.
- Conventional Commits, Bahasa Inggris.
- **Jangan commit kode yang testnya gagal.**
- **Jangan force-push ke `main`.**
- Jangan operasi git destruktif tanpa konfirmasi developer.
- Tetap buat PR walau solo — itu jejak audit untuk serah terima ke klien.

---

## 7. Yang TIDAK boleh dilakukan (ringkasan)

- Jangan pakai paket berbayar (Nova, Vapor, Forge). Klien beli sekali,
  tanpa lisensi berulang.
- Jangan panggil AI router sinkron di dalam request HTTP. Selalu lewat queue.
- Jangan simpan API key AI router di repo atau di database. Hanya `.env`,
  dan `.env.example` sengaja kosong.
- Jangan tambah fitur di luar 5 fitur inti tanpa konfirmasi eksplisit.
  Scope creep adalah risiko utama — kontraknya membatasi revisi 2x per modul.
- Jangan bikin microservice, Kubernetes, atau message broker eksternal.
  Satu VPS, monolith.
- **Dark mode sengaja tidak dibuat.** Kalau diminta, itu penambahan cakupan.

---

## 8. Hal terbuka yang harus diwaspadai

1. **Kunci AI lama pernah bocor di repo publik.** Sudah tidak ada di
   riwayat `main`, tapi masih ada di riwayat branch lama. **Kunci itu harus
   dicabut dan diganti** — membersihkan riwayat tidak menarik kembali yang
   sudah tersebar.
2. **Kata sandi akun contoh ada di repo publik** (`AccountSeeder`). Siapa
   pun yang menemukan URL dev bisa masuk sebagai admin. Ganti sebelum
   menyebarkan link.
3. **Rate limit 60/menit untuk simpan jawaban belum benar-benar terpasang**
   — `throttle` ada di `GET /ujian/{exam}`, sedangkan penyimpanan lewat
   `POST /livewire/update` yang tidak melewati grup itu.

---

## 9. Perilaku yang diharapkan dari agen AI

- Baca `CLAUDE.md` dan `.claude/rules/*.md` sebelum menulis kode.
- **Rencanakan dulu** untuk apa pun yang menyentuh lebih dari tiga berkas
  (gunakan subagent `planner` sesuai `.claude/rules/agents.md`).
- Tulis test regresi **sebelum** memperbaiki bug, dan buktikan gagal dulu.
- Jalankan lint, analyse, dan test — **tempelkan hasilnya**, jangan mengaku
  selesai berdasarkan dugaan.
- Kalau menyentuh auth, alur ujian, atau data murid, minta tinjauan
  keamanan (subagent `security-reviewer`).
- Jangan menambah dependensi berbayar, microservice, atau broker eksternal.
- Kalau permintaan terdengar seperti fitur baru dan bukan perbaikan,
  katakan ke developer sebelum mengerjakannya.
- Jangan mendelegasikan commit ke subagent. Subagent mengembalikan hasil;
  sesi utama atau developer yang memutuskan.
- Kalau dua subagent memberi saran bertentangan, jangan pilih diam-diam —
  sampaikan konfliknya ke developer.

---

## 10. Lingkungan dev singkat

```bash
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app npm ci && docker compose exec app npm run build
docker compose exec app php artisan migrate --seed
```

URL lokal: `http://127.0.0.1:8080`. Akun contoh ada di
`database/seeders/AccountSeeder.php`. Fitur AI butuh `AI_ROUTER_*` di
`.env` — nilai tidak ada di repo, minta ke pemilik proyek; `.env.example`
sengaja kosong dan harus tetap kosong.
