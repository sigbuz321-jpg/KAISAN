---
name: backend-patterns
description: Pola backend Laravel untuk KAISAN — Actions, Jobs, Services, akses database, dan penanganan error. Gunakan saat menulis atau meninjau kode di app/.
---

# Pola Backend

## Action: satu kelas, satu pekerjaan

Logika bisnis tinggal di `app/Actions/`. Controller dan komponen Livewire hanya
memvalidasi lalu memanggil Action.

```php
final class SubmitExamAttempt
{
    public function __construct(private ScoreAttempt $scorer) {}

    public function handle(ExamAttempt $attempt, array $answers): ExamAttempt
    {
        abort_if($attempt->isExpired(), 419, __('exam.window_closed'));

        return DB::transaction(function () use ($attempt, $answers) {
            $this->persistAnswers($attempt, $answers);
            $attempt->update([
                'score'        => $this->scorer->handle($attempt),
                'submitted_at' => now(),
                'status'       => AttemptStatus::Submitted,
            ]);
            return $attempt;
        });
    }
}
```

Aturan: satu method publik bernama `handle`. Dependensi lewat konstruktor.
Tanpa state internal.

## Transaksi

Apa pun yang menulis ke lebih dari satu tabel dibungkus `DB::transaction()`.
Submit ujian, generasi soal, dan reset season semuanya wajib.

Jangan panggil HTTP eksternal di dalam transaksi. Kalau butuh, panggil dulu,
baru buka transaksi.

## Job untuk pekerjaan lambat

Semua yang tidak selesai dalam 200 ms masuk queue.

```php
class GenerateQuestions implements ShouldQueue
{
    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [10, 60, 300];

    public function uniqueId(): string { return "gen:{$this->jobRecord->id}"; }

    public function failed(Throwable $e): void
    {
        $this->jobRecord->update([
            'status' => GenerationStatus::Failed,
            'error'  => $e->getMessage(),
        ]);
    }
}
```

Setiap job wajib punya `failed()` yang menandai status di database. Job yang
gagal diam-diam akan membuat guru menunggu selamanya tanpa penjelasan.

## Query

```php
// Salah — N+1
foreach ($exam->attempts as $a) { echo $a->student->name; }

// Benar
$attempts = $exam->attempts()->with('student')->get();
```

Aktifkan di `AppServiceProvider`:

```php
Model::preventLazyLoading(! app()->isProduction());
Model::preventSilentlyDiscardingAttributes(! app()->isProduction());
```

Untuk agregasi (leaderboard, rekap nilai), pakai satu query SQL dengan window
function. Jangan tarik ribuan baris ke PHP lalu diurutkan di memori.

## Enum, bukan string

```php
enum AttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted  = 'submitted';
    case Voided     = 'voided';

    public function label(): string
    {
        return match($this) {
            self::InProgress => 'Sedang dikerjakan',
            self::Submitted  => 'Sudah dikumpulkan',
            self::Voided     => 'Dibatalkan',
        };
    }
}
```

Cast di model: `protected function casts(): array { return ['status' => AttemptStatus::class]; }`

## Penanganan error

- Kesalahan pengguna → exception dengan pesan Bahasa Indonesia yang bisa ditampilkan.
- Kesalahan sistem → log dengan konteks (ID saja, bukan data pribadi), tampilkan
  pesan umum ke pengguna.
- Jangan pernah `catch (Exception $e) {}` kosong.
- Jangan tampilkan pesan exception mentah ke murid.

## Konfigurasi

`env()` hanya boleh dipanggil di dalam `config/`. Di tempat lain pakai `config()`.
Kalau tidak, aplikasi akan rusak saat `config:cache` dijalankan di produksi —
dan ini akan terjadi di VPS klien.
