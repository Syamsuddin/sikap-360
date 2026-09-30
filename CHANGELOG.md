# Changelog

Semua perubahan penting pada SIKAP 360 dicatat di berkas ini. Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/) dan penomoran versi mengikuti [Semantic Versioning](https://semver.org/lang/id/).

Instalasi yang dibuat pada versi lebih lama memerlukan migrasi database yang tercantum pada tiap versi (lihat `database/`). Instalasi baru cukup memakai `database/schema.sql`.

## [Belum dirilis]

Tidak ada migrasi database.

### Diperbaiki
- Formulir Edit/Tambah pegawai dan Edit/Tambah OPD kini benar-benar tersimpan. Sejak v1.2.0, tombol Simpan pada kedua formulir memuat ulang halaman tanpa menyimpan apa pun. Isian formulir, termasuk kata sandi baru bila diisi, ikut terkirim sebagai query string GET dan tercatat di log akses server serta riwayat browser. Penyebabnya, kedua formulir memuat isian tersembunyi bernama `id`, sehingga `form.id` di handler submit menunjuk isian itu, bukan id formulir (DOM clobbering). Handler kini membaca atribut `id` dan mencegah pengiriman biasa untuk semua formulir, kecuali latar modal.
- Tombol yang memicu permintaan ke server (Simpan draf, Konfirmasi kirim, simpan formulir, dan Masuk) kini menampilkan spinner dan label proses seperti "Menyimpan…" atau "Mengirim…" selama menunggu. Bila server belum menjawab dalam 10 detik, muncul pemberitahuan bahwa server lambat. Sebelumnya tombol hanya nonaktif tanpa tanda apa pun, sehingga terlihat tidak merespons ketika server lambat.
- Permintaan yang tidak dijawab server dalam 65 detik (sedikit di atas batas 60 detik nginx) kini dibatalkan dengan pesan "Server tidak merespons", lalu tombol aktif kembali. Sebelumnya permintaan menunggu tanpa batas dan semua tombol terkunci. Kegagalan jaringan menampilkan "Tidak dapat terhubung ke server", dan halaman galat HTML dari nginx (misalnya 504) menampilkan nomor status HTTP, bukan "Respons server tidak dapat dibaca".
- Pola validasi kode OPD tidak lagi ditolak browser. Pola lama `[A-Za-z0-9_-]` tidak sah dalam mode regex `v` yang dipakai atribut `pattern`, sehingga validasi di browser diabaikan dan console mencatat error.

## [1.8.0] - 2026-09-30

Migrasi database: jalankan `database/upgrade-1.8-bobot.sql` sebelum memasang kode versi ini. Migrasi menambah kolom `periods.weights` dan mengisi periode yang sudah ada dengan bobot bawaan. Tanpa kolom itu, pembuatan dan perubahan status periode gagal.

### Ditambahkan
- Bobot komposisi penilai melekat pada tiap periode dan dapat diubah administrator kabupaten pada laman Metode, untuk Kondisi 1 (atasan/rekan/bawahan), Kondisi 2 (atasan/rekan), dan Kondisi 3 (atasan/bawahan). Tiap bobot bilangan bulat 1–99 dan jumlah tiap kondisi harus 100%. Bobot bawaan tetap 60/25/15, 75/25, dan 85/15. Perubahan bobot tercatat di log audit per periode.
- Laman Metode mengikuti periode terpilih lewat tombol periode di kepala laman. Bobot hanya dapat diubah selama periode berstatus draf dan hanya berlaku untuk periode itu. Sejak periode dibuka, bobot terkunci dan tampil dengan keterangan terkunci, sehingga aturan tidak berubah selama penilaian berjalan, setelah pratinjau nilai terlihat, maupun setelah hasil dipublikasikan.
- Periode baru menyalin bobot periode terbaru. Pratinjau komposisi pada Struktur organisasi dan Dashboard memakai bobot periode terpilih.
- Grafik jaring laba-laba tujuh indikator BerAKHLAK pada kartu "Nilai saya" di Dashboard, di bawah donut nilai. Pusat grafik bernilai 20 dan tepinya 100, dengan satu cincin per tingkat jawaban. Nilai tepat tiap indikator muncul saat titik atau labelnya disorot, dan seluruh nilai dibacakan pembaca layar. Pada layar 721–940 px kartu dibentang penuh dengan donut dan grafik berdampingan.

### Diubah
- Kartu "Nilai saya" selalu menampilkan donut nilai dan grafik jaring laba-laba. Selama nilai belum boleh tampil (penilaian belum lengkap, menunggu publikasi, atau pegawai bukan peserta), keduanya menunjukkan nilai 0 dan jumlah penilaian yang sudah masuk tertulis di bawah judul kartu. Nilai sementara tetap tidak dibuka sebelum periode dipublikasikan.
- Ambang kelompok rekan sejawat dan bawahan diturunkan dari tiga menjadi satu orang. Buat penugasan kini memakai rekan dan bawahan berapa pun jumlahnya, pembukaan periode menerima kelompok berisi satu atau dua penilai, dan laman Struktur organisasi tidak lagi menandai kelompok kecil sebagai tidak dipakai. Akibatnya pejabat seperti kepala subbagian di sekretariat yang hanya memiliki dua subbagian kini menilai atasan dan rekan sejawatnya. Pegawai yang hanya memiliki atasan (tanpa rekan maupun bawahan) tetap dilewati. Penugasan periode yang sudah dibuka tidak berubah; aturan baru berlaku saat penugasan dibuat untuk periode draf.
- Logo aplikasi diganti dengan logo Kabupaten Hulu Sungai Selatan pada sidebar, laman login (lebih besar di layar lebar, menyesuaikan di ponsel), dan ikon tab browser.
- Penyimpanan dan pengiriman penilaian kini hanya ditentukan status periode. Selama periode dibuka, ASN dapat menyimpan dan mengirim penilaian meskipun tanggal hari ini berada di luar rentang periode (misalnya periode triwulan berikutnya yang dibuka lebih awal, atau pengisian yang berlanjut setelah triwulan berakhir). Administrator menghentikan pengisian dengan menutup periode.

### Diperbaiki
- Modal "Pilih periode" tidak lagi menumpuk tombol periode (kelas `.stack` bentrok dengan komponen daisyUI). Periode terpilih ditandai dan rentang tanggalnya ditampilkan.
- Panah pada kotak pilihan (select) tampil kembali.
- Impor SIASN kini juga menonaktifkan pegawai berjabatan "PNS TUGAS BELAJAR", tidak hanya yang berkedudukan hukum "Tugas Belajar". Dokter tugas belajar yang dititipkan di BKPSDM tercatat berkedudukan "Aktif" di SIASN, sehingga sebelumnya ikut diimpor sebagai pegawai aktif dan mendapat penugasan penilaian.

## [1.7.0] - 2026-09-30

Tidak ada migrasi database.

### Ditambahkan
- Kartu "Nilai saya" di Dashboard, di atas kartu Progres penilaian. Setelah periode dipublikasikan, kartu menampilkan nilai gabungan diri sendiri dari pegawai lain (skala 20–100), jumlah penilaian yang masuk, serta indikator tertinggi dan terendah, dengan tautan ke Hasil penilaian. Sebelum itu kartu hanya menampilkan jumlah penilaian yang sudah masuk dan kapan nilai akan tampil. Pegawai yang belum memiliki penilai melihat keterangan bukan peserta dan pilihan periode lain. Administrator kabupaten melihat pratinjau nilai begitu penilaian lengkap, sama seperti di Hasil penilaian. Identitas dan nilai individual penilai tetap tidak ditampilkan.

### Diubah
- Di layar tablet, kartu Nilai saya dan Progres penilaian tampil berdampingan dan kartu Sudut pandang yang lengkap melebar di bawahnya.

## [1.6.0] - 2026-09-30

Tidak ada migrasi database.

### Ditambahkan
- Atasan lintas OPD. Administrator kabupaten dapat menetapkan pimpinan dari OPD lain sebagai atasan, misalnya Bupati bagi para kepala OPD, sehingga kepala OPD ikut dinilai: atasan Bupati, rekan sesama kepala OPD, dan bawahan langsungnya. Admin OPD tidak dapat menetapkan, mengganti, atau melepas atasan lintas OPD, tetapi tetap dapat menyunting data kepala OPD-nya.
- Pejabat non-ASN yang tidak punya NIP (mis. Bupati) didaftarkan dengan kode huruf besar seperti `BUPATI-HSS` pada kolom NIP; kode ini juga dipakai untuk masuk.

### Diubah
- Buat penugasan membaca struktur dari seluruh OPD, sehingga penugasan per OPD pun menghitung atasan dan rekan lintas OPD. Yang dinilai tetap hanya pegawai OPD terpilih.
- Laman Struktur organisasi: kepala OPD tetap menjadi puncak kelompok OPD-nya, Bupati menampilkan kepala OPD sebagai tautan, dan daftar atasan memuat kelompok "Pimpinan lintas OPD". Atasan lintas OPD tidak lagi hilang saat formulir pegawai atau atasan disimpan.

## [1.5.0] - 2026-09-30

Tidak ada migrasi database.

### Ditambahkan
- Mode gelap. Tampilan awal mengikuti pengaturan terang/gelap perangkat; tombol bulan/matahari di bilah atas dan halaman masuk (di ponsel: menu profil) mengganti mode, dan pilihan disimpan di browser. Tema terang tidak berubah. Cetak hasil selalu memakai tema terang.

## [1.4.2] - 2026-09-29

### Diperbaiki
- Browser tidak lagi memakai `app.js` dan `app.css` lama hingga 7 hari setelah rilis. `index.php` menambahkan penanda versi (waktu ubah berkas) pada URL aset dan mengirim halaman dengan `Cache-Control: no-cache`. Sebelumnya klien yang masih memegang `app.js` pra-1.3.0 gagal membuka Periode & penilai karena `bootstrap` tidak lagi memuat `assignments`.

## [1.4.1] - 2026-09-29

### Diubah
- Label kolom masuk pada formulir login menjadi "Email / NIP".

## [1.4.0] - 2026-09-29

Tidak ada migrasi database.

### Ditambahkan
- Login dengan NIP. Kolom masuk menerima NIP atau email: isian yang mengandung "@" dicari sebagai email, selain itu sebagai NIP (spasi di dalam NIP diabaikan). Aksi `login` menerima kunci `username`; kunci lama `email` tetap diterima.

### Diubah
- Formulir masuk berlabel "NIP atau email" dan tidak lagi menampilkan petunjuk kata sandi awal.
- Pesan gagal masuk menjadi "NIP/email atau kata sandi salah."

## [1.3.0] - 2026-09-29

Tidak ada migrasi database pada rilis ini. Klien yang memakai `bootstrap` sebagai sumber daftar penugasan harus beralih ke `assignment_list` (lihat Diubah).

### Ditambahkan
- Aksi API `assignment_list` (admin dan Admin OPD): daftar penugasan satu periode per halaman (10 baris) dengan filter peran, status, pencarian nama pegawai atau penilai, dan OPD. Respons memuat nama pegawai, nama penilai, kode OPD, serta statistik total dan terkirim. Admin OPD tetap dibatasi ke OPD-nya; `opd_id` kiriman diabaikan.
- `scripts/import-siasn.py`: mengisi OPD, pegawai, akun, dan atasan langsung dari ekspor SIASN (`.xlsx`), dengan mode pratinjau tanpa menulis, penolakan bila sudah ada penugasan penilaian, dan penanganan email kosong atau dipakai bersama. Petunjuk pada README.

### Diubah
- `bootstrap` tidak lagi mengembalikan `assignments`; daftar penugasan diambil lewat `assignment_list`. Tabel Distribusi penilai pada laman Periode & penilai memuat halamannya dari server.
- Rekan sejawat pada Buat penugasan dan laman Struktur organisasi kini berjenjang: pejabat (pegawai yang punya bawahan) berekan dengan sesama pejabat di bawah atasan yang sama, staf berekan dengan staf lain dalam unit (UNOR) yang sama. Sebelumnya semua pegawai dengan atasan yang sama, termasuk staf yang langsung di bawah pimpinan, dicampur dengan pejabat. Seeder demo dan dokumentasi API mengikuti aturan ini.
- Dokumentasi `employee_save` mencantumkan aturan kata sandi (12–128 karakter; pegawai baru dengan kata sandi kosong memakai NIP).

### Diperbaiki
- Laman tidak lagi berhenti pada "Memuat ruang penilaian..." bagi administrator pada data besar. Sebelumnya `bootstrap` memuat seluruh penugasan periode ke memori PHP (sekitar 209 ribu baris pada instalasi SIASN) sehingga melewati `memory_limit` 128 MB dan mengembalikan HTTP 500.

## [1.2.0] - 2026-09-13

### Ditambahkan
- Multi-OPD dalam satu kabupaten: tabel `opd` dan `settings` (nama kabupaten dinamis, bawaan Hulu Sungai Selatan), kolom `employees.opd_id`, serta menu OPD & pengaturan untuk administrator kabupaten.
- Peran **Admin OPD** (`admin_opd`) yang mengelola pegawai, struktur organisasi, dan pasangan penilai terbatas pada OPD-nya sendiri; `employees` dan `assignments` pada bootstrap disaring per OPD.
- Periode triwulan (`app/Period.php`): periode dibuat dari tahun dan triwulan (I–IV), nama dan rentang tanggal ditetapkan otomatis, satu triwulan hanya bisa dibuat sekali.
- Periode default pada bootstrap memprioritaskan `open`, lalu `closed`, `published`, dan terakhir `draft`, sehingga periode draf masa depan tidak mengosongkan daftar tugas ASN.
- Konfigurasi `TRUSTED_PROXIES` agar pembatasan login membaca IP klien dari `X-Forwarded-For` hanya dari reverse proxy tepercaya; ambang per-akun (10) dan per-IP (100) dipisah.
- Seeder demo: lima OPD, 60 pegawai fiktif, struktur organisasi tiap OPD, dua periode triwulan, distribusi penilai turunan struktur; daftar akun pada `USERS.md`.
- Rangkaian pengujian PHP: `unit_scoring_edge`, `unit_period`, `integration_db`, `api_invariants`, `functional_flows`, `structure_flows`, `opd_flows`, dengan `tests/bootstrap.php` dan `tests/http_client.php`; E2E Playwright (`e2e/qa-crawl.spec.ts`, Chromium desktop dan Pixel 5).
- Paket QA 8 tahap pada `.qa/` (spesifikasi, skrip, laporan `QA-2026-09-13.md`) dan dokumentasi API pada `docs/API.md`.
- Migrasi `database/upgrade-1.2-opd.sql`: setiap nilai `employees.unit` lama menjadi satu OPD.

### Diubah
- Adapter pratinjau `demo.js` dipindah ke `resources/` dan disalin ke `dist/assets/` saat build; tidak lagi ikut terpasang pada aplikasi live.
- Zona waktu sesi MySQL disamakan dengan zona waktu aplikasi (`app/Database.php`) agar `submitted_at` dan `published_at` konsisten.
- `session.gc_maxlifetime` disamakan dengan batas tidak aktif sesi (1800 detik).
- Baris `login_attempts` lebih dari satu hari dibersihkan pada tiap login.
- Pelanggaran `CHECK` MySQL (errno 3819) dipetakan ke HTTP 422, bukan 503.
- `.dockerignore` mengecualikan `.qa`, `e2e`, `test-results`, dan `playwright.config.ts`; Apache pada image Docker menolak akses langsung `index.html` setara contoh Nginx.
- Kunci indikator pada jawaban wajib bilangan bulat kanonik (`"01"` atau `" 1"` ditolak dengan 422).

### Diperbaiki
- Header `Origin` yang ada tetapi tidak dapat di-parse kini ditolak dengan 403, bukan diperlakukan sebagai tanpa Origin (QA BUG-001).
- Periode draf berstatus masa depan tidak lagi menjadi periode default seluruh pengguna (QA BUG-002).

## [1.1.0] - 2026-09-13

### Ditambahkan
- Struktur organisasi: kolom `employees.supervisor_id` (atasan langsung) dan halaman Struktur organisasi.
- Rekan sejawat (pegawai dengan atasan yang sama) dan bawahan langsung diturunkan otomatis dari struktur; halaman menampilkan keabsahan komposisi penilai tiap pegawai.
- Tombol Buat penugasan untuk mengisi pasangan penilai suatu periode dari struktur organisasi; kelompok rekan/bawahan kurang dari tiga orang dilewati dan dilaporkan.
- Migrasi `database/upgrade-1.1-struktur.sql` untuk instalasi yang dibuat sebelum fitur struktur organisasi.

## [1.0.0] - 2026-09-12

### Ditambahkan
- Aplikasi penilaian perilaku ASN berbasis PHP 8.3, MySQL 8, Tailwind CSS 4, dan daisyUI 5 dengan tujuh indikator BerAKHLAK berskala 1–5.
- Login lokal dengan dua hak akses (administrator dan ASN), sesi HttpOnly/SameSite=Strict, token CSRF, pembatasan percobaan login, dan PDO prepared statements.
- Dashboard tugas, progres, profil, batas periode, serta pencarian dan penyaringan tugas menurut hubungan dan status.
- Draf parsial tersimpan di server, validasi tujuh jawaban, pengiriman satu atau beberapa draf dalam satu transaksi, penguncian jawaban setelah dikirim, dan nomor versi draf (optimistic locking, HTTP 409).
- Hasil pribadi gabungan dengan bobot Atasan/Rekan/Bawahan (60/25/15, 75/25/0, 85/0/15), konversi skala 20–100, grafik indikator, pilihan periode, dan cetak melalui browser.
- Administrasi pegawai dan akun ASN, ganti kata sandi (membatalkan sesi lain melalui `auth_version`), ekspor CSV.
- Siklus periode draft → open → closed → published, buka kembali periode yang belum dipublikasikan, validasi komposisi penilai saat dibuka, dan publikasi hanya bila seluruh penugasan terkirim.
- Audit tindakan pada tabel `audit_logs`.
- Instalasi CLI (`scripts/install.php`, mode demo dan mode kosong), Docker Compose, contoh konfigurasi Nginx, dan pratinjau statis `dist/` dengan data fiktif.
- Pengujian aturan penilaian `tests/scoring.php` (17 kasus) dan catatan validasi `docs/VALIDASI.md`.

[1.2.0]: https://github.com/Syamsuddin/sikap-360/releases/tag/v1.2.0
[1.1.0]: https://github.com/Syamsuddin/sikap-360/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/Syamsuddin/sikap-360/releases/tag/v1.0.0
