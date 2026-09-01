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

## Komentar

- Jangan komentari apa yang sudah jelas dari kode.
- Komentari **kenapa**, terutama untuk rumus adaptive difficulty dan skoring —
  itu bagian yang akan membingungkan orang berikutnya.
- Tulis komentar dalam Bahasa Inggris.

## Teks antarmuka

- Semua string yang dilihat pengguna masuk `lang/id/`. Jangan hardcode di Blade.
- Nada bahasa: sopan, sederhana, tanpa jargon teknis. Klien non-teknis.
- Contoh baik: "Ujian belum dimulai." Contoh buruk: "Exam state invalid (403)."
