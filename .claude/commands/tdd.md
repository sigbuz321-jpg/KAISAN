---
description: Jalankan siklus TDD untuk satu perilaku
argument-hint: [perilaku yang mau diimplementasikan]
---

Implementasikan dengan TDD: **$ARGUMENTS**

Delegasikan ke subagent `tdd-guide` dan jalankan siklusnya:

1. **MERAH** — tulis satu test yang gagal, jalankan, tempelkan output gagal
2. **HIJAU** — implementasi paling sederhana, jalankan, tempelkan output hijau
3. **REFACTOR** — rapikan, jalankan ulang

Ulangi per perilaku. Jangan tulis dua test sebelum yang pertama hijau.

Selesai kalau: `composer test` hijau dan perilaku yang diminta tercakup.
