---
name: frontend-patterns
description: Pola Livewire 3, Blade, Filament, dan Tailwind untuk KAISAN. Gunakan saat membangun panel guru atau murid.
---

# Pola Frontend

## Pembagian tugas

| Bagian | Alat | Alasan |
|---|---|---|
| Panel guru & admin | Filament 4 | CRUD, tabel, filter — hampir jadi sendiri |
| Halaman ujian murid | Livewire 3 komponen penuh | butuh state & timer |
| Halaman statis murid | Blade biasa | tidak perlu Livewire |
| Interaksi kecil (dropdown, modal) | Alpine.js | tidak perlu round-trip server |

Jangan pakai Livewire untuk halaman yang tidak punya state. Setiap komponen
Livewire adalah request tambahan ke server.

## Halaman ujian — pola yang wajib diikuti

```php
class TakeExam extends Component
{
    public ExamAttempt $attempt;
    public array $answers = [];      // question_id => option_key
    public int $currentIndex = 0;

    // Soal dimuat SEKALI, tanpa kunci jawaban
    public function mount(ExamAttempt $attempt): void
    {
        $this->authorize('take', $attempt);
        $this->questions = $attempt->exam->questions()
            ->select('id', 'stem', 'options')   // answer_key TIDAK diambil
            ->get();
    }

    public function saveAnswer(int $questionId, string $option): void
    {
        $this->answers[$questionId] = $option;
        // simpan parsial supaya tidak hilang kalau koneksi putus
        $this->attempt->answers()->updateOrCreate(
            ['question_id' => $questionId],
            ['selected_option' => $option],
        );
    }
}
```

Aturan keras:
- `answer_key` tidak pernah masuk properti komponen. Properti Livewire terkirim
  ke browser dan bisa dibaca.
- Timer di browser hanya tampilan. Kebenaran waktu ada di server saat submit.
- Simpan jawaban parsial setiap kali murid memilih. Koneksi internet murid sering
  tidak stabil.

## Filament

- Satu Resource per model utama. Jangan bikin Resource untuk tabel pivot.
- Batasi akses panel:

```php
public function canAccessPanel(Panel $panel): bool
{
    return in_array($this->role, [Role::Admin, Role::Guru], true);
}
```

- Label kolom dan form dalam Bahasa Indonesia.
- Untuk aksi yang berat (generate soal, reset season), pakai `Action` yang
  men-dispatch Job, lalu tampilkan notifikasi "sedang diproses". Jangan blokir
  panel menunggu selesai.

## Tailwind

- Pakai utility class langsung. Jangan bikin lapisan `@apply` yang menyembunyikan
  apa yang terjadi.
- Ekstrak ke komponen Blade (`<x-ui.button>`) kalau pola muncul tiga kali.
- Mobile-first. Sebagian besar murid membuka dari HP.
- Target ukuran sentuh minimal 44×44 px untuk opsi jawaban — ini soal akurasi,
  bukan estetika.

## Aksesibilitas & kejelasan

- Setiap input punya `<label>`.
- Pesan error muncul di dekat inputnya, bukan hanya di atas halaman.
- Kontras teks minimal AA.
- Bahasa sederhana. "Ujian belum dimulai", bukan "Status ujian tidak valid".

## Yang harus dihindari

- Jangan pakai `wire:poll` di halaman ujian. 150 murid × polling = beban sia-sia.
  Pakai penyimpanan saat aksi.
- Jangan render daftar panjang tanpa paginasi.
- Jangan andalkan menyembunyikan tombol sebagai otorisasi. Server tetap harus menolak.
