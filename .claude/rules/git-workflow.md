# Rule: Git Workflow

## Branch

- `main` — selalu bisa di-deploy.
- `feat/<modul>-<ringkas>` — mis. `feat/exam-scheduling`
- `fix/<ringkas>`
- `chore/<ringkas>`

Satu branch = satu modul dari `docs/04-ROADMAP.md`. Jangan campur dua modul.

## Format commit

Conventional Commits, Bahasa Inggris:

```
feat(exam): add scheduled exam window validation

Server now derives the deadline from started_at + duration instead of
trusting the client timer. Adds regression test for late submission.

Refs: ROADMAP M4
```

Tipe: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `perf`.

## Aturan

- Commit kecil dan sering. Satu commit = satu perubahan yang masuk akal.
- Jangan commit kode yang testnya gagal.
- Jangan commit `.env`, `vendor/`, `node_modules/`, `storage/*.sqlite`,
  atau dump database berisi data murid.
- Jangan `git push --force` ke `main`.
- Jangan lakukan operasi git destruktif (`reset --hard`, `clean -fd`, rebase pada
  branch yang sudah di-push) tanpa konfirmasi developer lebih dulu.

## Sebelum push

```bash
composer lint && composer test
```

## Pull request

Solo developer, tapi tetap buat PR — ini jadi jejak audit untuk serah terima.
Deskripsi PR minimal berisi:

1. Modul mana dari roadmap
2. Apa yang berubah
3. Bagaimana cara mengetesnya secara manual
4. Screenshot kalau ada perubahan tampilan

## Tag rilis

Setiap modul selesai dan diterima klien: `git tag v0.<modul>.0`.
Ini penting untuk klaim "revisi 2x per modul" di kontrak — tag menandai
titik penerimaan.
