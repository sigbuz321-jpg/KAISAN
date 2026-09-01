---
description: Diagnosis dan perbaiki build/test/migration yang gagal
argument-hint: [opsional: perintah yang gagal]
---

Perbaiki kegagalan build. $ARGUMENTS

1. Jalankan verifikasi untuk melihat kegagalannya:
   `composer test`, `composer lint`, `composer analyse`, `npm run build`
2. Delegasikan ke subagent `build-error-resolver`
3. Terapkan perbaikan akar masalah — bukan menekan gejala
4. Jalankan ulang seluruh verifikasi dan tempelkan hasilnya

Kalau setelah tiga hipotesis masih gagal, berhenti dan laporkan apa yang sudah
dicoba. Jangan menonaktifkan test atau aturan analisis statis untuk membuatnya hijau.
