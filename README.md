# KAISAN

Platform bimbel digital untuk KAISAN Bimbel. Bank soal dengan bantuan AI,
latihan adaptif, ujian terjadwal, dan peringkat per season.

## Stack

Laravel 12 · Livewire 3 · Filament 4 · PostgreSQL 17 · Redis 7 · Tailwind 4 · Pest 3

## Mulai (development)

```bash
git clone https://github.com/sigbuz321-jpg/KAISAN.git
cd KAISAN
cp .env.example .env
docker compose up -d --build
docker compose exec -u www-data app composer install
docker compose exec -u www-data app php artisan key:generate
docker compose exec -u www-data app php artisan migrate
docker compose exec -u www-data app npm ci
docker compose exec -u www-data app npm run build
```

Buka `http://127.0.0.1:8080`.

Pada server dev, port hanya terbuka di localhost. Akses dari laptop lewat
terowongan SSH: `ssh -L 8080:127.0.0.1:8080 kaisan-vps`.

Dua hal yang wajar terjadi dan bukan kerusakan:

- `queue` dan `scheduler` akan restart berulang sampai `composer install` selesai —
  keduanya butuh `vendor/`. Setelah itu keduanya pulih sendiri.
- `-u www-data` bukan hiasan. Tanpa itu perintah berjalan sebagai root dan file yang
  dibuat di dalam container jadi milik root di host.

Belum ada seeder demo. Akun pertama dibuat di M1.

## Verifikasi

```bash
docker compose exec -u www-data app composer lint      # Laravel Pint
docker compose exec -u www-data app composer analyse   # PHPStan level 6
docker compose exec -u www-data app composer test      # Pest
docker compose exec -u www-data app npm run build      # Vite
```

Keempatnya harus hijau sebelum commit.

Test berjalan di PostgreSQL (`kaisan_test`), bukan SQLite — skema di
`docs/03-DATABASE.md` memakai JSONB, window function, dan CHECK constraint yang
tidak bisa ditiru SQLite. Database itu dibuat otomatis saat volume PostgreSQL
pertama kali dibentuk.

## Bekerja dengan Claude Code

Repo ini sudah dikonfigurasi untuk Claude Code. Mulai dengan:

```bash
claude
```

Claude akan membaca `CLAUDE.md` dan seluruh `.claude/rules/` otomatis.

### Slash command

| Perintah | Fungsi |
|---|---|
| `/plan <fitur>` | Rencana implementasi sebelum kode ditulis |
| `/tdd <perilaku>` | Siklus TDD merah-hijau-refactor |
| `/code-review` | Review kualitas + keamanan sebelum PR |
| `/build-fix` | Diagnosis build/test yang gagal |
| `/verify` | Loop verifikasi penuh |
| `/checkpoint` | Simpan state sebelum compact konteks |
| `/learn` | Ekstrak pola yang dipelajari ke rules |
| `/handover` | Cek kesiapan serah terima ke klien |

### Alur yang disarankan

```
/plan M4 ujian terjadwal      → setujui rencananya
/tdd validasi jendela waktu   → siklus TDD per perilaku
/verify                       → buktikan hijau
/code-review                  → perbaiki temuan
commit + PR
```

Jangan lompat langsung ke kode. Rencana dulu — itu inti dari setup ini.

## Struktur

```
.claude/
  rules/      aturan yang selalu berlaku
  agents/     subagent khusus (planner, architect, reviewer, ...)
  commands/   slash command
  skills/     pengetahuan domain & pola
docs/         PRD, arsitektur, skema DB, roadmap, deployment
  panduan/    panduan Bahasa Indonesia untuk klien (dibuat di M7)
```

## Dokumentasi

Baca berurutan: `docs/01-PRD.md` → `02-ARCHITECTURE.md` → `03-DATABASE.md`
→ `04-ROADMAP.md`.

## Lisensi

Proprietary. Dikembangkan untuk KAISAN Bimbel dengan skema jual putus.
