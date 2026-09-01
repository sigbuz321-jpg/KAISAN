# Rule: Kapan Mendelegasikan ke Subagent

Subagent punya konteks sendiri. Pakai mereka supaya konteks utama tidak penuh oleh
detail yang tidak perlu diingat.

| Situasi | Subagent |
|---|---|
| Fitur baru yang menyentuh >3 file | `planner` |
| Keputusan struktural (skema DB, batas modul) | `architect` |
| Menulis logika bisnis inti | `tdd-guide` |
| Sebelum membuka PR | `code-reviewer` |
| Menyentuh auth, ujian, atau data murid | `security-reviewer` |
| Build/test merah dan penyebabnya tidak jelas | `build-error-resolver` |
| Setelah modul selesai | `doc-updater` |

## Aturan

- **Rencanakan dulu, baru kode.** Untuk apa pun yang lebih besar dari perbaikan
  sepele, jalankan `planner` dan tunjukkan rencananya ke developer sebelum menulis kode.
- Jangan delegasikan tugas satu file. Overhead-nya lebih mahal dari manfaatnya.
- Subagent tidak boleh melakukan commit. Mereka mengembalikan hasil; developer
  atau sesi utama yang memutuskan.
- Kalau dua subagent memberi saran bertentangan, jangan diam-diam pilih salah satu.
  Sampaikan konfliknya ke developer.

## Batasan

Subagent tetap tunduk pada semua rule di direktori ini. `security-reviewer`
tidak bisa memberi izin melanggar `security.md`.
