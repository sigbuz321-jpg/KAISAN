---
description: Review kualitas kode sebelum commit atau PR
---

Jalankan review kode pada perubahan saat ini.

1. `git status` dan `git diff main...HEAD --stat`
2. Delegasikan ke subagent `code-reviewer`
3. Kalau diff menyentuh auth, ujian, atau data murid — jalankan juga
   subagent `security-reviewer`
4. Tampilkan gabungan temuan, dikelompokkan: Pemblokir / Perlu diperbaiki / Saran
5. Tanyakan ke developer: perbaiki sekarang atau catat sebagai isu?

Jangan commit apa pun dalam perintah ini.
