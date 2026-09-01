# KAISAN Bimbel — Aplikasi Digital Penunjang Bimbel

Aplikasi web penunjang kegiatan belajar di **KAISAN Bimbel**: bank soal dengan
level adaptif, pembuatan soal dibantu AI, pencatatan histori nilai, serta
leaderboard bermusim (season) untuk guru dan siswa.

> **Status:** tahap awal pengembangan. Repo ini baru berisi dokumen penawaran;
> kode aplikasi belum ditulis.

---

## Ruang lingkup

Aplikasi dibangun dalam 4 modul sesuai dokumen penawaran:

### 1. UI/UX & Frontend
- Desain antarmuka aplikasi
- **Dashboard Guru** — kelola soal, pantau nilai siswa
- **Dashboard Siswa** — mengerjakan soal, lihat riwayat & peringkat
- Responsif di HP dan tablet

### 2. Backend & Database
- Autentikasi dan manajemen user (Admin, Guru, Siswa)
- **Sistem level adaptif** — tingkat kesulitan menyesuaikan kemampuan siswa
- **Bank soal** — penyimpanan dan kategorisasi soal
- **Histori nilai** — rekam jejak hasil pengerjaan siswa

### 3. Integrasi AI
- Koneksi ke API AI melalui router model
- **Prompting engine** untuk generate soal
- **Alur review/publish** — soal hasil AI ditinjau guru sebelum terbit ke siswa

### 4. Leaderboard & Season
- Logika perhitungan ranking
- Reset season berkala
- Penyimpanan snapshot data season sebelumnya

## Peran pengguna

| Peran | Kemampuan utama |
|---|---|
| **Admin** | Manajemen user dan konfigurasi sistem |
| **Guru** | Generate soal via AI, review & publish soal, pantau nilai siswa |
| **Siswa** | Mengerjakan soal adaptif, lihat histori nilai dan leaderboard |

## Arsitektur & teknologi

> Belum ditetapkan. Bagian ini diisi saat stack dipilih.

Yang sudah pasti dari kebutuhan sistem:

- Aplikasi web responsif (bukan aplikasi native)
- Backend dengan database relasional untuk user, bank soal, dan histori nilai
- Integrasi ke penyedia API AI lewat **router model** (bukan terikat satu vendor)
- Di-deploy ke VPS, diakses lewat domain sendiri

## Kebutuhan operasional

Biaya dan layanan yang perlu disiapkan agar aplikasi bisa diakses:

| Item | Spesifikasi / keterangan | Perkiraan biaya | Sifat |
|---|---|---|---|
| Domain | contoh: `kaisanbimbel.com` | Rp 150.000 – 250.000 | Tahunan |
| VPS / server | 2 vCPU, 2 GB RAM, 60 GB SSD | Rp 100.000 – 300.000 | Bulanan |
| Pemakaian AI | seperti token, naik-turun sesuai jumlah soal | ± Rp 150.000 | Bulanan |

Biaya AI bersifat variabel — bergantung banyaknya soal yang di-generate tiap bulan.

## Setup pengembangan

> Menyusul setelah stack ditetapkan. Akan mencakup: instalasi dependency,
> konfigurasi environment (kredensial database dan API key AI), migrasi
> database, dan cara menjalankan server lokal.

Catatan keamanan sejak awal: **API key AI dan kredensial database tidak boleh
di-commit** ke repo. Gunakan file `.env` yang di-ignore git.

## Timeline

- **Estimasi pengerjaan:** 2–3 bulan sejak DP diterima dan konfirmasi penawaran
- **Garansi:** perbaikan bug gratis 30 hari setelah aplikasi live
- **Revisi tampilan:** 2 kali per modul; di luar itu dihitung pekerjaan tambahan

## Ketentuan kerja sama

Sistem **jual putus** — setelah pelunasan, source code dan kepemilikan penuh
aplikasi menjadi milik KAISAN Bimbel.

Rincian harga, opsi pengelolaan akun operasional, dan form konfirmasi ada di:
`Penawaran Harga & Form Konfirmasi - KAISAN Bimbel (FINAL).docx`

## Kontak

**Sigit Irawan** — Pengembang Aplikasi
087733446184
