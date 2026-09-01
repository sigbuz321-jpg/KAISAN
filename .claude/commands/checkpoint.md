---
description: Simpan state pekerjaan sebelum compact konteks
---

Buat checkpoint sesi ini. Tulis ke `.claude/state/checkpoint.md` (timpa file lama):

```markdown
# Checkpoint — <tanggal & jam>

## Modul yang dikerjakan
## Yang sudah selesai
## Yang sedang dikerjakan (posisi persisnya)
## Langkah berikutnya
1.
## Keputusan yang diambil sesi ini
## Hal yang masih menggantung / butuh keputusan developer
## Status verifikasi terakhir
lint: | analyse: | test:
```

Sertakan path file konkret, bukan deskripsi samar. Checkpoint ini harus cukup
bagi sesi baru untuk melanjutkan tanpa membaca ulang seluruh kode.

Setelah menulis, sarankan ke developer untuk menjalankan `/compact`.
