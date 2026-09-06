# Konteks Desain — KAISAN

Dokumen masukan untuk pembuatan Design System KAISAN. Disusun dari isi repo pada
branch `feat/m6-leaderboard-core` (M0–M6 sudah berkode, M7 belum mulai).

Sumber: `CLAUDE.md`, `.claude/rules/*`, `.claude/skills/frontend-patterns`,
`docs/01-PRD.md`, `docs/02-ARCHITECTURE.md`, `docs/03-DATABASE.md`,
`docs/04-ROADMAP.md`, `database/migrations/*`, `routes/web.php`,
`app/Livewire/**`, `app/Filament/**`, `resources/views/**`, `resources/css/app.css`,
`package.json`, `composer.json`.

Apa pun yang tidak ada di repo ditulis **TIDAK DIKETAHUI**, bukan ditebak.

---

## 1. Ringkasan produk

Aplikasi web untuk KAISAN Bimbel, lembaga bimbingan belajar lokal dengan ~500 murid
aktif. Guru memakainya untuk membuat bank soal (manual atau dibantu AI), menjadwalkan
ujian, dan melihat nilai; murid memakainya untuk latihan adaptif, mengerjakan ujian
terjadwal, dan melihat peringkat per musim. Panel guru/admin dipakai dari meja kerja,
sedangkan halaman murid dipakai saat latihan mandiri dan saat ujian terjadwal — beban
puncak ~150 murid mengerjakan ujian bersamaan. Model bisnisnya jual putus ke satu klien
non-teknis, jadi seluruh operasi harian harus bisa dilakukan dari antarmuka tanpa
terminal. Bahasa antarmuka sepenuhnya Bahasa Indonesia.

---

## 2. Peran & panel

| Peran | Panel | Teknologi | Kebebasan styling |
|---|---|---|---|
| `murid` | Situs publik + halaman murid (`/`, `/latihan`, `/ujian`, `/peringkat`) | Blade + Tailwind 4 untuk halaman statis; Livewire 3 penuh untuk latihan & pengerjaan ujian; Alpine untuk timer & konfirmasi | **Penuh.** Markup milik sendiri, tanpa kerangka pihak ketiga. Ini area utama design system. |
| `guru` | Filament 4 di `/admin` | Filament 4 (Resource, Table, Form, Action, Notification) | **Terbatas.** Mengikuti komponen Filament. Kustomisasi realistis: palet warna panel, label/ikon/urutan navigasi, dan Blade view kustom pada halaman khusus. |
| `admin` | Filament 4 di `/admin` (superset milik guru) | sama seperti guru | sama seperti guru |

Catatan:
- `Role::canAccessPanel()` = semua kecuali `murid`. Guru dan admin memakai panel yang
  sama; pembedanya ada di Policy, bukan di panel terpisah.
- Halaman auth murid (`/masuk`, lupa/atur ulang/ganti kata sandi) memakai Blade sendiri,
  **bukan** halaman login Filament. Filament punya login sendiri di `/admin/login`.
  Jadi ada dua tampilan login yang harus diselaraskan design system.
- Panel Filament saat ini `->colors(['primary' => Color::Amber])`, sedangkan seluruh
  halaman murid memakai netral slate/hitam. **Dua identitas visual yang belum menyatu.**

---

## 3. Inventaris layar

Status: **[SUDAH ADA]** = file & rutenya ada di repo. **[RENCANA]** = disebut di
PRD/roadmap atau tersirat dari data, tapi belum ada kodenya.

### 3.1 Layar murid (dan layar publik/auth)

| Layar | Peran | Tujuan | Data utama yang tampil | Aksi utama | Modul | Status |
|---|---|---|---|---|---|---|
| Beranda `/` | tamu/semua | Halaman depan aplikasi | Judul, tagline, satu badge status (`lang/id/app.php`) | Ke Masuk | M0 | [SUDAH ADA] — masih teks placeholder "Aplikasi sedang disiapkan" |
| Setup awal `/setup` | tamu (sekali jalan) | Membuat akun admin pertama tanpa terminal | Form nama, email, kata sandi | Buat admin | M1 | [SUDAH ADA] |
| Masuk `/masuk` | tamu | Login semua peran | Form email + kata sandi, error validasi | Masuk, ke Lupa kata sandi | M1 | [SUDAH ADA] |
| Lupa kata sandi `/lupa-kata-sandi` | tamu | Minta tautan atur ulang | Form email, pesan status | Kirim tautan | M1 | [SUDAH ADA] |
| Atur ulang kata sandi `/atur-ulang-kata-sandi/{token}` | tamu | Menetapkan kata sandi baru | Form kata sandi + konfirmasi | Simpan | M1 | [SUDAH ADA] |
| Ganti kata sandi `/ganti-kata-sandi` | semua login | Ganti kata sandi sendiri | Form kata sandi lama/baru | Simpan | M1 | [SUDAH ADA] |
| Daftar latihan `/latihan` | murid | Pilih mata pelajaran untuk dilatih | Nama mapel, badge level (Pemula/Berkembang/Mahir/Ahli), bar progres, `answers_count`, ada/tidaknya soal terbit | Mulai / Lanjut berlatih | M5 | [SUDAH ADA] |
| Latihan adaptif `/latihan/{subject}` | murid | Mengerjakan soal yang menyesuaikan kemampuan | Level + bar progres + keterangan level, dijawab/benar sesi ini, kartu soal 4 opsi, umpan balik benar/salah, pembahasan, catatan naik level | Periksa jawaban, Soal berikutnya, Sudahi latihan | M5 | [SUDAH ADA] (Livewire) |
| Daftar ujian `/ujian` | murid | Melihat ujian yang menyasar kelasnya | Judul, mapel, badge status ujian, mulai/selesai, durasi, jumlah soal, nilai bila sudah dikumpulkan | Mulai / Lanjutkan ujian | M4 | [SUDAH ADA] |
| Pengerjaan ujian `/ujian/{exam}` | murid | Mengerjakan ujian terjadwal | Header lengket: nomor soal ke-n dari N + sisa waktu; kartu soal 4 opsi; peta nomor soal (hijau = terjawab); nilai setelah dikumpulkan | Pilih jawaban (auto-save), Sebelumnya/Berikutnya, lompat nomor, Kumpulkan (konfirmasi) | M4 | [SUDAH ADA] (Livewire + Alpine) |
| Peringkat `/peringkat` | murid | Melihat posisi sendiri di musim berjalan | Nama musim, tab Gabungan + per mapel, 20 besar (rank, nama, poin), baris "Posisimu" bila di luar 20 besar | Ganti papan (gabungan/mapel) | M6 | [SUDAH ADA] |
| Rincian hasil ujian per murid (jawaban sendiri + pembahasan setelah dinilai) | murid | Melihat kesalahan sendiri setelah ujian | `attempt_answers` + soal + pembahasan | — | M4? | [RENCANA] — tidak ada rutenya, tidak dijanjikan eksplisit di PRD/roadmap. Perlu keputusan (§9) |
| Riwayat nilai & perkembangan latihan | murid | Melihat progres jangka panjang | `exam_attempts`, `practice_answers.rating_before/after` | — | — | [RENCANA] — datanya lengkap di DB, layarnya belum ada |

### 3.2 Layar guru & admin (panel Filament `/admin`)

| Layar | Peran | Tujuan | Data utama yang tampil | Aksi utama | Modul | Status |
|---|---|---|---|---|---|---|
| Login panel `/admin/login` | guru, admin | Masuk ke panel | Form bawaan Filament | Masuk | M1 | [SUDAH ADA] |
| Dashboard `/admin` | guru, admin | Halaman pertama panel | Hanya widget bawaan Filament (`AccountWidget`, `FilamentInfoWidget`) | — | M1 | [SUDAH ADA] — **belum ada widget KAISAN sama sekali** |
| Akun (`UserResource`) — daftar | admin | Kelola akun guru & murid | Nama, email, peran, kelas, aktif, terakhir masuk; filter peran/kelas/status | Tambah akun, Impor murid dari CSV, Ubah, Aktifkan/Nonaktifkan | M1 | [SUDAH ADA] |
| Akun — buat/ubah | admin | Form akun | Nama, email, peran, kelas, kata sandi | Simpan | M1 | [SUDAH ADA] |
| Kelas (`ClassroomResource`) | admin (ubah), guru (lihat) | Kelola rombongan belajar | Nama, jenjang, tahun ajaran | Tambah, Ubah, Hapus (hanya bila tanpa murid) | M1 | [SUDAH ADA] |
| Mata Pelajaran (`SubjectResource`) | admin (ubah), guru (lihat) | Kelola mapel | Nama, slug, aktif | Tambah, Ubah, Hapus (hanya bila tanpa soal) | M2 | [SUDAH ADA] |
| Bab/Topik (relation manager di Mata Pelajaran) | guru, admin | Kelola bab per mapel | Nama, urutan | Tambah, Ubah, Urutkan | M2 | [SUDAH ADA] |
| Bank Soal (`QuestionResource`) — daftar | guru, admin | Kelola & tinjau soal | Potongan batang soal, mapel, bab, status, asal (guru/AI), kesulitan, % dijawab benar, pembuat; filter status/asal/mapel + filter "Perlu ditinjau" | Tulis soal, Impor dari CSV, Pratinjau murid, Ubah status, Tindakan massal (setujui/tolak), Hapus | M2, M3 | [SUDAH ADA] |
| Bank Soal — buat/ubah | guru, admin | Menulis satu soal | Batang soal, 4 opsi (A–D), kunci, pembahasan, mapel, bab, kesulitan, status | Simpan | M2 | [SUDAH ADA] |
| Pratinjau soal (modal) | guru, admin | Melihat soal persis seperti di layar murid | Render `<x-question-preview>` + kunci & pembahasan | Tutup | M2 | [SUDAH ADA] |
| Permintaan Soal AI (`AiGenerationJobResource`) | guru, admin | Meminta & memantau generasi soal | Waktu diminta, mapel, bab, kesulitan, jumlah diminta, status job, jumlah tersimpan, peminta, perkiraan biaya | **Buat soal dengan AI** (modal: mapel, bab, kesulitan, jumlah, jenjang kelas), Lihat rincian/alasan gagal | M3 | [SUDAH ADA] |
| Biaya AI (`AiCostReport`) | admin | Rekap biaya AI | Bulan berjalan (biaya + ambang anggaran) dan riwayat per bulan: jumlah job, soal tersimpan, token, biaya | — | M3 | [SUDAH ADA] |
| Ujian (`ExamResource`) — daftar | guru, admin | Kelola ujian | Judul, mapel, kelas peserta, status, mulai, ditutup, jumlah soal, jumlah pengerjaan, durasi; filter status/mapel | Buat, Jadwalkan, Lihat nilai, Ubah, Hapus | M4 | [SUDAH ADA] |
| Ujian — buat/ubah | guru, admin | Menyusun ujian | Seksi Ujian (judul, mapel, musim, kelas peserta), Seksi Jadwal (mulai, ditutup, durasi, bobot kesulitan), Seksi Pengacakan (acak soal, acak opsi) | Simpan | M4 | [SUDAH ADA] |
| Soal ujian (relation manager) | guru, admin | Memilih soal untuk satu ujian | Daftar soal terpilih + urutan | Tambah soal manual atau acak per topik & kesulitan | M4 | [SUDAH ADA] |
| Nilai ujian (`ExamResults`) | guru, admin | Melihat hasil satu ujian | Ringkasan (mengerjakan, sudah dinilai, rata-rata, tertinggi, terendah); tabel nilai per murid (murid, kelas, benar, nilai, status); daftar soal tersulit | — | M4 | [SUDAH ADA] |
| Musim (`SeasonResource`) | guru (lihat), admin (reset) | Kelola musim peringkat | Nama musim, berjalan?, mulai, berakhir, jumlah ujian, juara gabungan musim itu | **Mulai musim baru** (modal + checkbox konfirmasi ganda) | M6 | [SUDAH ADA] |
| Halaman panduan/serah terima di panel | admin | Membantu klien non-teknis | — | — | M7 | [RENCANA] — M7 belum mulai; kemungkinan berupa dokumen, bukan layar |

---

## 4. State per layar (5 layar murid terpenting)

### 4.1 Pengerjaan ujian `/ujian/{exam}`

| Kondisi | Yang dilihat murid |
|---|---|
| Empty | Tidak ada kondisi kosong murni: ujian tanpa soal ditolak server saat mulai, murid dikembalikan ke daftar ujian dengan pesan. Kasus ini seharusnya sudah dicegah di sisi guru. |
| Loading | Soal dimuat sekali di `mount()`. Tidak ada spinner di dalam kartu soal. Penyimpanan jawaban lewat `wire:model.live` (request kecil, tanpa indikator). Tombol "Ya, kumpulkan" dinonaktifkan lewat `wire:loading.attr="disabled"`. **Belum ada indikator "menyimpan…" / "tersimpan" yang terlihat murid.** |
| Error | Banner kuning (`amber-50`/`amber-300`) di atas kartu soal, berisi pesan dari `ExamWorkflowException`: "Ujian ini sedang tidak dibuka.", "Anda sudah mengumpulkan ujian ini.", "Waktu ujian sudah habis, jawaban terakhir tidak bisa disimpan." |
| Sukses / penuh data | Header lengket: "Soal 3 dari 20" + "Sisa waktu 24:13" (mono, tabular-nums; merah bila ≤ 60 detik). Kartu soal: nomor, batang soal, 4 opsi sebagai label besar (≥ 44px) dengan radio. Navigasi Sebelumnya/Berikutnya. Peta nomor soal 44×44: aktif = hitam, terjawab = hijau, kosong = putih. Kalimat penenang: "Jawaban tersimpan otomatis setiap kali kamu memilih." Konfirmasi kumpulkan menyebut jumlah soal yang masih kosong. |
| Selesai | Kartu tengah: "Ujian sudah dikumpulkan", nilai besar (`text-4xl`), tombol kembali ke daftar ujian. Bila belum dinilai: judul "Ujian selesai" + pesan. |
| Waktu habis | Timer browser mencapai 00:00 → memanggil `$wire.kumpulkan()` otomatis. Server tetap yang memutuskan sah/tidaknya. |

### 4.2 Latihan adaptif `/latihan/{subject}`

| Kondisi | Yang dilihat murid |
|---|---|
| Empty | Kartu: "Belum ada soal untuk dilatih" + "Mata pelajaran ini belum punya soal yang bisa dilatih. Beri tahu gurumu, ya." + tombol Kembali. |
| Loading | Tidak ada state loading eksplisit; perpindahan soal lewat request Livewire. Bar progres memakai `transition-all duration-500` (satu-satunya animasi di aplikasi). |
| Error | Pesan validasi pilihan muncul sebagai banner kuning kecil di bawah kartu soal. Pesan domain lain: "Sesi latihan ini sudah ditutup. Mulai latihan baru untuk melanjutkan." |
| Sukses / penuh data | Kartu status atas: "Levelmu sekarang: Mahir", "Dijawab 12 · benar 9", bar progres, kalimat keterangan level. Kartu soal + tombol "Periksa jawaban". Setelah dijawab: panel hijau ("Benar.") atau kuning ("Belum tepat. Jawaban yang benar: C.") + pembahasan + catatan bila level berubah, lalu tombol "Soal berikutnya". |
| Selesai | Kartu tengah: "Latihan selesai", "Kamu menjawab N soal, M di antaranya benar.", pengingat bahwa latihan tidak mempengaruhi peringkat, tombol kembali. |

### 4.3 Daftar ujian `/ujian`

| Kondisi | Yang dilihat murid |
|---|---|
| Empty | Kartu: "Belum ada ujian untuk kamu saat ini. Kalau gurumu sudah menjadwalkan ujian, ujian itu akan muncul di halaman ini." Muncul juga bila tidak ada musim aktif atau murid belum punya kelas. |
| Loading | Halaman Blade biasa (bukan Livewire). Perpindahan halaman memakai `wire:navigate` — tanpa skeleton. |
| Error | Banner kuning dari `session('pesan')`, mis. saat murid ditolak masuk ke ujian. |
| Sukses / penuh data | Kartu per ujian: judul, mapel, badge status (hijau bila menerima pengerjaan, abu bila tidak), grid Mulai/Selesai/Durasi/Jumlah soal, lalu salah satu dari: tombol "Mulai ujian"/"Lanjutkan ujian", "Ujian belum dimulai.", "Sudah dikumpulkan. Nilai kamu: 85.", atau "Ujian sudah ditutup…". |

### 4.4 Daftar latihan `/latihan`

| Kondisi | Yang dilihat murid |
|---|---|
| Empty | Kartu: "Belum ada mata pelajaran yang bisa dilatih." |
| Empty parsial | Mapel tetap ditampilkan tapi tanpa tombol: "Belum ada soal di mata pelajaran ini." |
| Loading | Tidak ada. |
| Error | Tidak ada state error di layar ini (403 bila bukan murid). |
| Sukses / penuh data | Kartu per mapel: nama, badge level (atau "Belum dimulai" bila belum pernah latihan), bar progres, "N soal sudah kamu kerjakan di mata pelajaran ini.", tombol "Mulai berlatih"/"Lanjut berlatih". |

### 4.5 Peringkat `/peringkat`

| Kondisi | Yang dilihat murid |
|---|---|
| Empty — tanpa musim | "Belum ada musim yang berjalan. Peringkat akan muncul setelah admin memulai musim baru." |
| Empty — musim ada, belum ada nilai | "Belum ada nilai ujian di musim ini. Peringkat muncul setelah ujian pertama dinilai." |
| Empty — murid belum berpoin | Papan tetap tampil, ditambah: "Kamu belum punya poin di papan ini. Ikut ujian untuk mulai masuk peringkat." |
| Loading | Tidak ada; data dibaca dari `leaderboard_entries` (cache Redis 60 detik). |
| Error | Tidak ada state error khusus. |
| Sukses / penuh data | Kalimat musim berjalan, deret tab pil (Gabungan + tiap mapel), daftar 20 besar (rank, nama, poin) dengan baris sendiri di-highlight cincin hitam + "(kamu)". Bila di luar 20 besar: blok terpisah "Posisimu". Catatan kaki: "Peringkat diperbarui setiap beberapa menit…". |

---

## 5. Komponen berulang

| Komponen | Dipakai di | Varian yang dibutuhkan |
|---|---|---|
| **Kartu soal** (`<x-question-body>`, sudah ada) | Pengerjaan ujian, Latihan adaptif, Pratinjau soal guru | interaktif / terkunci; dengan highlight kunci (khusus guru) / tanpa; dengan slot tambahan (pembahasan) |
| **Opsi jawaban** (bagian dari kartu soal) | sama seperti di atas | normal, terpilih, kunci benar (hijau), terkunci/disabled. Target sentuh ≥ 44px. **Belum ada varian "salah dipilih" (merah)** |
| **Badge status** | Daftar ujian (status ujian), Bank soal (status & asal soal), Permintaan AI (status job), Nilai ujian (status pengerjaan) | Enum sudah menetapkan warna semantik: gray / info / success / warning / danger / primary. Di sisi murid baru ada 2 varian (hijau, abu) — perlu diselaraskan |
| **Badge level kemampuan** | Daftar latihan, Latihan adaptif | Pemula, Berkembang, Mahir, Ahli, dan "Belum dimulai" |
| **Bar progres** | Daftar latihan, Latihan adaptif | statis vs beranimasi; perlu diputuskan apakah warnanya mengikuti level |
| **Kartu daftar** (putih, `border-slate-200`, `rounded-lg`, `p-4 sm:p-6`) | Daftar ujian, Daftar latihan, semua state kosong, kartu selesai | normal, ditandai/terpilih (cincin hitam di peringkat), kosong/informasi |
| **Baris leaderboard** | Peringkat (20 besar & blok "Posisimu") | normal, baris sendiri (highlight), baris terpisah di luar 20 besar |
| **Banner pesan** | Daftar ujian (pesan sesi), Pengerjaan ujian (error), Latihan (validasi), Ganti kata sandi (`<x-ui.status>`) | sukses (emerald), peringatan/error lunak (amber), error keras (red — kini hanya di input) |
| **Tombol** (`<x-ui.button>` + banyak tombol inline) | Semua layar | primer (hitam solid), sekunder (outline), penuh-lebar vs inline, disabled. **`<x-ui.button>` saat ini hanya dipakai di form auth; halaman murid menulis ulang class yang sama berkali-kali — kandidat utama perapihan** |
| **Input berlabel** (`<x-ui.input>`) | Semua form auth & setup | normal, error (border merah + pesan di bawah), dengan hint |
| **Timer ujian** | Pengerjaan ujian | normal, mendesak (≤ 60 detik → merah) |
| **Peta nomor soal** | Pengerjaan ujian | aktif, terjawab, belum dijawab |
| **Tab pil** | Peringkat (Gabungan / per mapel) | aktif, non-aktif |
| **Header + navigasi utama** (`layouts/app.blade.php`) | Semua halaman non-Filament | tamu vs login; tautan murid vs tautan panel. Saat ini semua tautan bergaya `underline` polos dalam satu baris — **belum ada pola navigasi mobile** |
| **Kartu statistik (dt/dd)** | Nilai ujian (Filament), Biaya AI (Filament) | grid 2 kolom di HP sampai 5 kolom di desktop |

---

## 6. Data motivasi yang tersedia

### Sudah ada di schema (bisa langsung ditampilkan ke murid)

| Angka/status | Sumber | Sudah tampil? |
|---|---|---|
| Level kemampuan per mapel (Pemula/Berkembang/Mahir/Ahli) | `student_abilities.rating` → `AbilityLevel::forRating()` | Ya — daftar latihan & layar latihan |
| Progres di dalam level (0–100%) | `student_abilities.rating` → `AbilityLevel::progressFor()` | Ya — bar progres |
| Jumlah soal latihan yang pernah dikerjakan per mapel | `student_abilities.answers_count` | Ya |
| Terakhir kali berlatih | `student_abilities.last_practiced_at` | **Belum** — kolom ada, tidak dipakai di UI |
| Dijawab & benar dalam satu sesi latihan | `practice_sessions.questions_count`, `correct_count` | Ya (hanya sesi berjalan) |
| Akurasi latihan kumulatif per mapel | agregasi `practice_sessions` | **Belum** — tidak butuh kolom baru, cukup query |
| Rating sebelum/sesudah tiap jawaban latihan | `practice_answers.rating_before`, `rating_after` | **Belum** — cukup untuk grafik perkembangan, datanya lengkap |
| Nilai tiap ujian | `exam_attempts.score` | Sebagian — hanya nilai terakhir di daftar ujian |
| Jumlah benar / total soal per ujian | `exam_attempts.correct_count`, `total_questions` | **Belum** di sisi murid (sudah ada di sisi guru) |
| Riwayat seluruh pengerjaan ujian | `exam_attempts` (permanen, tidak pernah dihapus) | **Belum** — tidak ada layar riwayat murid |
| Waktu pengerjaan per soal | `attempt_answers.time_spent_ms` | **Belum** — kolom ada, belum dipakai di mana pun |
| Peringkat & poin, gabungan dan per mapel | `leaderboard_entries.rank`, `points` | Ya |
| Nama musim berjalan | `seasons.name`, `is_active` | Ya |
| Peringkat musim-musim sebelumnya (termasuk juara) | Entri musim lama **tidak dihapus** saat reset — `ResetSeason` hanya menutup musim lalu membuka yang baru | Sisi guru saja (kolom "Peringkat 1" di tabel Musim) |
| Tingkat kesulitan soal | `questions.difficulty` (Elo) + band Mudah/Sedang/Sulit | Tidak ke murid (disengaja) |

> **Aturan yang mengikat:** `.claude/skills/adaptive-difficulty` dan `AbilityLevel`
> eksplisit bahwa **angka rating Elo tidak boleh diperlihatkan ke murid** — hanya band
> level. Design system tidak boleh menampilkan "1247" di layar murid.
> Aturan domain 4: latihan tidak menambah poin peringkat — ini perlu tetap dikomunikasikan di UI.

### Butuh kolom/tabel baru bila diinginkan

| Ide motivasi | Kebutuhan schema |
|---|---|
| Beruntun benar (streak) saat ini & terpanjang | Bisa dihitung dari `practice_answers`, tapi mahal per tampilan → tambah `student_abilities.current_streak` + `best_streak` (integer default 0, dicerminkan di `$attributes`) |
| Beruntun hari berlatih (daily streak) | Kolom baru, mis. `student_abilities.streak_days` (integer default 0) + `last_streak_date` (date nullable) — **belum ada** |
| Lencana / pencapaian | Tabel baru `achievements` + pivot `user_achievements` — **belum ada** |
| Tren peringkat (naik/turun sejak perhitungan sebelumnya) | `leaderboard_entries` hanya menyimpan kondisi terkini per musim → tambah kolom `previous_rank` (integer nullable) atau tabel snapshot |
| Target pribadi / goal per mapel | Tabel baru — **belum ada** |
| Perbandingan dengan rata-rata kelas | Bisa diagregasi dari `exam_attempts` + `users.classroom_id`, tanpa kolom baru |

---

## 7. Batasan teknis

| Hal | Kondisi |
|---|---|
| Tailwind | **v4** (`tailwindcss ^4.0.0` + `@tailwindcss/vite`). **Tidak ada `tailwind.config.js`** — konfigurasi tema hidup di `resources/css/app.css` lewat `@theme { }`. Token baru harus ditulis sebagai CSS variable di situ. |
| Build | Vite 7 + `laravel-vite-plugin`. Entry: `resources/css/app.css`, `resources/js/app.js`. Tanpa SPA, tanpa framework JS lain. |
| Filament | **v4**, satu panel `admin` di `/admin`. Kustomisasi saat ini hanya `->colors(['primary' => Color::Amber])`. **Belum ada custom theme** (tidak ada `->viteTheme()` maupun file CSS tema Filament). Batas realistis: palet warna Filament, ikon/label/urutan navigasi, dan Blade view kustom untuk halaman khusus (sudah dipakai di Nilai ujian & Biaya AI). Mengubah bentuk komponen Filament secara mendalam melawan arus dan menyulitkan upgrade. |
| Livewire/Alpine | Livewire 3 dengan `wire:navigate` antar halaman murid. Alpine untuk timer & konfirmasi kumpulkan. **`wire:poll` dilarang di halaman ujian** (150 murid × polling = beban sia-sia). |
| Dark mode | **Halaman murid: tidak ada** — nol class `dark:`, `<body>` dipatok `bg-slate-50 text-slate-900`. Panel Filament: view kustom sudah menulis `dark:` mengikuti bawaan Filament. Dark mode saat ini setengah jalan dan perlu diputuskan. |
| i18n / bahasa | Bahasa Indonesia saja. Tersedia `lang/id/` (`app`, `auth`, `validation`, `passwords`, `pagination`, `actions`, `http-statuses`) + `lang/id.json`. **Namun `lang/id/app.php` hanya berisi 5 kunci; hampir seluruh teks layar murid masih hardcoded di Blade** — menyalahi `.claude/rules/coding-style.md` ("semua string yang dilihat pengguna masuk `lang/id/`"). Design system sebaiknya sekalian memindahkannya. |
| Font | `--font-sans` di `@theme` menyebut `'Instrument Sans'`, **tetapi font itu tidak pernah dimuat** — tidak ada `@font-face` maupun tautan Google Fonts di `layouts/app.blade.php`. Efektifnya aplikasi memakai font sistem. Perlu diputuskan: muat fontnya, atau hapus dan pakai system stack (lebih hemat untuk HP kelas bawah). |
| Performa | Halaman < 2 detik saat 150 murid ujian bersamaan. Halaman ujian < 10 query per request. Soal dimuat sekali di awal. Leaderboard dibaca dari tabel hasil + cache Redis 60 detik. VPS 4 vCPU / 8 GB. Implikasi desain: hindari aset berat, animasi mahal, dan font/ikon dari CDN eksternal. |
| Aksesibilitas | Wajib: target sentuh ≥ 44×44 px (dipakai `min-h-11` / `h-11 w-11`), setiap input punya `<label>`, pesan error di dekat inputnya, kontras minimal AA, skip-link sudah ada di layout. |
| Mobile | `.claude/skills/frontend-patterns`: **mobile-first, sebagian besar murid membuka dari HP**. Lebar konten dipatok `max-w-3xl` untuk semua halaman murid. |
| Bahasa UI | Sopan, sederhana, tanpa jargon teknis. "Ujian belum dimulai." bukan "Exam state invalid (403)." Sapaan ke murid memakai "kamu". |
| Keamanan yang membatasi UI | `answer_key` tidak boleh sampai ke klien selama ujian; pratinjau kunci hanya di sisi guru. Nama/email murid tidak boleh masuk log. Menyembunyikan tombol bukan otorisasi. |
| Cakupan | Kontrak membatasi revisi 2x per modul. Menambah layar di luar 5 fitur inti dihitung penambahan cakupan, bukan revisi. |

---

## 8. Token yang sudah ada

Belum ada design token formal. Yang ada adalah pola utility Tailwind yang berulang —
ini token de-facto yang perlu dikodifikasi:

| Kategori | Nilai yang benar-benar dipakai |
|---|---|
| Font | `@theme --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, …emoji` (file font tidak dimuat — lihat §7) |
| Warna netral | `slate-50` (latar halaman), `white` (kartu, header), `slate-100/200/300` (border, bar kosong, badge netral), `slate-400/500/600/700` (teks sekunder), `slate-800/900` (teks utama, tombol primer, aksen aktif) |
| Warna sukses | `emerald-50`, `emerald-100`, `emerald-300`, `emerald-400`, `emerald-500`, `emerald-900` — jawaban benar, badge ujian aktif, banner status, nomor soal terjawab |
| Warna peringatan | `amber-50`, `amber-300`, `amber-900` — jawaban salah, banner pesan/error lunak |
| Warna bahaya | `red-500`, `red-600`, `red-700` — border & pesan error input, timer ≤ 60 detik |
| Warna panel Filament | `primary` = `Color::Amber` (berbeda dari halaman murid) |
| Radius | `rounded` (tombol, input, kotak nomor soal), `rounded-lg` (kartu, banner), `rounded-full` (badge, tab pil, bar progres) |
| Lebar konten | `max-w-3xl` + `px-4`; `py-10` untuk `<main>`, `py-4` untuk header |
| Padding kartu | `p-3 sm:p-4` (baris peringkat), `p-4 sm:p-6` (kartu umum), `p-6` (kartu state kosong/selesai) |
| Jarak | `space-y-2` (daftar peringkat), `space-y-4` (daftar kartu), `space-y-6` (blok halaman Livewire), `gap-2/3/4` |
| Skala teks | `text-xs` (badge), `text-sm` (sekunder), `text-base` (isi & opsi jawaban), `text-lg` (judul kartu), `text-xl`/`text-2xl` (judul halaman), `text-4xl` (nilai akhir) |
| Bobot teks | `font-medium`, `font-semibold`, `font-bold` (hanya nilai akhir ujian) |
| Target sentuh | `min-h-11` (44px) untuk semua tombol/tautan aksi/opsi jawaban; `h-11 w-11` untuk kotak nomor soal |
| Border | `border-slate-200` (kartu), `border-slate-300` (input, tombol sekunder), `ring-1 ring-slate-900` (item terpilih) |
| Bayangan | **Tidak dipakai sama sekali** — desain saat ini datar, hanya mengandalkan border |
| Animasi | Hanya `transition-all duration-500` pada bar progres latihan |
| Ikon | **Tidak ada ikon di halaman murid.** Panel Filament memakai Heroicon (mis. `Heroicon::OutlinedTrophy`) |

---

## 9. Pertanyaan terbuka

Hal yang tidak bisa dijawab dari repo dan harus diputuskan manusia:

| # | Pertanyaan | Kenapa penting |
|---|---|---|
| 1 | **Target perangkat murid** — HP Android kelas bawah? Berapa RAM, versi Android, ukuran layar tipikal? Ada yang memakai perangkat pinjaman/bergantian? | Menentukan anggaran CSS/JS, boleh-tidaknya font kustom dimuat, dan kelayakan animasi/bayangan. Repo hanya menyebut "sebagian besar murid membuka dari HP". **TIDAK DIKETAHUI** |
| 2 | **Mobile-first atau desktop-first** untuk masing-masing panel | Aturan repo menyebut mobile-first untuk murid, tapi tidak menyebut apa pun untuk panel guru (Filament praktis desktop-first). Perlu ditegaskan apakah guru pernah memakai panel dari HP. **TIDAK DIKETAHUI** |
| 3 | **Warna & logo dari klien** — ada identitas merek KAISAN? File logo, warna primer, tipografi? | Tidak ada aset merek di repo sama sekali; halaman murid netral slate/hitam, panel Filament amber. Tanpa jawaban ini, palet apa pun hanya tebakan. **TIDAK DIKETAHUI** |
| 4 | **Rentang usia murid** — SD, SMP, SMA, atau campuran? | Menentukan tingkat bahasa, ukuran teks dasar, dan seberapa "ceria" nada visual boleh. PRD hanya menyebut "mayoritas di bawah 18 tahun". **TIDAK DIKETAHUI** |
| 5 | Dark mode untuk halaman murid: dibuat atau tidak? | Belum ada sama sekali; menambahkannya menggandakan pekerjaan token dan pengujian |
| 6 | Bolehkah murid melihat rincian jawabannya setelah ujian dinilai (soal + kunci + pembahasan)? | Layarnya belum ada dan tidak disebut di roadmap. Ini fitur baru atau bagian M4? Menyentuh aturan kunci jawaban |
| 7 | Perlukah layar riwayat murid (nilai ujian lampau, grafik perkembangan latihan)? | Datanya sudah lengkap di DB, tapi layarnya berpotensi dihitung penambahan cakupan |
| 8 | Font: muat 'Instrument Sans' atau pakai font sistem? | Sekarang dideklarasikan tapi tidak dimuat — harus diputuskan, jangan dibiarkan ambigu |
| 9 | Seberapa jauh panel Filament perlu diselaraskan dengan identitas halaman murid — cukup ganti warna primer, atau perlu custom theme? | Menentukan besar pekerjaan dan risiko saat upgrade Filament |
| 10 | Beranda `/` akan jadi halaman penjelasan/pemasaran, atau cukup pengalih ke Masuk? | Isinya masih placeholder M0 |
| 11 | Elemen motivasi apa yang benar-benar diinginkan klien (streak, lencana, target)? | Semuanya butuh kolom/tabel baru (§6) dan berpotensi dianggap penambahan cakupan |
| 12 | Perlukah mode kontras tinggi atau pengaturan ukuran teks untuk murid? | Belum ada; berdampak ke struktur token sejak awal |
