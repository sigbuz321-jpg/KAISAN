# HANDOVER — KAISAN

**Untuk developer baru dan asisten AI-nya. Baca berkas ini lebih dulu, sampai
habis, sebelum menyentuh kode.**

Kalau kamu asisten AI: berkas ini adalah orientasi. Aturan yang mengikat ada di
`CLAUDE.md` dan `.claude/rules/`. Kalau isi berkas ini bertentangan dengan
`.claude/rules/`, **aturan yang menang** — laporkan konfliknya ke developer,
jangan diam-diam memilih salah satu.

---

## 1. Ini aplikasi apa

Platform bimbel untuk **KAISAN Bimbel**, ~500 murid. Laravel 12 + Livewire 3 +
Filament 4 + PostgreSQL 17 + Redis 7, monolith, satu VPS, Docker Compose.

Lima fitur inti — jangan menambah di luar ini tanpa persetujuan eksplisit:

1. Bank soal pilihan ganda dengan bantuan AI (guru selalu me-review sebelum terbit)
2. Latihan adaptif per mata pelajaran
3. Ujian terjadwal dengan koreksi otomatis
4. Leaderboard dan musim yang bisa di-reset
5. Dua panel terpisah: guru dan murid

**Model bisnis: jual putus.** Kode harus bisa dirawat orang lain, dan klien yang
non-teknis harus bisa mengoperasikannya tanpa developer. Kontrak membatasi
revisi 2x per modul, jadi penambahan cakupan adalah risiko nyata — kalau sebuah
permintaan terdengar seperti fitur baru, tandai ke developer dulu.

Antarmuka **Bahasa Indonesia**. Kode, nama variabel, komentar, dan commit
message **Bahasa Inggris**.

---

## 2. Urutan membaca

| Urutan | Berkas | Isinya |
|---|---|---|
| 1 | `CLAUDE.md` | Ringkasan proyek dan aturan wajib |
| 2 | `.claude/rules/*.md` | 7 aturan mengikat: keamanan, gaya kode, testing, git, performa, domain, subagent |
| 3 | `docs/07-ONBOARDING-DEV.md` | Akses VPS, langkah deploy, jebakan lengkap |
| 4 | `docs/01-PRD.md` sampai `05-DEPLOYMENT.md` | Spesifikasi, arsitektur, skema DB, roadmap, deploy |
| 5 | `docs/06-DESIGN-SYSTEM/README.md` | Design system: token, komponen, layar |
| 6 | `.claude/skills/*/SKILL.md` | Pola per area: adaptif, AI, backend, frontend, ujian |

`docs/DESIGN-CONTEXT.md` berisi inventaris seluruh layar — berguna untuk
memahami cakupan tanpa membaca semua kode.

---

## 3. Mulai dari mana

```bash
git clone git@github.com:sigbuz321-jpg/KAISAN.git
cd KAISAN
cp .env.example .env

docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app npm ci && docker compose exec app npm run build
docker compose exec app php artisan migrate --seed
```

Buka `http://127.0.0.1:8080`. Akun contoh ada di
`database/seeders/AccountSeeder.php`.

Fitur AI butuh `AI_ROUTER_*` di `.env`. Nilainya **tidak** ada di repo —
minta ke pemilik proyek. `.env.example` sengaja kosong dan harus tetap kosong.

Yang sudah live: **https://kaisan-dev-103-93-132-127.nip.io**

---

## 4. Definisi "selesai"

Tidak ada pekerjaan yang selesai sebelum tiga perintah ini hijau:

```bash
docker compose exec -T app php artisan config:clear   # WAJIB - lihat jebakan 1
docker compose exec -T app composer lint              # Pint
docker compose exec -T app composer analyse           # PHPStan level 6
docker compose exec -T app composer test              # Pest
```

Patokan saat serah terima: **lint PASS 248 berkas, analyse no errors,
test 459 passed**. Kalau ada yang merah, pekerjaan belum selesai — jangan
melapor sukses berdasarkan "seharusnya jalan".

**TDD wajib** untuk skoring, adaptive difficulty, leaderboard, dan aturan jadwal
ujian. Setiap laporan bug menghasilkan **satu test regresi lebih dulu**,
dibuktikan gagal, baru diperbaiki. Ini bukan formalitas: setiap bug di bagian 6
lolos justru karena test yang ada tidak memeriksa hal yang benar.

---

## 5. Aturan yang paling sering dilanggar AI

Diambil dari `.claude/rules/`. Kalau ragu, pilih yang lebih ketat.

1. **Kunci jawaban tidak pernah sampai ke klien selama ujian.** Properti
   komponen Livewire ikut terkirim ke browser. `ExamPaper` hanya mengeluarkan
   `id`, `number`, `stem`, `options`.
2. **Skor dihitung di server saat submit.** Jangan pernah menerima skor dari klien.
3. **Waktu selesai ditentukan server** (`started_at + duration`), bukan timer browser.
4. **Soal AI selalu berstatus `review` dulu.** Tidak ada jalur ke `published`
   tanpa persetujuan guru.
5. **Data murid adalah data anak di bawah umur.** Jangan log nama, email, atau
   jawaban — log ID saja. Jangan kirim data murid ke AI router.
6. **Jangan andalkan menyembunyikan tombol sebagai otorisasi.** Setiap model
   punya Policy, dan server tetap harus menolak.
7. **Jangan panggil AI router sinkron di request HTTP.** Selalu lewat queue.
8. **Angka rating Elo tidak pernah diperlihatkan ke murid** — hanya band level.
9. **Latihan tidak menambah poin peringkat.** Hanya ujian.
10. **Nilai tidak pernah dihapus** — pembatalan pakai `voided_at` + alasan.

Batas mekanis: file 300 baris, method 40 baris, logika bisnis di `Actions/` atau
`Services/` — bukan di Blade atau Controller.

---

## 6. Jebakan yang sudah memakan waktu

Tujuh hal ini sudah pernah menggigit. Gejalanya ditulis supaya bisa dikenali cepat.

**1. `config:cache` membuat test menyerang database dev.**
`phpunit.xml` menetapkan `kaisan_test`, tapi config yang di-cache mengalahkan
`<env>` PHPUnit. *Gejala:* error unique-constraint pada data seeder saat test.
Selalu `config:clear` sebelum `composer test`.

**2. Default kolom wajib dicerminkan di `$attributes` model.**
Sudah menggigit **empat kali**. *Gejala:* `Call to a member function X() on null`
atau `must be of type int, null given` tepat setelah sesuatu dibuat. Dijaga
`tests/Unit/ModelDefaultsTest.php`.

**3. Layout melayani dua jalur render.** Halaman Blade lewat `@yield('content')`,
komponen Livewire full-page lewat `$slot`. *Gejala:* halaman 200 tapi `<main>`
kosong — blank di browser. Jangan hapus cabang `@isset($slot)` di
`resources/views/layouts/app.blade.php`.

**4. `wire:model` deferred pada kontrol tersembunyi.** Radio jawaban itu
`sr-only` dan gaya "terpilih" dirender server. *Gejala:* klik tidak terasa,
tombol lanjut terkunci selamanya. Pakai `.live` di sana.

**5. Jangan `wire:poll` di halaman ujian.** 150 murid dikali polling = beban
sia-sia (`performance.md`). Timer pakai Alpine; server tetap pemegang kebenaran
waktu.

**6. Caddy menimpa `X-Forwarded-Proto`.** *Gejala:* CSS dan JS diblokir sebagai
mixed content di halaman https. Dijaga `trusted_proxies static private_ranges`
di `docker/caddy/Caddyfile`.

**7. Tipe properti Filament harus sama persis dengan induknya.**
`$navigationGroup` itu `UnitEnum|string|null`, bukan `?string`. *Gejala:* fatal
error saat `composer install` menjalankan `package:discover`.

---

## 7. Peta kode

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
`app/Services/Exams/ExamPaper.php` (apa yang murid boleh lihat),
`app/Actions/GradeExamAttempt.php` (penilaian), dan
`app/Services/Adaptive/EloRating.php` (rumus adaptif).

---

## 8. Alur kerja git

- Satu branch = satu modul: `feat/<modul>-<ringkas>`, `fix/<ringkas>`
- Conventional Commits, Bahasa Inggris
- Jangan commit kode yang testnya gagal
- Jangan force-push ke `main`
- Jangan operasi git destruktif tanpa konfirmasi developer
- Tetap buat PR walau solo — itu jejak audit untuk serah terima ke klien

---

## 9. Yang masih terbuka

1. **Kunci AI lama pernah bocor di repo publik.** Sudah tidak ada di riwayat
   `main`, tapi masih ada di riwayat branch lama. **Kunci itu harus dicabut dan
   diganti** — membersihkan riwayat tidak menarik kembali yang sudah tersebar.
2. **Kata sandi akun contoh ada di repo publik** (`AccountSeeder`). Siapa pun
   yang menemukan URL dev bisa masuk sebagai admin. Ganti sebelum menyebarkan link.
3. **Rate limit 60/menit untuk simpan jawaban belum benar-benar terpasang** —
   `throttle` ada di `GET /ujian/{exam}`, sedangkan penyimpanan lewat
   `POST /livewire/update` yang tidak melewati grup itu.
4. **M7 Serah Terima** belum mulai (`docs/04-ROADMAP.md`): panduan klien,
   deploy produksi, backup + uji restore, rotasi log, pelatihan guru.
5. **Dark mode sengaja tidak dibuat.** Kalau diminta, itu penambahan cakupan.

---

## 10. Kalau kamu asisten AI, ini yang diharapkan

- Baca `.claude/rules/` sebelum menulis kode. Aturan itu berlaku selalu, tanpa diminta.
- Rencanakan dulu untuk apa pun yang menyentuh lebih dari tiga berkas.
- Tulis test regresi **sebelum** memperbaiki bug, dan buktikan gagal dulu.
- Jalankan lint, analyse, dan test — tempelkan hasilnya, jangan mengaku selesai
  berdasarkan dugaan.
- Kalau menyentuh auth, alur ujian, atau data murid, minta tinjauan keamanan.
- Jangan menambah dependensi berbayar, microservice, atau broker eksternal.
- Kalau sebuah permintaan terdengar seperti fitur baru dan bukan perbaikan,
  katakan itu ke developer sebelum mengerjakannya.
