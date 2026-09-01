---
description: Jalankan loop verifikasi penuh sebelum menyatakan pekerjaan selesai
---

Jalankan verifikasi lengkap. Tempelkan output nyata setiap perintah — jangan
meringkas, jangan menyimpulkan dari "seharusnya lolos".

```bash
composer lint
composer analyse
composer test
npm run build
php artisan migrate:fresh --seed --env=testing
```

Lalu periksa checklist ini terhadap perubahan saat ini:

- [ ] Semua model yang tersentuh punya Policy dan Policy-nya dipanggil
- [ ] Tidak ada `answer_key` yang bisa dijangkau murid saat ujian aktif
- [ ] Tidak ada panggilan AI router sinkron di jalur HTTP
- [ ] Tidak ada data pribadi murid di `Log::`
- [ ] String antarmuka ada di `lang/id/`, bukan hardcode
- [ ] Migration baru punya indeks untuk kolom filter/urut
- [ ] Coverage tidak turun di bawah 80%

Laporkan: **LULUS** atau daftar apa yang gagal. Jangan katakan selesai kalau ada
satu pun yang merah.
