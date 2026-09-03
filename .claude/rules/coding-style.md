# Rule: Gaya Kode

## Prinsip

Kode ini akan diserahkan ke pihak lain (jual putus). Optimalkan untuk **mudah dibaca
orang asing**, bukan untuk pintar. Kalau harus memilih antara elegan dan jelas,
pilih jelas.

## Konvensi Laravel

- Ikuti PSR-12. Format dengan Laravel Pint sebelum commit.
- Nama file & kelas: `StudentAbility`, bukan `student_ability`.
- Tabel jamak snake_case: `exam_attempts`. Kolom snake_case.
- Model tunggal: `ExamAttempt`.
- Enum PHP asli untuk status, bukan string bebas:
  `enum ExamStatus: string { case Draft = 'draft'; ... }`

## Organisasi file

```
app/
  Actions/          # aksi tunggal, dipanggil dari controller/command/job
  Enums/
  Jobs/             # kerja async (generasi AI, hitung leaderboard)
  Livewire/
    Murid/          # komponen panel murid
  Filament/
    Resources/      # panel guru & admin
  Models/
  Policies/
  Services/
    AiRouter/       # klien AI router + parsing respons
    Adaptive/       # logika difficulty
    Scoring/
```

- Batas file: **300 baris**. Lebih dari itu, pecah.
- Batas method: **40 baris**. Lebih dari itu, pecah.
- Logika bisnis tidak boleh ada di Blade atau di Controller. Taruh di `Actions/`
  atau `Services/`.

## Immutability & efek samping

- Method yang mengembalikan nilai jangan mengubah state. Method yang mengubah state
  jangan mengembalikan nilai yang dipakai untuk pengambilan keputusan.
- Kelas Service sebaiknya stateless. State ada di database.
- Hindari static mutable. Pakai dependency injection.

## Database access

- Selalu waspada N+1. Pakai `with()` eksplisit. Aktifkan
  `Model::preventLazyLoading()` di environment lokal & testing.
- Query yang menyentuh lebih dari 1000 baris wajib pakai `chunk()` atau `cursor()`.
- Jangan pakai `$model->update()` di dalam loop. Pakai bulk update.

## Default kolom wajib dicerminkan di model

Kalau sebuah kolom punya `default()` di migration, tulis juga di `$attributes`
model. Kalau tidak, model yang baru dibuat melaporkan `null` untuk kolom itu —
nilai default baru muncul setelah di-`refresh()`.

```php
// migration
$table->integer('answers_count')->default(0);

// model — wajib, bukan opsional
protected $attributes = ['answers_count' => 0];
```

Bug ini sudah muncul tiga kali di proyek ini: status `queued` di
`ai_generation_jobs` (M3), `times_answered` di `questions` (M5), dan
`questions_count` di `practice_sessions` (M5). Dua di antaranya baru ketahuan
di CI, satu hampir lolos ke VPS.

Gejalanya selalu sama: `TypeError ... must be of type int, null given`, atau
sebuah nilai yang tampak benar di database tapi salah di layar tepat setelah
sesuatu dibuat.

## Komentar

- Jangan komentari apa yang sudah jelas dari kode.
- Komentari **kenapa**, terutama untuk rumus adaptive difficulty dan skoring —
  itu bagian yang akan membingungkan orang berikutnya.
- Tulis komentar dalam Bahasa Inggris.

## Teks antarmuka

- Semua string yang dilihat pengguna masuk `lang/id/`. Jangan hardcode di Blade.
- Nada bahasa: sopan, sederhana, tanpa jargon teknis. Klien non-teknis.
- Contoh baik: "Ujian belum dimulai." Contoh buruk: "Exam state invalid (403)."
