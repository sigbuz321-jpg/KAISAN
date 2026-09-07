# Onboarding Developer — KAISAN

Untuk developer yang melanjutkan proyek ini. Baca `CLAUDE.md` dan
`.claude/rules/` lebih dulu; dokumen ini hanya soal cara masuk, menjalankan,
dan men-deploy.

---

## 1. Kondisi repo hari ini — baca ini sebelum clone

**`main` BUKAN yang berjalan di server.** Jangan mulai dari `main`.

| Branch | Isi | Status |
|---|---|---|
| `main` | M0–M5 | tertinggal 34 commit |
| `feat/m6-leaderboard-core` | M6 leaderboard & musim | PR #16 masih terbuka |
| `feat/design-system` | M6 + design system + seluruh perbaikan bug | **inilah yang jalan di VPS dev** |

```bash
git clone git@github.com:sigbuz321-jpg/KAISAN.git
cd KAISAN
git checkout feat/design-system
```

`feat/design-system` dicabangkan dari `feat/m6-leaderboard-core`, bukan dari
`main`, karena halaman Peringkat hanya ada di sana. Urutan merge yang benar:
PR #16 ke `main` dulu, baru design system.

---

## 2. Akses yang dibutuhkan

### GitHub
Sudah ditambahkan sebagai collaborator.

### VPS dev

| | |
|---|---|
| Host | `103.93.132.127` |
| Port | `8022` (22 juga terbuka) |
| User | `sigbuz` |
| Auth | kunci publik saja — password login dimatikan |
| Path repo | `/home/sigbuz/kaisan-dev` |

**Cara mendapat akses.** Jangan pernah mengirim private key lewat chat atau
email. Developer baru membuat kuncinya sendiri, lalu kirim yang **publik**:

```bash
# di laptop developer baru
ssh-keygen -t ed25519 -C "nama@contoh.com" -f ~/.ssh/kaisan
cat ~/.ssh/kaisan.pub        # kirim isi baris ini ke pemilik VPS
```

Pemilik VPS memasangnya:

```bash
ssh kaisan-vps
echo "ssh-ed25519 AAAA... nama@contoh.com" >> ~/.ssh/authorized_keys
```

Lalu di `~/.ssh/config` developer baru:

```
Host kaisan-vps
    HostName 103.93.132.127
    User sigbuz
    Port 8022
    IdentityFile ~/.ssh/kaisan
    IdentitiesOnly yes
    ServerAliveInterval 30
```

Uji: `ssh kaisan-vps 'hostname'`

> **Catatan.** Kuncinya dipasang ke akun `sigbuz` yang sudah ada, bukan user
> Linux baru. Alasannya teknis: container memetakan `www-data` ke UID 1000, dan
> repo di-bind-mount, jadi user dengan UID lain akan bentrok kepemilikan file
> pada `vendor/` dan `node_modules/`. Konsekuensinya, jejak audit per orang
> hilang dan akun itu punya sudo tanpa password. Untuk server dev berdua ini
> dianggap cukup; untuk server produksi klien, buat user terpisah.

---

## 3. Menjalankan di lokal

Butuh Docker saja. PHP, Composer, dan Node berjalan di dalam container.

```bash
cp .env.example .env          # isi AI_ROUTER_* kalau perlu pakai fitur AI
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app npm ci && docker compose exec app npm run build
docker compose exec app php artisan migrate --seed
```

Buka `http://127.0.0.1:8080`. Akun contoh dibuat `AccountSeeder`
(`database/seeders/AccountSeeder.php`) — lihat di sana untuk email dan
kata sandinya.

---

## 4. Deploy ke VPS dev

Repo di server adalah working copy biasa. Deploy = pull lalu bangun ulang.

```bash
ssh kaisan-vps
cd /home/sigbuz/kaisan-dev
git pull --ff-only

# 1. PHP dulu. panel.css mengimpor theme.css milik Filament dari vendor/,
#    jadi npm yang jalan duluan akan gagal build.
docker compose exec -T app composer install

# 2. Aset. Wajib setiap kali CSS/Blade berubah — design system seluruhnya
#    token CSS, tanpa langkah ini tampilannya polos.
docker compose exec -T app npm ci
docker compose exec -T app npm run build

# 3. Migrasi kalau ada
docker compose exec -T app php artisan migrate --force

# 4. Cache produksi
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

docker compose restart -t 30 app queue scheduler
```

Urutannya penting. Langkah 1 sebelum 2, dan `optimize:clear` sebelum
tiga `*:cache`.

---

## 5. Verifikasi — jalankan sebelum bilang selesai

```bash
docker compose exec -T app php artisan config:clear    # WAJIB, lihat jebakan #1
docker compose exec -T app composer lint
docker compose exec -T app composer analyse
docker compose exec -T app composer test
```

Patokan saat dokumen ini ditulis: lint PASS 248 file, analyse no errors,
test 459 passed. Kalau ada yang merah, pekerjaan belum selesai
(`.claude/rules/testing.md`).

Setelah selesai menguji, pasang lagi `config:cache` supaya situs kembali ke
mode produksi.

---

## 6. Jebakan yang sudah memakan waktu — jangan terperangkap dua kali

**1. `config:cache` membuat test menyerang database dev.**
`phpunit.xml` menetapkan `DB_DATABASE=kaisan_test`, tetapi config yang sudah
di-cache mengalahkan `<env>` PHPUnit. Selalu `config:clear` sebelum `composer test`.

**2. Default kolom wajib dicerminkan di `$attributes` model.**
Sudah menggigit **empat kali**. Kolom dengan `default()` di migration harus
ditulis ulang di `$attributes`, kalau tidak model yang baru dibuat melaporkan
`null`. Dijaga `tests/Unit/ModelDefaultsTest.php`. Baca
`.claude/rules/coding-style.md`.

**3. Halaman Livewire full-page butuh `$slot`, bukan `@yield`.**
`layouts/app.blade.php` melayani dua jalur render. Kalau ditulis ulang, jangan
hilangkan cabang `@isset($slot)` — dulu ini membuat layar latihan dan ujian
blank total selama dua modul tanpa ketahuan.

**4. `wire:model` deferred pada kontrol yang disembunyikan.**
Radio jawaban itu `sr-only` dan gaya "terpilih" dirender server. Binding
deferred membuat pilihan tidak pernah sampai ke server. Pakai `.live` di sana.

**5. Jangan pakai `wire:poll` di halaman ujian.**
Dilarang `.claude/rules/performance.md`: 150 murid × polling = beban sia-sia.
Timer memakai Alpine di klien; server tetap pemegang kebenaran waktu.

**6. Filament menimpa `X-Forwarded-Proto`.**
`docker/caddy/Caddyfile` punya `trusted_proxies static private_ranges`. Tanpa
itu Laravel membuat URL `http://` di halaman `https://` dan browser memblokir
aset.

**7. Tipe properti Filament harus sama persis dengan induknya.**
`$navigationGroup` itu `UnitEnum|string|null`, bukan `?string`. Salah tipe =
fatal error saat panel boot.

---

## 7. Peta lingkungan

| | |
|---|---|
| URL dev | https://kaisan-dev-103-93-132-127.nip.io |
| URL lama | `kaisan.103-93-132-127.nip.io` → 301 ke URL dev |
| Panel guru | `/guru` |
| Panel admin | `/admin` |
| Halaman murid | `/masuk`, `/latihan`, `/ujian`, `/peringkat` |

Stack dev terikat ke `127.0.0.1:8080`. Yang menghadap internet adalah **Caddy
sistem** di `/etc/caddy/Caddyfile` (di luar repo, perlu sudo). Layanan lain ikut
menumpang di sana — jangan sentuh blok vhost milik orang lain. Backup otomatis
tersimpan sebagai `/etc/caddy/Caddyfile.bak.*`.

Tanpa akses publik, pakai terowongan SSH:

```bash
ssh -L 8081:127.0.0.1:8080 kaisan-vps      # lalu buka http://localhost:8081
```

Port 8080 di laptop sering sudah dipakai layanan lain; pakai 8081.

`.env` server **tidak** dilacak git dan berisi kunci AI. Jangan salin ke repo.
`.env.example` hanya cetak biru — nilainya wajib kosong.

---

## 8. Yang masih terbuka

1. **Kunci AI lama bocor di repo publik** (`sk-c9f…`, commit `7ccff05`). Belum
   dicabut, riwayat belum dibersihkan. **Bersihkan riwayat sebelum merge ke
   `main`**, kalau tidak kuncinya ikut menyebar ke riwayat `main`.
2. **PR #16 (M6)** masih menunggu penerimaan.
3. **`main` tertinggal 34 commit.**
4. **Kata sandi akun contoh ada di repo publik.** Siapa pun yang menemukan URL
   dev bisa masuk sebagai admin. Ganti sebelum menyebarkan link lebih luas.
5. **M7 Serah Terima** belum mulai (`docs/04-ROADMAP.md`).
