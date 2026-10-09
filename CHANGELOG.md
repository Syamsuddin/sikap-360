# Changelog

Semua perubahan penting pada SIKAP 360 dicatat di berkas ini. Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/) dan penomoran versi mengikuti [Semantic Versioning](https://semver.org/lang/id/).

Instalasi yang dibuat pada versi lebih lama memerlukan migrasi database yang tercantum pada tiap versi (lihat `database/`). Instalasi baru cukup memakai `database/schema.sql`.

## [Belum dirilis]

Tidak ada migrasi database.

### Ditambahkan
- Status aktif/nonaktif pegawai dari tabel Data pegawai, lewat dialog konfirmasi yang menjelaskan akibatnya dan memperingatkan bila pegawai itu atasan langsung pegawai aktif. Administrator kabupaten untuk semua pegawai; admin OPD untuk pegawai OPD-nya, kecuali administrator kabupaten. Akun sendiri dan administrator kabupaten terakhir tidak dapat dinonaktifkan, dan pegawai di OPD nonaktif tidak dapat diaktifkan. Perubahan tercatat di log audit. Aksi API baru `employee_status`.
- Penanda "Dikecualikan" pada distribusi penilai dan jumlah penugasan yang dikecualikan di rincian periode.

### Diubah
- Penilaian hanya bagi pegawai aktif. Penugasan yang melibatkan pegawai nonaktif tidak dihapus, tetapi dikecualikan dari daftar tugas penilai, simpan dan kirim penilaian, perhitungan nilai, Dashboard admin, kolom progres periode, dan syarat publikasi; semuanya berlaku lagi bila pegawai diaktifkan kembali. Sebelumnya penugasan seperti itu tetap dihitung dan dapat menahan publikasi karena penilai nonaktif tidak dapat masuk.
- Membuka periode draf dengan komposisi yang tidak sah karena penilai nonaktif ditolak dengan menyebut nama pegawainya. Saat membuka kembali atau mempublikasikan periode, pegawai seperti itu dikecualikan (tanpa nilai) agar periode tidak tertahan, dan jumlahnya disebut di notifikasi.
- Tabel Data pegawai: nama, unit, dan jabatan boleh terbungkus, NIP pindah ke bawah nama pada layar ≤1180 px, dan label akun pindah ke bawah nama di ponsel, sehingga tombol Edit dan Nonaktifkan tetap terlihat tanpa menggeser tabel. Baris pegawai nonaktif diredupkan.

### Diperbaiki
- Buat penugasan dari struktur tidak lagi memakai atasan langsung yang nonaktif sebagai penilai atasan; pegawai yang atasannya nonaktif dilewati dengan catatan agar atasan pengganti ditetapkan.

## [1.12.0] - 2026-10-09

Tidak ada migrasi database.

### Ditambahkan
- Tombol aksi status langsung di tiap baris tabel periode pada laman Periode & penilai (administrator kabupaten): Buka untuk draf, Tutup untuk periode aktif, Buka kembali dan Publikasikan untuk periode yang ditutup. Sebelumnya tabel hanya berisi tombol Pilih dan aksi status hanya ada di bagian periode terpilih. Dialog konfirmasi kini menyebut nama periode, dan aksi tetap melewati validasi server yang sama.
- Hapus periode: periode draf yang belum memiliki penugasan dapat dihapus dari tabel lewat dialog konfirmasi, beserta bobot dan indikator tematiknya, dan tercatat di log audit. Periode yang sudah memiliki penugasan, sudah dibuka, atau satu-satunya periode tidak dapat dihapus. Aksi API baru `period_delete`.
- Kolom Progres per periode di tabel periode: penugasan terkirim dari total beserta persentasenya (admin OPD: pegawai dinilai di OPD-nya).
- Peringatan di dialog konfirmasi saat membuka periode ketika periode lain masih aktif: kedua periode akan aktif bersamaan dan pegawai otomatis diarahkan ke periode aktif dengan tanggal mulai terbaru.
- Tautan "Atur bobot & tematik" ke laman Metode di bagian periode terpilih selama periode berstatus draf.

### Diubah
- Tabel periode menggabungkan kolom Mulai dan Selesai menjadi rentang tanggal di bawah nama periode, sehingga tombol aksi muat sebaris. Di ponsel status tampil di bawah nama periode dan tombol aksi tetap terlihat tanpa menggeser tabel; deret tombol di bagian periode terpilih turun ke baris berikutnya agar halaman tidak melebar.

## [1.11.0] - 2026-10-09

Tidak ada migrasi database.

### Ditambahkan
- Grafik jaring laba-laba kedua pada kartu "Nilai saya" untuk indikator tematik, di bawah grafik BerAKHLAK. Tiap grafik diberi keterangan nama dan rata-ratanya, misalnya "BerAKHLAK · 95" dan "Tematik · Pemberantasan Korupsi · 65". Grafik tematik sepenuhnya dinamis: label sumbu diturunkan dari nama butir tematik periode terpilih (kata terpanjang, misalnya Gratifikasi dan Kepentingan, diberi nomor bila kembar), nama lengkap tampil di tooltip dan pembaca layar, jumlah sudut mengikuti banyaknya butir, keterangan mengikuti tema periode, dan nilai tiap titik berasal dari hasil penilaian. Periode tanpa indikator tematik tetap menampilkan satu grafik.

### Diubah
- Dashboard pegawai didesain ulang agar info penting terlihat tanpa menggulir. Kartu "Penilaian saya" kini memuat seluruh info penugasan dalam satu kartu: lingkar progres, bilah status bertingkat dengan jumlah terkirim/draf/belum, sisa hari menuju batas periode, tombol "Mulai menilai" atau "Lanjutkan draf" ke tugas berikutnya, tombol "Kirim n draf lengkap", dan lima tugas teratas (draf dan yang belum dinilai lebih dulu; tiga di ponsel). Kolom kanan memuat "Nilai saya" dan kartu ringkas "Yang dinilai" (jumlah indikator dan bobot penilai). Kartu "Penilaian saya" dan "Nilai saya" muat dalam satu layar mulai 1366×768, dan seluruh dashboard mulai 1440×900; sebelumnya dashboard setinggi 2,6 layar di 1366×768. Di ponsel tinggi dashboard turun dari 4 menjadi 2 layar dengan kartu "Penilaian saya" utuh di layar pertama.
- Sapaan dashboard memakai nama tanpa gelar, misalnya "Selamat datang, Rina".
- Kartu "Nilai saya" menampilkan donut dan grafik berdampingan. Label grafik yang panjang mengecilkan jari-jari grafik sehingga huruf tetap terbaca dan tidak terpotong, dan baris nilai tematik terpisah diganti keterangan grafik tematik.

### Dihapus
- Kartu statistik terpisah, kartu progres gelap, catatan batas penilaian, kartu profil, kartu "Nilai yang membentuk ASN", dan kartu "Sudut pandang yang lengkap" dari dashboard pegawai. Isinya tercakup kartu "Penilaian saya", grafik "Nilai saya", dan kartu "Yang dinilai"; profil tetap tersedia di menu akun dan rincian metode di laman Metode.

## [1.10.0] - 2026-10-09

Tidak ada migrasi database.

### Ditambahkan
- Laman Dashboard admin (menu Pengelolaan, khusus administrator kabupaten) untuk periode terpilih. Bagian atas berisi kartu progres skala kabupaten: persentase penugasan terkirim, jumlah terkirim/draf/belum, pegawai selesai dinilai, dan penilai yang sudah mengirim semua tugasnya. Di bawahnya progres per peran penilai dan jumlah OPD per status (Tuntas, Berjalan, Belum mulai, Tanpa penugasan). Bagian bawah berisi tabel progres per OPD dengan pencarian, filter status, urutan (nama, progres terendah atau tertinggi, penugasan terbanyak), pagination 10 OPD per halaman, dan unduhan CSV. Laman hanya menampilkan jumlah, tanpa nilai maupun identitas penilai. Data diambil saat laman dibuka dan dapat dimuat ulang.
- Aksi API `admin_dashboard` untuk data laman tersebut.

### Diubah
- Panduan administrator kabupaten menyertakan langkah memantau progres pada Dashboard admin.
- Laman masuk menyebut "Tujuh indikator BerAKHLAK dan tematik triwulan".
- README memuat bagian "Indikator tematik per triwulan" (cara mengganti tema dan butir tiap triwulan) serta perintah regresi lengkap. `docs/VALIDASI.md` memuat langkah uji penerimaan indikator tematik. Bantuan `--apply` pada impor SIASN menyebut bahwa periode beserta indikator tematiknya ikut terhapus.

## [1.9.0] - 2026-10-09

Migrasi database: jalankan `database/upgrade-1.9-tematik.sql` sebelum memasang kode versi ini. Migrasi menambah kolom `indicators.category`, `indicators.period_id`, dan `periods.theme`, serta memperlebar ID indikator menjadi SMALLINT. Tujuh indikator yang ada menjadi kategori BerAKHLAK. Periode yang sudah ada tidak memiliki indikator tematik, sehingga tetap dinilai dengan tujuh indikator. Untuk memasang indikator tematik Triwulan IV 2026, jalankan juga `database/tematik-triwulan-iv-2026.sql` setelah migrasi.

### Ditambahkan
- Lima indikator tematik per periode, di samping tujuh indikator BerAKHLAK. Tema dan butirnya berganti tiap triwulan. Administrator kabupaten menetapkannya di laman Metode selama periode berstatus draf: tema triwulan, lalu nama dan uraian perilaku tiap butir. Sejak periode dibuka, indikator tematik terkunci seperti bobot. Periode baru menyalin tema dan butir tematik periode terbaru untuk diganti sebelum dibuka. Setiap perubahan tercatat di log audit.
- Indikator tematik Triwulan IV 2026 bertema Pemberantasan Korupsi: Menolak Gratifikasi dan Pungutan Liar; Mengelola Konflik Kepentingan; Jujur dalam Anggaran, Aset, dan Laporan; Transparan dan Taat Prosedur; serta Berani Menolak dan Melaporkan Penyimpangan. Kelima butir dirumuskan sebagai perilaku yang dapat diamati atasan, rekan, dan bawahan pada semua jenjang, dari pelaksana sampai kepala OPD dan Sekretaris Daerah.
- Formulir penilaian mengelompokkan pertanyaan menjadi "Indikator BerAKHLAK" dan "Indikator Tematik · tema", bernomor urut, dengan progres dan daftar periksa yang mengikuti jumlah indikator periode.
- Halaman hasil menampilkan grafik batang per kelompok beserta rata-rata tiap kategori.

### Diubah
- Nilai akhir adalah rata-rata seluruh indikator periode dengan bobot setara. Pada periode bertematik, nilai akhir adalah rata-rata 12 indikator, sehingga tematik menyumbang 5/12 nilai. Periode tanpa indikator tematik tetap memakai rata-rata tujuh indikator.
- Pengiriman penilaian dan publikasi periode mensyaratkan jawaban untuk seluruh indikator periode. Pesan kelengkapan kini menyebut jumlahnya, misalnya "Lengkapi seluruh 12 indikator sebelum mengirim."
- Kartu "Nilai saya" tetap menampilkan jaring laba-laba tujuh indikator BerAKHLAK, ditambah baris nilai tematik. Kartu "Nilai yang membentuk ASN" di Dashboard menyebut tema triwulan.
- Impor SIASN menghapus indikator tematik bersama periodenya, agar butir lama tidak menempel pada periode baru yang memakai ID yang sama setelah tabel periode dikosongkan.

## [1.8.1] - 2026-09-30

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
