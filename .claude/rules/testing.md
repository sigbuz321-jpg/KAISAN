# Rule: Testing

## TDD adalah default

Untuk logika bisnis (skoring, adaptive difficulty, leaderboard, aturan jadwal ujian):
tulis test dulu, lihat gagal, baru tulis implementasi. Tidak ada pengecualian untuk
empat area itu — di situlah bug paling mahal.

Untuk CRUD sederhana dan penyesuaian tampilan, test setelahnya boleh.

## Target

- Coverage minimum **80%** secara keseluruhan.
- Coverage **100%** untuk `app/Services/Scoring`, `app/Services/Adaptive`,
  dan job perhitungan leaderboard.
- Setiap laporan bug menghasilkan satu test regresi lebih dulu, baru diperbaiki.

## Jenis test

| Jenis | Kapan | Alat |
|---|---|---|
| Unit | rumus murni, tanpa DB | Pest |
| Feature | rute, Livewire, policy | Pest + RefreshDatabase |
| Browser | alur ujian ujung-ke-ujung | Pest Browser / Dusk |

## Yang wajib ada testnya

1. Murid tidak bisa melihat kunci jawaban selama ujian berlangsung.
2. Murid tidak bisa membuka hasil ujian murid lain.
3. Submit setelah waktu habis ditolak.
4. Skor dihitung ulang di server dan mengabaikan skor kiriman klien.
5. Reset season mengosongkan peringkat tanpa menghapus riwayat ujian.
6. Job generasi AI yang gagal tidak meninggalkan soal berstatus setengah jadi.
7. Rating adaptif naik setelah jawaban benar beruntun dan turun setelah salah.

## Aturan penulisan test

- Nama test deskriptif dalam Bahasa Inggris:
  `it('rejects submission after the exam window closes')`.
- Satu perilaku per test. Kalau butuh tiga assertion tentang tiga hal, buat tiga test.
- Pakai factory, jangan seeder, untuk setup test.
- Jangan mock apa yang kamu miliki sendiri. Mock hanya AI router (HTTP eksternal),
  pakai `Http::fake()`.
- Test tidak boleh bergantung pada urutan eksekusi atau waktu nyata. Bekukan waktu
  dengan `travelTo()`.

## Sebelum menyatakan selesai

Jalankan dan tempelkan hasilnya:

```bash
composer test
composer lint
composer analyse
```

Kalau ada yang merah, pekerjaan belum selesai. Jangan laporkan sukses berdasarkan
"seharusnya jalan".
