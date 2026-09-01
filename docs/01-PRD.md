# PRD — KAISAN Bimbel

## Masalah

KAISAN Bimbel mengelola ~500 murid secara manual: soal dibuat dan digandakan di
kertas, ujian dikoreksi tangan, nilai direkap di spreadsheet. Guru kehabisan waktu
di pekerjaan administratif, dan murid tidak mendapat umpan balik cepat.

## Tujuan

1. Guru bisa membuat bank soal dalam hitungan menit, bukan jam.
2. Ujian dikoreksi otomatis, nilai langsung terlihat.
3. Murid berlatih di tingkat kesulitan yang sesuai kemampuannya.
4. Ada motivasi kompetitif yang sehat lewat peringkat per season.

## Bukan tujuan

- Bukan SaaS multi-bimbel. Satu instalasi untuk KAISAN saja.
- Bukan LMS lengkap. Tidak ada video, tidak ada materi, tidak ada absensi.
- Tidak ada soal esai atau penilaian manual di versi ini.
- Tidak ada aplikasi mobile native. Web responsif saja.
- Tidak ada pembayaran/SPP.

## Pengguna

| Peran | Siapa | Kebutuhan utama |
|---|---|---|
| Admin | Pemilik bimbel | Kelola akun guru, season, lihat biaya AI |
| Guru | Pengajar, non-teknis | Buat soal cepat, jadwalkan ujian, lihat nilai |
| Murid | Siswa, mayoritas di bawah 18 tahun | Latihan, ikut ujian, lihat progres |

## User story

### Guru
- Sebagai guru, saya ingin membuat 20 soal Matematika bab Aljabar dengan bantuan AI,
  lalu memeriksa dan menyetujuinya, agar saya tidak menulis dari nol.
- Sebagai guru, saya ingin menjadwalkan ujian pada tanggal dan jam tertentu dengan
  durasi tetap, agar semua murid mengerjakan dalam kondisi yang sama.
- Sebagai guru, saya ingin melihat nilai seluruh murid di kelas saya segera setelah
  ujian ditutup, agar bisa langsung membahasnya di pertemuan berikutnya.
- Sebagai guru, saya ingin tahu soal mana yang paling banyak dijawab salah, agar
  saya tahu materi mana yang perlu diulang.

### Murid
- Sebagai murid, saya ingin berlatih soal yang tidak terlalu mudah dan tidak terlalu
  sulit, agar saya tetap tertantang tanpa menyerah.
- Sebagai murid, saya ingin melihat peringkat saya di mata pelajaran tertentu,
  agar saya tahu posisi saya.
- Sebagai murid, saya ingin jawaban saya tersimpan meski internet saya putus di
  tengah ujian, agar saya tidak kehilangan pekerjaan.

### Admin
- Sebagai admin, saya ingin me-reset season di akhir semester, agar peringkat mulai
  dari nol tanpa menghilangkan riwayat nilai.
- Sebagai admin, saya ingin melihat berapa biaya AI bulan ini, agar saya bisa
  menganggarkannya.

## Kriteria penerimaan tingkat produk

- 150 murid bisa mengerjakan ujian bersamaan tanpa halaman melebihi 2 detik.
- Jawaban murid tidak hilang saat koneksi putus.
- Guru bisa menjalankan seluruh operasi harian tanpa membuka terminal.
- Kunci jawaban tidak bisa diakses murid selama ujian berlangsung.
- Nilai tidak pernah hilang, bahkan setelah reset season.

## Batasan

- VPS tunggal: 4 vCPU, 8 GB RAM, 60 GB.
- Tidak ada paket berbayar berulang selain VPS, domain, dan penggunaan AI.
- Biaya AI ditanggung klien dan bersifat variabel.
- Dikerjakan solo. Revisi dibatasi 2x per modul sesuai kontrak.
