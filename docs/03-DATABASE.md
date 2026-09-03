# Skema Database

PostgreSQL 17. Semua tabel punya `id bigserial`, `created_at`, `updated_at`.

## Identitas

### users
| Kolom | Tipe | Catatan |
|---|---|---|
| name | varchar(120) | |
| email | varchar(180) unique | |
| password | varchar | |
| role | enum: admin, guru, murid | |
| classroom_id | fk nullable | hanya untuk murid |
| is_active | boolean default true | nonaktif ≠ hapus |
| last_login_at | timestamp nullable | |

Indeks: `(role)`, `(classroom_id)`

### classrooms
`name`, `grade` (smallint), `academic_year` (varchar 9, mis. `2025/2026`)

## Bank soal

### subjects
`name`, `slug` unique, `is_active`

### topics
`subject_id` fk, `name`, `order` smallint
Indeks: `(subject_id, order)`

### questions
| Kolom | Tipe | Catatan |
|---|---|---|
| subject_id | fk | |
| topic_id | fk nullable | |
| stem | text | batang soal |
| options | jsonb | `{"A":"...","B":"...","C":"...","D":"..."}` |
| answer_key | char(1) | CHECK in (A,B,C,D) |
| explanation | text nullable | |
| difficulty | integer default 1200 | skala Elo |
| source | enum: ai, manual | |
| status | enum: draft, review, published, archived | |
| created_by | fk users | |
| approved_by | fk users nullable | |
| approved_at | timestamp nullable | |
| stem_hash | varchar(64) | deteksi duplikat |
| times_answered | integer default 0 | |
| times_correct | integer default 0 | |
| ai_meta | jsonb nullable | model, token, job id |

Indeks: `(subject_id, difficulty, status)`, `(status)`, `(subject_id, stem_hash)` unique

CHECK: `jsonb_array_length(jsonb_path_query_array(options, '$.*')) = 4`

### ai_generation_jobs
`requested_by` fk, `subject_id`, `topic_id` nullable, `difficulty`, `count`,
`status` (queued/running/done/failed), `model` varchar, `prompt_tokens` int,
`completion_tokens` int, `estimated_cost` numeric(12,4), `error` text nullable,
`finished_at` timestamp nullable, `meta` jsonb

Indeks: `(requested_by, created_at)`, `(status)`

## Latihan adaptif

### student_abilities
`user_id` fk, `subject_id` fk, `rating` integer default 1200,
`answers_count` integer default 0, `last_practiced_at` timestamp nullable

Indeks: `(user_id, subject_id)` **unique**

### practice_sessions
`user_id`, `subject_id`, `started_at`, `ended_at` nullable,
`questions_count`, `correct_count`

### practice_answers
`practice_session_id`, `question_id`, `selected_option` char(1),
`is_correct` boolean, `rating_before` int, `rating_after` int, `answered_at`

Indeks: `(practice_session_id)`, `(question_id)`

## Ujian

### exams
| Kolom | Tipe | Catatan |
|---|---|---|
| title | varchar(180) | |
| subject_id | fk | |
| season_id | fk | |
| created_by | fk users | |
| starts_at / ends_at | timestamp | |
| duration_minutes | smallint | |
| question_count | smallint | |
| difficulty_weight | numeric(3,2) default 1.00 | 1.00–2.00 |
| shuffle_questions | boolean default true | |
| shuffle_options | boolean default true | |
| status | enum: draft, scheduled, active, closed, graded | |

Indeks: `(status, starts_at)`, `(season_id, subject_id)`

CHECK: `ends_at > starts_at`

### exam_classroom
`exam_id`, `classroom_id`

Indeks: `(exam_id, classroom_id)` **unique**, `(classroom_id)`

Kelas mana yang ikut sebuah ujian. Murid hanya melihat dan bisa mengerjakan ujian
yang menyasar kelasnya. Ujian tanpa kelas peserta tidak bisa dijadwalkan, sama
seperti ujian tanpa soal.

Ditambahkan di M4. Tanpa ini setiap murid aktif melihat setiap ujian, dan sistem
tidak bisa tahu siapa yang seharusnya ikut sehingga "belum mengerjakan" mustahil
dilaporkan.

### classroom_teacher
`classroom_id`, `user_id`

Indeks: `(classroom_id, user_id)` **unique**, `(user_id)`

Guru yang mengampu sebuah kelas. Dipakai untuk menegakkan aturan di
`.claude/rules/security.md`: data murid dibatasi ke kelas yang diampu guru
tersebut, kecuali admin.

Many-to-many karena satu guru mengampu beberapa kelas, dan satu kelas diampu
beberapa guru untuk mata pelajaran berbeda.

### exam_questions
`exam_id`, `question_id`, `order` smallint
Indeks: `(exam_id, order)`, `(exam_id, question_id)` unique

### exam_attempts
`exam_id`, `user_id`, `started_at`, `submitted_at` nullable,
`score` numeric(5,2) nullable, `correct_count` smallint nullable,
`total_questions` smallint, `status` enum (in_progress/submitted/voided),
`voided_at` nullable, `voided_reason` text nullable, `reopened_by` fk nullable

Indeks: `(exam_id, user_id)` **unique**, `(user_id, submitted_at)`

### attempt_answers
`exam_attempt_id`, `question_id`, `selected_option` char(1) nullable,
`is_correct` boolean nullable, `answered_at`, `time_spent_ms` integer nullable

Indeks: `(exam_attempt_id)`, `(exam_attempt_id, question_id)` unique

## Peringkat

### seasons
`name` varchar(120), `starts_at`, `ends_at` nullable,
`is_active` boolean default false

Hanya satu season boleh `is_active = true`. Tegakkan dengan partial unique index:
```sql
CREATE UNIQUE INDEX one_active_season ON seasons (is_active) WHERE is_active;
```

### leaderboard_entries
`season_id`, `subject_id` nullable (null = peringkat gabungan),
`user_id`, `points` numeric(10,2), `rank` integer, `computed_at`

Indeks: `(season_id, subject_id, points DESC)`,
`(season_id, subject_id, user_id)` unique

## Aturan retensi

- `exam_attempts` dan `attempt_answers`: **permanen**. Tidak pernah dihapus.
  Pembatalan pakai `voided_at`.
- `practice_answers`: simpan 12 bulan, lalu arsipkan.
- `leaderboard_entries`: dikosongkan saat reset season, setelah snapshot disimpan.
- `ai_generation_jobs`: permanen — ini bukti biaya untuk klien.
