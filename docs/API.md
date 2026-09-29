# Kontrak API SIKAP 360

Endpoint relatif: `api.php?action=NAMA_AKSI`. Semua permintaan POST, Content-Type `application/json`, cookie sesi SIKAPSESSID. Mutasi selain login wajib header `X-CSRF-Token` dari bootstrap. Origin harus cocok dengan APP_URL. API tidak membuka CORS.

| Aksi | Peran | Isi JSON |
| --- | --- | --- |
| login | Publik | username (NIP atau email; berisi "@" dicari sebagai email, selain itu sebagai NIP dengan spasi diabaikan; kunci lama email tetap diterima), password |
| info | Publik | objek kosong; mengembalikan kabupaten_name |
| bootstrap | ASN/admin | period_id opsional |
| logout | ASN/admin | objek kosong |
| password_change | ASN/admin | current_password, password |
| save_draft | Pemilik tugas | id, version, answers objek `{1:4,2:5}`, feedback |
| submit | Pemilik tugas | period_id, ids array ID tugas |
| employee_save | Admin / Admin OPD | id opsional, name, nip, position, opd_id (admin OPD: diabaikan, selalu OPD-nya), unit, grade, email, password (12–128 karakter; pegawai baru: kosong = NIP, edit: kosong = tidak diganti), supervisor_id opsional, role opsional (asn/admin_opd/admin; hanya admin kabupaten) |
| period_create | Admin kabupaten | year (2000–2100), quarter (1–4) |
| period_status | Admin kabupaten | period_id, status |
| assignment_create | Admin / Admin OPD | period_id, subject_id, rater_id, rater_role (keduanya satu OPD) |
| assignment_delete | Admin / Admin OPD | id |
| supervisor_set | Admin / Admin OPD | employee_id, supervisor_id (null atau 0 = tanpa atasan; harus satu OPD) |
| assignment_list | Admin / Admin OPD | period_id, opd_id opsional (admin kabupaten; admin OPD: diabaikan), role, status, q (nama pegawai atau penilai), page. Mengembalikan rows (10 per halaman: id, subject_id, rater_id, rater_role, status, opd_id, opd_code, subject_name, rater_name), total, page, pages, per, stats {total, submitted} |
| assignment_generate | Admin / Admin OPD | period_id (periode harus draft), opd_id opsional (admin kabupaten: batasi ke satu OPD; admin OPD: diabaikan) |
| opd_save | Admin kabupaten | id opsional, name, code (2–20 huruf/angka, disimpan huruf besar), active |
| settings_save | Admin kabupaten | kabupaten_name |

Peran akun: `admin` (administrator kabupaten: semua OPD, periode, OPD, pengaturan), `admin_opd` (administrator satu OPD: pegawai, struktur, dan pasangan penilai OPD-nya sendiri; aksi kabupaten → 403; pegawai di luar OPD-nya → 403), `asn`. Satu instalasi = satu kabupaten (nama di tabel settings, default Hulu Sungai Selatan) dengan banyak OPD; periode berlaku serentak untuk seluruh OPD.

bootstrap mengembalikan user (termasuk opd_id, opd_name, opd_code), settings, opds (dengan employee_count), periods, period, indicators, tasks, result, csrf. employees (admin) memuat supervisor_id, atasan langsung; rekan sejawat (pejabat: sesama pejabat seatasan; staf: staf lain satu unit dalam OPD yang sama) dan bawahan langsung diturunkan klien dari kolom ini bersama unit. Tanpa period_id (atau period_id tidak dikenal), periode default adalah periode berstatus open dengan tanggal mulai terbaru; bila tidak ada, berturut-turut closed, published, lalu draft. employees hanya berisi data untuk admin; untuk admin OPD dibatasi ke OPD-nya. Daftar penugasan tidak disertakan pada bootstrap (jumlahnya dapat ratusan ribu); ambil lewat assignment_list, yang menyaring admin OPD berdasarkan OPD pegawai yang dinilai. Setiap baris employees memuat opd_id, opd_name, opd_code, role. Jawaban tasks hanya milik penilai yang sedang login. result hanya untuk pegawai yang sedang login. Sesi berlaku dengan batas tidak aktif 30 menit. Penggantian kata sandi membatalkan sesi lain melalui auth_version.

supervisor_set dan employee_save menolak atasan diri sendiri (422), atasan tidak aktif/tidak ada (404), dan struktur melingkar (409). assignment_generate mengembalikan created, skipped, warnings (diawali kode OPD): tiap pegawai aktif yang punya atasan mendapat penilai atasan; rekan bagi pejabat (pegawai yang punya bawahan aktif) adalah sesama pejabat dengan atasan yang sama, dan bagi staf adalah staf lain satu unit (UNOR: opd_id + unit, tanpa membedakan huruf besar); bawahan langsung menjadi bawahan hanya bila kelompok berjumlah minimal 3 (kelompok 1–2 dilewati dengan peringatan); pegawai yang hanya akan punya penilai atasan dilewati seluruhnya karena komposisi tidak sah; pasangan (period, subject, rater) yang sudah ada dilewati; pegawai tanpa atasan tidak dinilai. Menjalankan ulang bersifat idempoten.

save_draft mengembalikan version baru. Perbedaan version menghasilkan HTTP 409. Client wajib memuat ulang sebelum mengulang penyimpanan konflik. submit memvalidasi semua tugas lebih dahulu dan menyimpan dalam satu transaksi. Salah satu tugas tidak valid membatalkan seluruh batch.

Semua perubahan jawaban serta status periode mengunci baris periode terlebih dahulu, sehingga pengiriman dan penutupan periode tidak berjalan melampaui satu sama lain. Kunci tugas diambil dengan urutan ID untuk pengiriman batch.

period_create membuat satu triwulan kalender: nama (`Triwulan I 2027`), start_date, dan end_date diturunkan server dari year + quarter. Triwulan yang sudah ada menghasilkan 409.

Status periode: draft → open → closed → published. closed → open diizinkan sebelum publikasi. Rentang tanggal tetap membatasi penyimpanan dan pengiriman. published bersifat final di aplikasi versi ini.

Pembatasan login: 10 kegagalan per akun atau 100 kegagalan per alamat IP dalam 15 menit menghasilkan 429. Alamat IP diambil dari X-Forwarded-For hanya bila permintaan datang dari proxy yang terdaftar pada TRUSTED_PROXIES.

Kode respons: 200 berhasil, 400 JSON rusak, 401 sesi/login, 403 otorisasi/CSRF/origin, 404 data tidak ditemukan, 409 konflik, 413 ukuran, 415 tipe konten, 422 validasi, 429 pembatasan login, 503 database.
