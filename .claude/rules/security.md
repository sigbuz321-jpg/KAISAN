# Rule: Keamanan

Berlaku selalu. Kalau ragu, pilih yang lebih ketat.

## Data murid adalah data anak di bawah umur

Sebagian besar murid bimbel berusia di bawah 18 tahun. Perlakukan seluruh tabel
`users` dengan `role = murid` sebagai data sensitif.

- Jangan pernah log nama, email, nomor HP, atau jawaban murid ke file log.
  Log pakai ID saja: `Log::info('attempt submitted', ['attempt_id' => $id])`.
- Jangan kirim data murid ke AI router. Prompt generasi soal hanya boleh berisi
  mata pelajaran, topik, tingkat kesulitan, dan jumlah soal — tidak ada nama,
  tidak ada riwayat jawaban individual.
- Endpoint yang mengembalikan daftar murid wajib dibatasi ke kelas yang diampu guru
  tersebut, kecuali role `admin`.

## Otorisasi

- Setiap model punya Policy. Tidak ada pengecualian.
- Controller/Livewire component wajib memanggil `$this->authorize(...)` atau
  `Gate::authorize(...)`. Jangan andalkan hanya menyembunyikan tombol di Blade.
- Murid hanya boleh membaca `ExamAttempt` miliknya sendiri. Uji ini dengan test,
  bukan dengan inspeksi manual.
- Panel Filament dibatasi dengan `canAccessPanel()` — hanya `admin` dan `guru`.

## Integritas ujian

- Kunci jawaban (`answer_key`) TIDAK BOLEH pernah dikirim ke klien selama ujian
  berlangsung. Gunakan API resource terpisah yang membuang kolom itu.
- Skor dihitung di server saat submit, tidak pernah diterima dari klien.
- Waktu selesai ujian ditentukan server (`started_at + duration`), bukan timer browser.
  Timer browser hanya tampilan.
- Submit setelah `ends_at` ditolak, dengan toleransi 30 detik untuk latensi jaringan.

## Input & injeksi

- Semua input lewat Form Request atau aturan validasi Livewire. Tidak ada
  `$request->all()` yang langsung di-`create()`.
- Query mentah wajib pakai parameter binding. Tidak ada interpolasi string ke SQL.
- Soal hasil AI di-render sebagai teks biasa, bukan HTML. Kalau butuh format
  (rumus, superskrip), pakai whitelist tag yang sempit — jangan `{!! !!}` mentah.

## Rahasia

- Semua kredensial di `.env`. `.env` masuk `.gitignore`. Sediakan `.env.example`
  dengan nilai kosong.
- Kunci AI router hanya dibaca lewat `config('services.ai_router.key')`,
  tidak pernah `env()` langsung di luar file config (rusak saat config cache).
- Kalau ada kredensial tidak sengaja ter-commit, hentikan pekerjaan dan beri tahu
  developer. Jangan cuma hapus di commit berikutnya.

## Rate limit

- Login: 5 percobaan / menit per IP.
- Generasi soal AI: 20 job / jam per guru. Ini pagar biaya, bukan cuma keamanan.
- Submit jawaban: 60 / menit per murid.

## Checklist sebelum menyatakan fitur selesai

1. Ada Policy dan dipanggil?
2. Ada test yang membuktikan user lain tidak bisa akses?
3. Tidak ada data pribadi di log?
4. Input tervalidasi?
5. Tidak ada rahasia di kode?
