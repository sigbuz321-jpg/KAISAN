# Roadmap

Urutan ini disusun supaya setiap modul bisa didemokan ke klien dan diterima
sebelum lanjut. Ini penting karena kontrak membatasi revisi 2x per modul —
penerimaan bertahap mencegah revisi menumpuk di akhir.

Tandai status di sini setiap kali modul selesai. Beri tag git `v0.<n>.0`.

---

## M0 — Fondasi
**Status:** selesai — diterima, `v0.0.0`

- Laravel 12 + PostgreSQL + Redis via Docker Compose
- Pest, Pint, PHPStan level 6, skrip `composer test/lint/analyse`
- CI dasar (GitHub Actions: lint + test)
- Layout dasar, Tailwind, bahasa Indonesia (`lang/id/`)

**Selesai kalau:** `docker compose up -d` dari repo kosong menghasilkan aplikasi
yang jalan, dan seluruh verifikasi hijau.

---

## M1 — Identitas & peran
**Status:** selesai — diterima, `v0.1.0`

- Model `users`, `classrooms`
- Login, logout, ganti password, lupa password
- Role admin/guru/murid + Policy dasar
- Panel Filament untuk admin: kelola guru & murid, impor murid dari CSV
- Perintah membuat admin pertama tanpa terminal (halaman setup sekali jalan)

**Selesai kalau:** admin bisa membuat akun guru dan mengimpor 500 murid dari CSV
lewat panel.

---

## M2 — Bank soal manual
**Status:** selesai — diterima, `v0.2.0`

- `subjects`, `topics`, `questions`
- CRUD soal di Filament, dengan pratinjau tampilan murid
- Alur status: draft → review → published → archived
- Impor soal dari CSV/Excel

**Selesai kalau:** guru bisa membuat, mengedit, dan mempublikasikan soal, dan
melihat persis seperti apa soal itu di layar murid.

Dikerjakan sebelum M3 supaya generasi AI punya tempat yang jelas untuk menaruh
hasilnya, dan supaya aplikasi tetap berguna kalau AI bermasalah.

---

## M3 — Generasi soal dengan AI
**Status:** selesai — menunggu penerimaan (tag `v0.3.0`)

Baca `.claude/skills/ai-question-generation/SKILL.md` sebelum mulai.

- Klien AI Router + `ai_generation_jobs`
- Job asinkron + Horizon
- Validasi keluaran, deteksi duplikat via `stem_hash`
- Antarmuka review massal untuk guru (setujui/tolak per soal)
- Rate limit per guru, pencatatan token & biaya
- Halaman rekap biaya AI di panel admin

**Selesai kalau:** guru meminta 20 soal, mendapat notifikasi saat siap, meninjau,
dan menyetujui — dan admin bisa melihat berapa biayanya.

---

## M4 — Ujian terjadwal
**Status:** selesai — menunggu penerimaan (tag `v0.4.0`)

Baca `.claude/skills/exam-leaderboard/SKILL.md` sebelum mulai.

- `exams`, `exam_questions`, `exam_attempts`, `attempt_answers`
- Pembuatan ujian di Filament: pilih soal manual atau acak per topik & kesulitan
- Halaman pengerjaan murid (Livewire): timer, simpan parsial, navigasi soal
- Skoring server-side, transisi status via scheduled command
- Halaman hasil untuk guru: nilai per murid, soal tersulit

**Modul paling berisiko.** Kerjakan dengan TDD penuh. Uji semua kasus tepi di
skill terkait sebelum demo ke klien.

**Selesai kalau:** 30 murid uji coba bisa mengerjakan ujian bersamaan tanpa
kehilangan jawaban, dan nilai muncul otomatis.

---

## M5 — Latihan adaptif
**Status:** selesai — menunggu penerimaan (tag `v0.5.0`)

Baca `.claude/skills/adaptive-difficulty/SKILL.md` sebelum mulai.

- `student_abilities`, `practice_sessions`, `practice_answers`
- Rumus Elo + pemilihan soal
- Halaman latihan murid dengan umpan balik langsung + pembahasan
- Tampilan level (Pemula/Berkembang/Mahir/Ahli), bukan angka
- Penanda soal bermasalah untuk guru (benar < 15%)

**Selesai kalau:** murid yang menjawab benar beruntun mendapat soal yang jelas
lebih sulit, dan sebaliknya.

---

## M6 — Leaderboard & season
**Status:** belum mulai

- `seasons`, `leaderboard_entries`
- Job agregasi tiap 5 menit dengan window function
- Halaman peringkat murid: gabungan & per mata pelajaran
- Reset season dari panel admin dengan konfirmasi ganda + snapshot juara

**Selesai kalau:** admin bisa reset season dari panel, peringkat kosong, dan
seluruh riwayat nilai masih utuh.

---

## M7 — Serah terima
**Status:** belum mulai

Jalankan `/handover`.

- Panduan klien Bahasa Indonesia lengkap (6 dokumen + screenshot)
- Deploy produksi, TLS, backup harian + uji restore
- Rotasi log
- Sesi pelatihan untuk guru
- Dokumen biaya berjalan (VPS, domain, AI variabel)

---

## Di luar cakupan (kalau diminta, ini fitur baru — bukan revisi)

- Soal esai / penilaian manual
- Materi video atau modul belajar
- Absensi, SPP, pembayaran
- Aplikasi mobile native
- Multi-cabang / multi-tenant
- Notifikasi WhatsApp otomatis
- Laporan PDF untuk orang tua

Kalau klien meminta salah satu dari ini, tandai sebagai penambahan cakupan dan
bahas harganya terpisah.
