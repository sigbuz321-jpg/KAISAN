---
name: exam-leaderboard
description: Ujian terjadwal, skoring, leaderboard, dan season/reset. Gunakan saat menyentuh alur ujian atau perhitungan peringkat.
---

# Ujian, Skoring, dan Leaderboard

## Siklus hidup ujian

```
draft → scheduled → active → closed → graded
```

- `draft` — guru masih menyusun, belum terlihat murid
- `scheduled` — terlihat murid, tapi belum bisa dimulai
- `active` — `now()` berada di antara `starts_at` dan `ends_at`
- `closed` — jendela waktu lewat, tidak menerima submit baru
- `graded` — semua pengerjaan dinilai, poin masuk leaderboard

Transisi dijalankan oleh scheduled command tiap menit, bukan dihitung on-the-fly
di setiap request.

**Ujian yang sudah `active` tidak boleh diubah soalnya.** Kalau guru perlu revisi,
sistem membuat ujian baru.

## Waktu — sumber kebenaran adalah server

```php
public function deadlineFor(ExamAttempt $attempt): Carbon
{
    return min(
        $attempt->started_at->addMinutes($attempt->exam->duration_minutes),
        $attempt->exam->ends_at,
    );
}
```

Murid yang mulai 10 menit sebelum ujian ditutup hanya punya 10 menit, meskipun
durasi ujian 60 menit. Ini disengaja.

Toleransi submit terlambat: **30 detik**. Untuk latensi jaringan, bukan untuk
memberi waktu tambahan.

## Skoring

Dihitung di server saat submit, tidak pernah diterima dari klien.

```
skor = (jumlah_benar / jumlah_soal) * 100
```

Dibulatkan 2 desimal. Soal yang tidak dijawab dihitung salah.

Simpan juga `correct_count` dan `total_questions` — jangan hanya persentase.
Kalau nanti rumusnya berubah, data mentahnya masih ada.

## Poin leaderboard

```
poin = skor * bobot_kesulitan_ujian
```

`bobot_kesulitan_ujian` ditentukan guru saat membuat ujian (1.0 default,
1.0–2.0). Tanpa ini, ujian mudah dan ujian sulit memberi kontribusi sama —
dan murid akan menghindari ujian sulit.

Poin masuk leaderboard hanya dari `exam_attempts` yang `submitted` dan tidak
`voided`. Latihan adaptif tidak memberi poin.

## Perhitungan leaderboard

Satu query dengan window function, dijalankan oleh job terjadwal tiap 5 menit,
hasilnya ditulis ke `leaderboard_entries`.

```sql
INSERT INTO leaderboard_entries (season_id, subject_id, user_id, points, rank, computed_at)
SELECT
    :season_id,
    e.subject_id,
    a.user_id,
    SUM(a.score * e.difficulty_weight) AS points,
    RANK() OVER (
        PARTITION BY e.subject_id
        ORDER BY SUM(a.score * e.difficulty_weight) DESC
    ) AS rank,
    NOW()
FROM exam_attempts a
JOIN exams e ON e.id = a.exam_id
WHERE a.submitted_at IS NOT NULL
  AND a.voided_at IS NULL
  AND e.season_id = :season_id
GROUP BY e.subject_id, a.user_id;
```

Jangan hitung ini saat halaman dibuka. Dengan 500 murid × puluhan ujian, itu akan
menjadi query paling lambat di aplikasi.

Cache hasilnya di Redis 60 detik.

`RANK()` dipakai supaya nilai seri mendapat peringkat yang sama — ini sesuai
harapan orang dan menghindari pertanyaan orang tua.

## Season & reset

Season adalah periode peringkat. Reset dilakukan admin dari panel, tanpa terminal.

Yang terjadi saat reset:
1. Season aktif ditandai `ended_at = now()`, `is_active = false`
2. Snapshot peringkat akhir disimpan (untuk arsip/juara)
3. Season baru dibuat dan diaktifkan
4. `leaderboard_entries` season baru mulai kosong

**Yang TIDAK dihapus:** `exam_attempts`, `attempt_answers`, nilai, dan rating
adaptif murid. Riwayat akademik permanen. Reset hanya mengosongkan papan peringkat.

Ini aturan yang paling sering disalahpahami — beri komentar jelas di kodenya dan
konfirmasi ganda di panel sebelum reset dijalankan.

## Kasus tepi yang wajib diuji

- Submit tepat di detik terakhir → diterima
- Submit 31 detik setelah deadline → ditolak
- Dua tab terbuka, dua submit → hanya satu pengerjaan tersimpan
- Koneksi putus di tengah, murid buka lagi → jawaban sebelumnya masih ada,
  sisa waktu dihitung dari `started_at` server
- Ujian tanpa soal → tidak bisa dijadwalkan (validasi di pembuatan)
- Reset season → peringkat kosong, riwayat utuh
- Nilai seri → peringkat sama, peringkat berikutnya melompat
