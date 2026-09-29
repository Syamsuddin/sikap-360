# Spesifikasi Aturan Bisnis — sumber SATU-SATUNYA nilai harapan test

Sumber resmi: docs/API.md, docs/VALIDASI.md (uji penerimaan 1–19), README.md, tests/scoring.php, komentar kode.
Test hanya memakai baris DISAHKAN. Baris tanpa sumber = [PERLU KEPUTUSAN].

| ID | Aturan | Input | Harapan | Sumber | Status |
|---|---|---|---|---|---|
| R-01 | Bobot komposisi atasan+rekan+bawahan = 60/25/15 | 3 kelompok | 60,25,15 | tests/scoring.php, README | DISAHKAN |
| R-02 | Bobot atasan+rekan = 75/25; atasan+bawahan = 85/15 | 2 kelompok | 75/25; 85/15 | tests/scoring.php | DISAHKAN |
| R-03 | Tepat 1 atasan; rekan/bawahan (jika ada) minimal 1 orang | komposisi | DomainException | Scoring::validateComposition, VALIDASI #13 | DISAHKAN |
| R-04 | Skor 1–5 bilangan bulat; indikator harus dikenal (1–7) | 0,6,4.5,id 8 | HTTP 422, data tak berubah | VALIDASI #6 | DISAHKAN |
| R-05 | Submit wajib 7 jawaban lengkap | 6 jawaban | seluruh batch ditolak | VALIDASI #7, #11 | DISAHKAN |
| R-06 | Konversi skor: mean tertimbang ×20, rentang 20–100 | semua 1 / semua 5 | 20 / 100 | tests/scoring.php | DISAHKAN |
| R-07 | ASN tidak boleh employee_save/period_status/assignment_create/… | role asn | HTTP 403 | VALIDASI #2, API.md | DISAHKAN |
| R-08 | save_draft ID tugas orang lain ditolak, data pemilik tak berubah | id milik user lain | 404 (kode) / "ditolak" (spec) | VALIDASI #3, Api.php:77 | DISAHKAN |
| R-09 | Mutasi tanpa/dengan CSRF salah | tanpa header | HTTP 403 | VALIDASI #4 | DISAHKAN |
| R-10 | Tugas submitted terkunci; submit ulang ditolak | submit 2x | HTTP 409 | VALIDASI #8, #9 | DISAHKAN |
| R-11 | Versi draf berbeda ditolak (optimistic lock) | version lama | HTTP 409 | VALIDASI #10, API.md | DISAHKAN |
| R-12 | Periode closed/di luar tanggal menolak simpan/kirim | closed | HTTP 409 | VALIDASI #12, Api::openPeriod | DISAHKAN |
| R-13 | Transisi status: draft→open→closed→published; closed→open boleh; published final | transisi lain | HTTP 409 | API.md | DISAHKAN |
| R-14 | Publikasi ditolak bila ada tugas belum submitted | 1 pending | HTTP 409 | VALIDASI #14 | DISAHKAN |
| R-15 | Bootstrap ASN tidak memuat password_hash, jawaban ASN lain, daftar pegawai | role asn | tidak ada field tsb | VALIDASI #16, API.md | DISAHKAN |
| R-16 | Hasil (score) hanya tampil jika complete dan (published atau admin) | open, asn | score null | Api::bootstrap | DISAHKAN |
| R-17 | Ganti kata sandi membatalkan sesi lain (auth_version) | 2 sesi | sesi lama 401 | VALIDASI #17, API.md | DISAHKAN |
| R-18 | Login gagal ≥10x/15 menit per akun, atau ≥100x per IP (IP dari XFF hanya via TRUSTED_PROXIES) → 429 | 10 percobaan | HTTP 429 | Api::login, docs/API.md | DISAHKAN |
| R-19 | Origin tak cocok APP_URL → 403; non-POST → 405; non-JSON → 415; >64KB → 413 | header | kode tsb | API.md, api.php | DISAHKAN |
| R-20 | Tidak ada respons 500 dan tidak ada pesan SQL/stack bocor | input sampah | ≠500, body JSON rapi | invarian universal | DISAHKAN |
| R-21 | Distribusi penilai (assignment create/delete) hanya saat periode draft | open | HTTP 409 | Api.php:136,141 | DISAHKAN |
| R-22 | NIP 18 digit (atau DEMO-xxxx); password 12–128 | nip 5 digit | HTTP 422 | Api::saveEmployee | DISAHKAN |
| R-23 | Pegawai nonaktif (active=0) tidak bisa login dan sesi aktifnya putus | active=0 | 401 | Api::auth, Api::login | DISAHKAN |
| R-24 | Publikasi periode saat ada subject dengan komposisi tak valid tapi semua submitted | — | ? | — | [PERLU KEPUTUSAN] |
| R-25 | Periode default bootstrap: open → closed → published → draft (start_date terbaru) | tanpa period_id | periode open | docs/API.md | DISAHKAN |
