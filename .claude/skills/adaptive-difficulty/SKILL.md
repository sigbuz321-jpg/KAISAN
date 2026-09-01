---
name: adaptive-difficulty
description: Logika latihan adaptif per mata pelajaran — model rating, pemilihan soal, dan aturan konvergensi. Gunakan saat menyentuh app/Services/Adaptive.
---

# Adaptive Difficulty

## Model yang dipakai: Elo yang disederhanakan

Setiap murid punya satu `rating` per mata pelajaran (bukan satu rating global —
murid bisa kuat di Matematika dan lemah di IPA). Setiap soal punya `difficulty`
dalam skala yang sama.

Nilai awal: **1200** untuk murid baru dan untuk soal baru.

## Rumus

Peluang murid menjawab benar:

```
E = 1 / (1 + 10^((difficulty - rating) / 400))
```

Setelah menjawab:

```
rating_baru = rating + K * (hasil - E)
```

`hasil` = 1 kalau benar, 0 kalau salah.

Nilai K menurun seiring bertambahnya jawaban — supaya rating stabil, tidak
berayun terus:

| Jumlah jawaban di mapel itu | K |
|---|---|
| < 20 | 40 |
| 20–59 | 24 |
| ≥ 60 | 16 |

**Kenapa Elo dan bukan IRT penuh:** IRT lebih akurat tapi butuh kalibrasi ratusan
respons per soal sebelum berguna. Bimbel ini punya 500 murid dan bank soal yang
terus berganti. Elo memberi 80% manfaat dengan 5% kerumitan, dan bisa dijelaskan
ke guru dalam satu kalimat.

## Pemilihan soal berikutnya

Target: peluang benar sekitar **70%**. Cukup menantang untuk belajar, tidak cukup
frustrasi untuk menyerah.

```
target_difficulty = rating - 150
```

Ambil soal dengan `difficulty` dalam rentang ±100 dari target, lalu:

1. Buang soal yang sudah dikerjakan murid dalam 30 hari terakhir
2. Buang soal berstatus selain `published`
3. Acak dari kandidat yang tersisa — jangan selalu ambil yang paling dekat,
   karena itu membuat latihan terasa berulang
4. Kalau kandidat kosong, lebarkan rentang ke ±250, lalu ±400
5. Kalau masih kosong, ambil soal acak dari mapel itu dan catat peringatan
   "bank soal tipis" untuk guru

## Update difficulty soal

Rating soal juga bergerak, tapi jauh lebih lambat (K = 8), dan hanya setelah soal
itu dijawab minimal 10 kali. Soal yang ternyata selalu dijawab benar akan naik
turun ke tingkat yang sebenarnya.

Soal dengan tingkat jawaban benar di bawah 15% setelah 20 percobaan ditandai untuk
ditinjau guru — kemungkinan soalnya salah atau ambigu, bukan sulit.

## Aturan bisnis

- **Latihan adaptif tidak memberi poin peringkat.** Hanya ujian yang memberi poin.
- Rating murid tidak pernah turun di bawah 400 atau naik di atas 2400.
- Rating diperbarui dalam transaksi yang sama dengan penyimpanan jawaban.
- Rating ditampilkan ke murid sebagai level (Pemula / Berkembang / Mahir / Ahli),
  bukan sebagai angka. Angka mentah membuat murid membandingkan diri secara tidak sehat.

## Testing — wajib 100% coverage

- Rating naik setelah jawaban benar terhadap soal yang lebih sulit
- Rating turun setelah jawaban salah terhadap soal yang lebih mudah
- Jawaban benar terhadap soal yang jauh lebih mudah hampir tidak menaikkan rating
- K mengecil setelah 20 dan 60 jawaban
- Rating berhenti di batas 400 dan 2400
- Pemilihan soal tidak mengulang soal yang baru dikerjakan
- Rentang melebar ketika kandidat kosong
- Bank soal kosong tidak menyebabkan error, hanya peringatan
