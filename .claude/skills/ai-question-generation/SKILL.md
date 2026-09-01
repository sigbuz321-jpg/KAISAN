---
name: ai-question-generation
description: Pola untuk membuat soal pilihan ganda lewat AI router — prompting, validasi keluaran, kontrol biaya, dan alur review guru. Gunakan saat menyentuh app/Services/AiRouter atau job generasi soal.
---

# Generasi Soal dengan AI

## Prinsip yang tidak bisa ditawar

1. **Selalu asinkron.** AI router dipanggil dari Job, tidak pernah dari request HTTP.
2. **Selalu di-review.** Soal hasil AI masuk berstatus `review`. Guru menyetujui
   sebelum bisa dipakai di ujian.
3. **Tidak ada data murid dalam prompt.** Hanya mata pelajaran, topik, tingkat
   kesulitan, jumlah soal, dan jenjang kelas.
4. **Biaya tercatat.** Setiap job menyimpan perkiraan token dan biaya. Klien
   menanggung biaya ini dan berhak melihat rinciannya.

## Bentuk prompt

Minta JSON ketat, dan katakan secara eksplisit untuk tidak menambahkan apa pun.

```
Buat {n} soal pilihan ganda Bahasa Indonesia.
Mata pelajaran: {subject}
Topik: {topic}
Jenjang: {grade}
Tingkat kesulitan: {easy|medium|hard}

Aturan:
- Tepat 4 opsi: A, B, C, D
- Tepat satu jawaban benar
- Pengecoh harus masuk akal, bukan asal salah
- Tanpa gambar, tanpa tabel
- Sertakan pembahasan singkat 1-2 kalimat

Balas HANYA dengan array JSON. Tanpa markdown, tanpa penjelasan tambahan.
[{"stem":"...","options":{"A":"...","B":"...","C":"...","D":"..."},
  "answer_key":"A","explanation":"..."}]
```

## Validasi keluaran — jangan percaya begitu saja

Setiap soal harus lolos semua pemeriksaan ini sebelum disimpan:

```php
$rules = [
    'stem'        => ['required', 'string', 'min:10', 'max:1000'],
    'options'     => ['required', 'array', 'size:4'],
    'options.A'   => ['required', 'string', 'max:500'],
    // B, C, D sama
    'answer_key'  => ['required', 'in:A,B,C,D'],
    'explanation' => ['required', 'string', 'max:1000'],
];
```

Ditambah pemeriksaan semantik:
- Tidak ada dua opsi yang identik
- `answer_key` menunjuk opsi yang ada dan tidak kosong
- Batang soal tidak duplikat dengan soal yang sudah ada di mata pelajaran yang sama
  (bandingkan hash teks yang sudah dinormalisasi)

Soal yang gagal validasi dibuang dan dicatat, bukan disimpan setengah jadi.

## Penanganan respons rusak

Model kadang membungkus JSON dalam ```` ```json ````. Bersihkan sebelum parse:

```php
$clean = preg_replace('/^```(?:json)?|```$/m', '', trim($raw));
$data = json_decode($clean, true, flags: JSON_THROW_ON_ERROR);
```

Kalau `json_decode` gagal, retry sekali dengan instruksi tambahan. Kalau gagal lagi,
tandai job `failed` dengan pesan yang bisa dimengerti guru: "Gagal membuat soal.
Silakan coba lagi atau ubah topiknya."

## Kontrol biaya

- Batas: 20 job per jam per guru, maksimal 20 soal per job.
- Simpan di `ai_generation_jobs`: `prompt_tokens`, `completion_tokens`,
  `estimated_cost`, `model`, `finished_at`.
- Panel admin punya halaman rekap biaya per bulan.
- Cache prompt yang identik selama 24 jam — guru sering menekan tombol dua kali.

## Idempotensi

Job memakai `ShouldBeUnique` dengan kunci dari ID record generasi. Kalau job
dijalankan ulang setelah crash, tidak boleh menghasilkan soal ganda.

## Testing

`Http::fake()` untuk seluruh test. Jangan pernah memanggil AI router sungguhan
dari test suite — itu membakar uang klien.

Kasus yang wajib diuji:
- Respons valid → soal tersimpan dengan status `review`
- Respons JSON rusak → job retry, lalu `failed` dengan pesan jelas
- Respons dengan 3 opsi → soal itu ditolak, sisanya tetap tersimpan
- Respons dengan `answer_key` di luar A-D → ditolak
- Job gagal → tidak ada soal berstatus setengah jadi yang tertinggal
- AI router timeout → job masuk backoff, tidak menggantung
