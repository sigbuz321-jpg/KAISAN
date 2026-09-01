---
name: verification-loop
description: Loop verifikasi berkelanjutan — cara membuktikan pekerjaan benar-benar selesai, bukan sekadar terlihat selesai. Gunakan sebelum menyatakan tugas apa pun selesai.
---

# Verification Loop

## Masalah yang dipecahkan skill ini

Kegagalan paling umum bukan kode yang salah, melainkan **menyatakan selesai tanpa
bukti**. "Seharusnya jalan" bukan verifikasi.

## Aturan tunggal

> Jangan pernah melaporkan sesuatu selesai tanpa menempelkan output perintah yang
> membuktikannya.

Bukan ringkasan output. Output aslinya.

## Loop

```
1. Ubah kode
2. Jalankan verifikasi
3. Merah?  → perbaiki, kembali ke 1
   Hijau?  → lanjut
4. Ulangi sampai seluruh tugas selesai
5. Jalankan verifikasi penuh sekali lagi
6. Baru laporkan
```

Verifikasi dijalankan setelah **setiap** perubahan bermakna, bukan sekali di akhir.
Kalau kamu mengubah delapan file lalu baru menjalankan test, kamu kehilangan
informasi tentang perubahan mana yang merusak apa.

## Perintah verifikasi

```bash
composer lint       # Pint --test
composer analyse    # PHPStan level 6
composer test       # Pest
npm run build       # Vite
```

Cepat (jalankan sering): `composer test -- --filter=NamaTest`
Penuh (sebelum melapor): keempatnya.

## Tingkat bukti

| Klaim | Bukti yang dibutuhkan |
|---|---|
| "Test lolos" | output Pest |
| "Tidak ada error tipe" | output PHPStan |
| "Fitur berfungsi" | test fitur yang menjalankannya |
| "Query efisien" | jumlah query dari Debugbar/`DB::listen` |
| "Migration aman" | `migrate:fresh --seed` berhasil |
| "Tidak ada regresi" | seluruh suite hijau, bukan hanya test baru |

## Yang bukan verifikasi

- Membaca ulang kode sendiri dan merasa yakin
- Menjalankan hanya test yang baru ditulis
- "Perubahannya kecil, tidak mungkin merusak"
- Menonaktifkan test yang gagal
- Menambah `@phpstan-ignore` supaya analisis lewat

## Ketika verifikasi gagal berulang

Setelah tiga percobaan perbaikan yang gagal, berhenti. Laporkan:

```
Sudah dicoba:
1. <hipotesis> → <hasil>
2. <hipotesis> → <hasil>
3. <hipotesis> → <hasil>

Error terakhir:
<output>

Butuh keputusan developer.
```

Terus menebak setelah tiga kegagalan hampir selalu memperburuk keadaan.
