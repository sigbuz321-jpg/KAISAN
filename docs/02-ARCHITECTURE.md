# Arsitektur

## Bentuk umum

Monolith Laravel di satu VPS. Tidak ada layanan terpisah, tidak ada message broker
eksternal, tidak ada container orchestration.

```
Internet
   |
 Caddy (TLS otomatis, reverse proxy)
   |
 PHP-FPM  <---- Laravel (web + Livewire + Filament)
   |
   +--- PostgreSQL 17   (sumber kebenaran)
   +--- Redis 7         (cache, session, queue)
   |
 Horizon worker (3 proses) ---- AI Router (HTTP eksternal, milik developer)
```

Semua dijalankan lewat Docker Compose supaya serah terima ke klien sederhana:
satu `docker compose up -d`.

## Kenapa monolith

Beban puncaknya 150 pengguna bersamaan. Itu jauh di bawah titik di mana pemisahan
layanan memberi manfaat. Yang dibutuhkan proyek ini adalah kemudahan dirawat orang
lain setelah diserahkan, dan monolith unggul di situ.

## Kenapa PostgreSQL, bukan MySQL

1. `JSONB` untuk `questions.options` dan `ai_generation_jobs.meta` — bisa diindeks
   dan di-query, tidak sekadar disimpan.
2. Window function (`RANK() OVER`) membuat leaderboard jadi satu query alih-alih
   loop PHP atas ribuan baris.
3. MVCC: pembaca tidak memblokir penulis. Saat 150 murid submit bersamaan sementara
   guru membuka halaman nilai, ini terasa.
4. Batasan `CHECK` yang lebih kuat untuk menegakkan aturan bisnis di level database.

## Batas modul

```
Identitas       users, roles, classrooms
Bank soal       subjects, topics, questions, ai_generation_jobs
Latihan         practice_sessions, practice_answers, student_abilities
Ujian           exams, exam_questions, exam_attempts, attempt_answers
Peringkat       seasons, leaderboard_entries
```

Modul berkomunikasi lewat Action, bukan dengan saling memanggil model secara bebas.
Modul Peringkat hanya membaca dari modul Ujian, tidak pernah menulis ke sana.

## Alur data penting

### Generasi soal
```
Guru klik "Buat Soal"
  → ai_generation_jobs dibuat (status: queued)
  → GenerateQuestions job masuk Redis queue
  → Horizon worker memanggil AI Router
  → Validasi tiap soal
  → questions disimpan (status: review)
  → Guru dapat notifikasi
  → Guru menyetujui → status: published
```
Request HTTP guru selesai dalam < 200 ms. Sisanya asinkron.

### Pengerjaan ujian
```
Murid mulai
  → exam_attempts dibuat (started_at = server now)
  → soal dimuat sekali TANPA answer_key
  → tiap pilihan disimpan parsial ke attempt_answers
  → submit → server hitung skor → attempt.submitted_at
  → job leaderboard menjemputnya dalam ≤ 5 menit
```

### Leaderboard
```
Scheduled job tiap 5 menit
  → satu query agregasi dengan RANK()
  → tulis ke leaderboard_entries
  → cache Redis 60 detik
```
Halaman peringkat hanya membaca tabel hasil. Tidak pernah menghitung saat dibuka.

## Kegagalan yang direncanakan

| Kalau ini mati | Yang terjadi | Pemulihan |
|---|---|---|
| AI Router | Generasi soal gagal; ujian & latihan tetap jalan | Job retry 3x, guru dapat pesan jelas |
| Redis | Session hilang, queue berhenti | Data utuh di PG; restart Redis |
| Horizon worker | Job menumpuk, tidak hilang | Restart; job diproses dari antrean |
| PostgreSQL | Aplikasi berhenti | Restore dari backup harian |

AI Router adalah satu-satunya dependensi eksternal, dan sengaja ditempatkan di
jalur yang tidak kritis. Ujian tidak pernah bergantung padanya.

## Keputusan yang perlu ditinjau ulang kalau...

- Murid melewati 2000 → pertimbangkan read replica
- Bank soal melewati 100.000 → pertimbangkan pencarian full-text terpisah
- Klien minta multi-cabang → itu perubahan model bisnis, bukan perubahan teknis;
  bahas ulang kontrak
