# Design System KAISAN

Sumber kebenaran untuk tampilan aplikasi: halaman murid dan panel guru/admin.
Folder ini berisi spesifikasi desain (4 tahap) dan kit komponen statis yang bisa
dibuka langsung di browser tanpa build.

Dokumen pendamping: [`../DESIGN-CONTEXT.md`](../DESIGN-CONTEXT.md) — inventaris
layar dan batasan teknis yang dipakai sebagai masukan saat desain ini dibuat.

---

## Isi folder

| Berkas | Isi | Buka kalau ingin tahu |
|---|---|---|
| `kaisan-tahap-1-fondasi.html` | Palet, tipografi, jarak, radius, bayangan, aturan ikon & animasi | Nilai token dan alasannya |
| `kaisan-tahap-2-komponen.html` | 16 komponen inti, lengkap dengan matriks varian × state | Bentuk dan state sebuah komponen |
| `kaisan-tahap-3-layar.html` | 4 layar murid prioritas + catatan implementasi | Susunan layar murid |
| `kaisan-tahap-4-panel-guru.html` | Palet, navigasi, dan widget dasbor panel guru | Rencana panel guru |
| `colors_and_type.css` | Lapisan token untuk kit statis di `app/` | — (dipakai oleh kit) |
| `app/` | Kit komponen statis, satu berkas per komponen | Contoh markup yang bisa disalin |
| `app/index.html` | Peluncur kit — buka ini dulu | Semua komponen dalam satu halaman |
| `DESIGN-HANDOFF.md` | Instruksi serah terima bawaan alat ekspor | Konteks proses, bukan aturan proyek |
| `DESIGN-MANIFEST.json` | Peta berkas bawaan alat ekspor | Konteks proses, bukan aturan proyek |

Urutan baca: **Tahap 1 → 2 → 3 → 4**. Setiap tahap mengandaikan tahap sebelumnya
sudah dibaca.

> `DESIGN-HANDOFF.md` dan `DESIGN-MANIFEST.json` dihasilkan otomatis oleh alat
> ekspor desain, bukan ditulis untuk proyek ini. Keduanya disimpan sebagai
> jejak, tapi **kalau isinya bertentangan dengan `.claude/rules/`, aturan proyek
> yang menang.** Lihat "Penyimpangan" di bawah.

---

## Status implementasi

Seluruh empat tahap sudah diterapkan di kode pada branch `feat/design-system`.

| Tahap | Hasil di repo |
|---|---|
| 1 · Fondasi | `resources/css/app.css` (blok `@theme`), `config/design.php`, `resources/views/components/icon/*` |
| 2 · Komponen | `resources/views/components/ui/*` (18 komponen Blade) |
| 3 · Layar murid | `resources/views/murid/*`, `resources/views/livewire/murid/*`, `resources/views/layouts/app.blade.php` |
| 4 · Panel guru | `app/Providers/Filament/GuruPanelProvider.php`, `app/Filament/Guru/Widgets/GuruOverview.php`, `resources/css/filament/panel.css` |

Satu warna primer dipakai bersama: `--color-accent` di CSS dan
`config('design.colors.primary')` (`#D97706`) di PHP untuk Filament. **Kalau
salah satu berubah, ubah keduanya di commit yang sama.**

---

## Penyimpangan dari paket desain

Paket desain ini ditulis dengan asumsi struktur repo yang berbeda. Yang berikut
sengaja **tidak** diikuti secara harfiah, karena repo sudah punya padanan yang
setara atau lebih baik, atau karena bertabrakan dengan `.claude/rules/`.

| Yang diminta dokumen | Yang dikerjakan | Alasan |
|---|---|---|
| Kolom baru `questions.cost_cents` + `is_ai_generated` (Opsi A, Tahap 4) | Tidak ada migration | Tabel `ai_generation_jobs` sudah mencatat `estimated_cost` per job — ini justru "Opsi B" yang sudah jadi, dan sudah jadi bukti tagihan ke klien |
| `config/ai.php` → `monthly_budget_idr` | Pakai `config('services.ai_router.monthly_budget')` | Sudah ada dan sudah dipakai halaman Biaya AI |
| `wire:poll.1s` untuk timer, `wire:poll.8s` untuk heartbeat (Tahap 3) | Timer Alpine di klien, simpan saat aksi | `.claude/rules/performance.md` melarang `wire:poll` di halaman ujian: 150 murid × polling = beban sia-sia. Kebenaran waktu tetap di server |
| Endpoint REST `/api/attempts/{id}/...` | Tetap lewat Action + Livewire | Tidak ada lapisan API di proyek ini |
| "Menunggu penilaian sampai guru menilai" | Skor dihitung server saat submit | Perilaku M4 yang sudah diterima; mengubahnya bukan urusan design system |
| Model `PracticeLog`, tabel `students`, `student_id` | `practice_sessions` / `practice_answers`, `users` role murid, `user_id` | Nama sebenarnya di `docs/03-DATABASE.md` |
| Resource `Grade` dan `Student` di panel guru | Tidak dibuat | Tidak ada di repo; nilai dibuka dari halaman Nilai per ujian, murid dikelola admin lewat `UserResource` |
| Menu "Murid" untuk guru | Tidak dibuat | Butuh melonggarkan `UserPolicy`. Menyentuh otorisasi data anak di bawah umur — harus lewat `security-reviewer` dulu, bukan diselipkan di pekerjaan tampilan |
| Flag `seen_practice_disclaimer_at` di tabel `students` | Kalimat kontrak selalu tampil | Menambah kolom di luar cakupan; menampilkan terus lebih aman dan lebih murah |

Navigasi panel mengikuti **struktur** yang diminta Tahap 4 (dua grup, yang
paling sering dipakai di atas, badge antrian di Bank Soal, tanpa menu
tersembunyi) dengan resource yang benar-benar ada:

- **Akademik** — Dasbor, Bank Soal (badge), Jadwal Ujian, Permintaan Soal AI
- **Referensi** — Mata Pelajaran, Kelas, Musim
- **Pengguna** (admin saja) — Akun, Biaya AI

---

## Cara memakai kit statis

```
docs/06-DESIGN-SYSTEM/app/index.html
```

Buka berkas itu langsung di browser — tanpa server, tanpa build. `styles.css`
mengimpor `../colors_and_type.css`, jadi perubahan token langsung terlihat di
seluruh halaman spec.

Kelas di kit memakai awalan `k-*` dan hanya untuk halaman spec. **Kode produksi
tidak memakai kelas `k-*`**; produksi memakai utility Tailwind di atas token
yang sama, sesuai `.claude/rules/coding-style.md` ("pakai utility class
langsung, jangan bikin lapisan `@apply`").

---

## Aturan menyunting

1. Token hanya dideklarasikan di dua tempat: `resources/css/app.css` (produksi)
   dan `colors_and_type.css` (kit). Keduanya harus sama.
2. Jangan menambah warna di luar tujuh peran yang ada. Kalau butuh peran baru,
   bahas dulu — bukan tambah satu hex.
3. Jangan memuat font atau ikon dari CDN. System stack dan SVG inline, titik.
   Alasannya ada di Tahap 1: murid pakai HP kelas menengah di jaringan lambat.
4. Angka rating Elo tidak pernah tampil ke murid. Hanya band level
   (Pemula / Berkembang / Mahir / Ahli).
5. Dark mode sengaja tidak dibuat. Kalau nanti diminta, itu penambahan cakupan.
