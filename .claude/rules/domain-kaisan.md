# Rule: Domain & Aturan Bisnis KAISAN

## Istilah

Gunakan istilah ini secara konsisten. Kolom kiri untuk kode (Inggris), kolom kanan
untuk antarmuka (Indonesia).

| Kode | Antarmuka | Arti |
|---|---|---|
| `subject` | Mata Pelajaran | Matematika, IPA, dst. |
| `topic` | Bab / Topik | sub-bagian mata pelajaran |
| `question` | Soal | pilihan ganda, 4 opsi |
| `exam` | Ujian | terjadwal, dinilai |
| `practice` | Latihan | adaptif, tidak dinilai untuk peringkat |
| `attempt` | Pengerjaan | satu kali murid mengerjakan ujian |
| `season` | Musim | periode peringkat, bisa di-reset |
| `classroom` | Kelas | rombongan belajar |
| `teacher` (role `guru`) | Guru | |
| `student` (role `murid`) | Murid | |

## Aturan bisnis yang tidak boleh dilanggar

1. **Soal AI selalu berstatus `review` dulu.** Tidak ada jalur yang membuat soal
   hasil AI langsung `published`. Guru harus menyetujui satu per satu atau massal.
2. **Satu murid satu pengerjaan per ujian**, kecuali guru secara eksplisit membuka
   ulang. Pembukaan ulang dicatat siapa yang melakukan dan kapan.
3. **Ujian yang sudah dimulai tidak boleh diubah soalnya.** Kalau guru perlu revisi,
   sistem membuat ujian baru.
4. **Latihan adaptif tidak mempengaruhi peringkat.** Hanya ujian yang memberi poin.
   Ini mencegah murid menggenjot peringkat lewat latihan berulang.
5. **Reset season menghapus peringkat, bukan riwayat.** `exam_attempts` dan nilai
   tetap ada selamanya; hanya `leaderboard_entries` yang dikosongkan dan season baru
   dibuat.
6. **Nilai tidak pernah dihapus.** Kalau perlu dibatalkan, tandai `voided_at` +
   alasan. Bimbel butuh jejak untuk menjelaskan ke orang tua.
7. Murid yang dinonaktifkan tidak hilang dari riwayat ujian yang sudah lewat.

## Konteks komersial yang mempengaruhi keputusan teknis

- **Jual putus, bukan SaaS.** Tidak ada multi-tenant, tidak ada billing, tidak ada
  langganan. Satu instalasi untuk satu bimbel.
- **Klien non-teknis.** Setiap fitur harus bisa dioperasikan dari panel, bukan dari
  terminal. Kalau sebuah fitur butuh `php artisan` untuk dipakai sehari-hari,
  desainnya salah.
- **Biaya AI ditanggung klien dan bersifat variabel.** Setiap job generasi mencatat
  perkiraan token dan biaya di `ai_generation_jobs`, dan admin bisa melihat
  rekapnya. Ini mencegah sengketa tagihan.
- **Revisi dibatasi 2x per modul di kontrak.** Kalau permintaan terdengar seperti
  fitur baru dan bukan perbaikan, tandai ke developer sebagai kemungkinan scope creep
  — jangan langsung kerjakan.
