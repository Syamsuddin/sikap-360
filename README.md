# SIKAP 360

![Dashboard penilaian SIKAP 360](dist/assets/banner.png)

[![Rilis](https://img.shields.io/github/v/tag/Syamsuddin/sikap-360?label=rilis&color=2563eb)](https://github.com/Syamsuddin/sikap-360/releases)
[![Lisensi MIT](https://img.shields.io/github/license/Syamsuddin/sikap-360?color=16a34a)](LICENSE)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL 8](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Tailwind CSS 4](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)
[![daisyUI 5](https://img.shields.io/badge/daisyUI-5-1AD1A5?logo=daisyui&logoColor=white)](https://daisyui.com/)
[![Docker Compose](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)](compose.yaml)
[![Pengujian](https://img.shields.io/badge/pengujian-PHP_%2B_Playwright-45ba4b?logo=playwright&logoColor=white)](tests/)
[![Changelog](https://img.shields.io/badge/changelog-Keep_a_Changelog-E05735?logo=keepachangelog&logoColor=white)](CHANGELOG.md)

Aplikasi penilaian perilaku ASN berbasis PHP 8.3, MySQL, Tailwind CSS 4, dan daisyUI 5. Tampilan dan alur mengikuti 12 gambar referensi. Data contoh memakai identitas fiktif, tanpa foto, email, atau NIP orang pada gambar.

## Isi aplikasi

Satu instalasi melayani satu kabupaten (nama dinamis, bawaan Hulu Sungai Selatan) dengan banyak OPD dalam satu kerangka. Administrator kabupaten mengelola OPD, periode, dan pengaturan; Admin OPD mengelola pegawai, struktur organisasi, dan pasangan penilai OPD-nya; periode penilaian berlaku serentak untuk seluruh OPD.

- Login lokal dan dua hak akses: administrator dan ASN.
- Dashboard pegawai satu layar (1366×768 ke atas): kartu "Penilaian saya" berisi progres, jumlah terkirim/draf/belum, batas waktu, tombol lanjut dan kirim draf lengkap, serta lima tugas teratas; di sampingnya kartu "Nilai saya" (donut nilai akhir, grafik jaring laba-laba BerAKHLAK, dan grafik kedua untuk indikator tematik bila periode memilikinya) serta ringkasan indikator dan bobot periode.
- Dashboard admin untuk administrator kabupaten: progres penilaian skala kabupaten, progres per peran penilai, status OPD, dan tabel progres per OPD (pencarian, filter status, urutan, pagination, unduh CSV).
- Pencarian dan penyaringan tugas menurut hubungan serta status.
- Tujuh indikator BerAKHLAK ditambah lima indikator tematik per triwulan (tema dan butirnya ditetapkan administrator kabupaten), semuanya dengan skala bilangan bulat 1–5.
- Draf parsial, validasi jawaban seluruh indikator periode, pengiriman satu atau beberapa draf lengkap.
- Penguncian jawaban setelah dikirim, nomor versi draf untuk mencegah penimpaan antar sesi.
- Hasil pribadi gabungan, grafik indikator, pilihan periode, dan cetak melalui browser.
- Penambahan/edit pegawai, akun ASN, status aktif/nonaktif pegawai, perubahan kata sandi, ekspor CSV.
- Pembuatan periode, distribusi penilai, buka/tutup/buka kembali, publikasi, dan hapus periode draf yang belum memiliki penugasan, langsung dari tabel periode dengan kolom progres per periode.
- Audit tindakan di database, CSRF, sesi HttpOnly, PDO prepared statements, pembatasan percobaan login.

## Dua bentuk penggunaan

`public/` adalah aplikasi PHP yang terhubung ke MySQL melalui `public/api.php`. Semua penilaian disimpan di database. Aplikasi tidak beralih ke data demo jika koneksi gagal.

`dist/` adalah pratinjau statis dengan data fiktif dan penyimpanan lokal browser. Berlabel Ruang demo. Tombol Coba sebagai admin membuka simulasi pengelolaan. Pratinjau ini tidak menjalankan backend PHP, tidak memakai MySQL, dan tidak menjadi penyimpanan bersama antarpegawai. Reset melalui menu profil.

## Instalasi cepat dengan Docker

Kebutuhan: Docker Engine dan Docker Compose.

1. Salin `.env.example` menjadi `.env`.
2. Ganti `DB_PASSWORD` dan `MYSQL_ROOT_PASSWORD`. Pertahankan `APP_URL="http://localhost:8080"` untuk uji lokal. Di produksi HTTPS, set `SESSION_SECURE="1"` dan isi `TRUSTED_PROXIES` dengan IP reverse proxy agar pembatasan login mengenali IP klien, bukan IP proxy.
3. Jalankan:

```bash
docker compose up -d --build
docker compose exec app php scripts/install.php --demo
```

Buka http://localhost:8080.

Akun demo (masuk dengan email, atau NIP `DEMO-0001` dan seterusnya):

| Akses | Email | Kata sandi |
| --- | --- | --- |
| Administrator | pegawai1@example.test | SikapDemo2026! |
| ASN | pegawai3@example.test | SikapDemo2026! |

Seeder membuat kabupaten Hulu Sungai Selatan dengan lima OPD (Sekretariat Daerah, BKPSDM, Dinas Pendidikan dan Kebudayaan, Dinas Komunikasi dan Informatika, Dinas Kesehatan; 60 pegawai), struktur organisasi tiap OPD, dua periode triwulan, dan distribusi penilai yang diturunkan dari struktur. pegawai1 (Disdikbud) dan pegawai28 (BKPSDM) adalah administrator kabupaten; tiap OPD punya satu Admin OPD (lihat USERS.md). Periode aktif adalah triwulan berjalan saat instalasi; triwulan sebelumnya berstatus dipublikasikan. Administrator juga memiliki tugas sebagai ASN. Skor periode terdahulu telah dipublikasikan untuk mencoba halaman hasil.

Untuk database kosong tanpa contoh, gunakan instalasi berikut. Tetapkan nilai pada lingkungan shell Anda, jangan menuliskan kata sandi pada source code atau Git.

```bash
export ADMIN_EMAIL='admin@instansi.go.id'
export ADMIN_NAME='Nama Administrator'
export ADMIN_NIP='ISI_NIP_18_DIGIT'
export KABUPATEN_NAME='Hulu Sungai Selatan'   # opsional, bawaan Hulu Sungai Selatan; bisa diubah lagi di menu OPD & pengaturan
export ADMIN_OPD='Sekretariat Daerah'         # opsional, OPD tempat akun admin didaftarkan
read -rs -p 'Kata sandi admin: ' ADMIN_PASSWORD
export ADMIN_PASSWORD
docker compose exec -e ADMIN_EMAIL -e ADMIN_NAME -e ADMIN_NIP -e ADMIN_PASSWORD -e KABUPATEN_NAME -e ADMIN_OPD app php scripts/install.php
unset ADMIN_PASSWORD
```

Instalasi menolak menimpa database yang sudah memiliki akun. Pilih instalasi demo atau instalasi kosong pada database yang berbeda. Jangan gunakan akun demo dalam produksi.

## Instalasi PHP biasa, Ubuntu, XAMPP, atau MAMP

Kebutuhan: PHP 8.3+ dengan ekstensi `pdo_mysql`, `mbstring`, `session`, `json`, dan MySQL 8.0.16+ atau 8.4. Node.js hanya diperlukan ketika mengubah CSS. CSS hasil kompilasi sudah disertakan.

1. Buat database `sikap360` dengan charset `utf8mb4` dan akun database khusus aplikasi.
2. Salin `.env.example` menjadi `.env` lalu isi host, port, nama database, pengguna, kata sandi, dan `APP_URL`.
3. Jalankan `php scripts/install.php --demo`, atau siapkan environment admin dan jalankan tanpa `--demo`.
4. Arahkan document root Apache/Nginx ke folder `public/`, bukan akar proyek.
5. Untuk uji lokal:

```bash
php -S localhost:8080 -t public
```

Jika dipasang dalam subfolder, sesuaikan `APP_URL` dengan URL lengkap. Referensi aset dan API relatif terhadap halaman aplikasi.

Contoh konfigurasi Nginx tersedia pada `config/nginx.conf.example`. Sesuaikan socket PHP-FPM yang terpasang. Konfigurasi contoh memakai port HTTP untuk pemasangan awal. Untuk penggunaan instansi, aktifkan HTTPS, tetapkan `APP_URL` ke URL HTTPS yang benar, dan `SESSION_SECURE="1"`. Akses langsung database dan file `.env` dibatasi pada administrator server. Backup MySQL diperlukan sebelum perubahan lingkungan atau pembaruan aplikasi.

## Impor pegawai dari SIASN

`scripts/import-siasn.py` mengisi OPD, pegawai, akun, dan atasan langsung dari ekspor SIASN (`.xlsx`). Skrip ini **mengosongkan seluruh data** (kecuali tujuh indikator BerAKHLAK dan pengaturan) lalu mengisinya ulang, jadi backup database terlebih dahulu. Periode ikut terhapus beserta indikator tematiknya, sehingga tematik triwulan perlu diisi lagi setelah impor. Kebutuhan: Python 3 dengan `pandas`, `openpyxl`, `pymysql`, serta PHP CLI. Koneksi database dibaca dari `.env`.

```bash
python3 scripts/import-siasn.py ekspor-siasn.xlsx          # pratinjau statistik tanpa menulis
python3 scripts/import-siasn.py ekspor-siasn.xlsx --apply --admin email.admin@instansi.go.id
```

- OPD diturunkan dari pohon UNOR; nomenklatur lama/baru digabung, Kelurahan masuk Kecamatan, UPT dan RSUD Daha Sejahtera masuk dinas induknya.
- Atasan langsung adalah pejabat struktural unit pegawai, atau unit induknya bila jabatan itu kosong.
- Guru, pegawai sekolah (SD/SMP/TK), dan Puskesmas tidak diimpor kecuali dengan `--semua`, karena kepala sekolah dan kepala Puskesmas tidak tercatat di SIASN.
- Pegawai tanpa UNOR masuk OPD "Unit Organisasi Belum Terpetakan". Email yang kosong, rusak, atau dipakai bersama diganti `NIP@sikap360.local`.
- Pegawai masuk dengan NIP (18 digit) atau email SIASN-nya; kata sandi awal adalah NIP dan dapat diganti pada menu Profil. Impor ditolak bila sudah ada penugasan penilaian, kecuali dengan `--timpa-penilaian`.
- Hanya kolom yang dipakai aplikasi yang diimpor; NIK, alamat, HP, dan NPWP diabaikan. Jangan simpan berkas ekspor SIASN di repositori.

## Urutan pengoperasian

1. Masuk sebagai administrator kabupaten. Pada menu OPD & pengaturan, periksa nama kabupaten (bawaan Hulu Sungai Selatan) dan daftarkan OPD. Lengkapi data pegawai; tetapkan peran Admin OPD bagi pengelola tiap OPD agar mereka mengurus pegawai, struktur, dan pasangan penilai OPD-nya sendiri.
2. Tetapkan atasan langsung setiap pegawai pada menu Struktur organisasi (atau pada formulir pegawai). Rekan sejawat dan bawahan langsung diturunkan otomatis. Rekan pejabat (pegawai yang punya bawahan) adalah sesama pejabat dengan atasan yang sama, misalnya Sekretaris dan para Kepala Bidang di bawah Kepala Badan; rekan staf adalah staf lain dalam unit (UNOR) yang sama, dan laman ini memperlihatkan apakah komposisi penilai tiap pegawai sudah sah. Agar kepala OPD ikut dinilai, administrator kabupaten dapat mendaftarkan Bupati (OPD tingkat kabupaten, NIP diisi kode seperti `BUPATI-HSS`) dan menetapkannya sebagai atasan para kepala OPD; para kepala OPD lalu saling menjadi rekan sejawat.
3. Buat periode berstatus draf dengan memilih tahun dan triwulan (I: Januari–Maret, II: April–Juni, III: Juli–September, IV: Oktober–Desember), kemudian pilih periode tersebut. Nama dan rentang tanggal ditetapkan otomatis; satu triwulan hanya bisa dibuat sekali. Periode baru menyalin bobot serta tema dan lima indikator tematik periode terbaru; ganti tema dan butir tematik triwulan itu pada laman Metode sebelum periode dibuka.
4. Tekan Buat penugasan pada Struktur organisasi untuk mengisi pasangan penilai dari struktur, atau tambahkan penilai satu per satu pada Periode & penilai. Rekan sejawat dan bawahan dipakai berapa pun jumlahnya (minimal satu orang); pegawai yang hanya punya atasan dilewati dan dilaporkan.
5. Pilih peran berdasarkan posisi penilai terhadap pegawai yang dinilai. Jika A menilai bawahannya B, peran penilai A adalah Atasan. Di daftar tugas A, B diberi label Bawahan.
6. Buka periode setelah komposisi lengkap. Distribusi penilai dikunci sejak periode dibuka. Tombol Buka, Tutup, Buka kembali, dan Publikasikan tersedia di tiap baris tabel periode maupun di bagian periode terpilih. Bila periode lain masih aktif, dialog konfirmasi memperingatkan bahwa kedua periode akan aktif bersamaan dan pegawai otomatis diarahkan ke periode aktif dengan tanggal mulai terbaru. Periode draf yang salah dibuat dan belum memiliki penugasan dapat dihapus dari tabel; periode yang sudah memiliki penugasan, sudah dibuka, atau satu-satunya periode tidak dapat dihapus.
7. ASN mengisi tujuh indikator BerAKHLAK dan lima indikator tematik (bila periode memilikinya). Draf disimpan di server dan tetap tersedia setelah login kembali.
8. Kirim draf lengkap. Jawaban yang terkirim tidak bisa diedit.
9. Pantau progres pengisian pada Dashboard admin (khusus administrator kabupaten). Kartu atas merangkum seluruh kabupaten; tabel bawah merinci tiap OPD, dan urutan "Progres terendah" menampilkan OPD yang perlu didorong lebih dulu. Penugasan dan pegawai dihitung menurut OPD pegawai yang dinilai, penilai menurut OPD penilai. Laman ini hanya menampilkan jumlah, tanpa nilai maupun identitas penilai.
10. Setelah semua penugasan selesai, tutup periode dan publikasikan hasil.
11. Jika periode ditutup terlalu cepat, admin membuka kembali periode yang belum dipublikasikan. Pengisian mengikuti status periode, bukan rentang tanggal: selama periode dibuka, ASN tetap dapat menyimpan dan mengirim penilaian.
12. ASN memilih periode yang dipublikasikan pada Hasil penilaian.

Penilaian hanya bagi pegawai aktif. Administrator menonaktifkan atau mengaktifkan kembali pegawai dari tabel Data pegawai (admin OPD: pegawai OPD-nya, tidak untuk administrator kabupaten; tidak untuk akun sendiri). Pegawai nonaktif tidak dapat masuk, tidak dinilai, dan tidak menilai. Penugasan yang melibatkannya tidak dihapus, tetapi dikecualikan dari daftar tugas penilai, perhitungan nilai, progres, dan syarat publikasi, lalu berlaku lagi bila pegawai diaktifkan kembali. Buat penugasan melewati pegawai yang atasan langsungnya nonaktif. Saat membuka periode draf, komposisi yang menjadi tidak sah karena penilai nonaktif ditolak dengan nama pegawainya; saat membuka kembali atau mempublikasikan, pegawai tersebut dikecualikan (tanpa nilai) agar periode tidak tertahan.

Pegawai yang belum diberi pasangan penilai belum menjadi peserta penilaian pada periode tersebut. Penilaian diri sendiri ditolak. Pasangan penilai yang sama tidak bisa didaftarkan dua kali untuk pegawai/periode yang sama.

## Indikator tematik per triwulan

Selain tujuh indikator BerAKHLAK yang berlaku untuk semua periode, tiap periode dapat memiliki lima indikator tematik dengan tema yang berganti setiap triwulan. Penilai menjawab kedua kelompok pada formulir yang sama dengan skala 1–5, dan nilai akhir menghitung keduanya dengan bobot setara (lihat Perhitungan).

1. Pilih periode berstatus draf, lalu buka laman Metode sebagai administrator kabupaten.
2. Pada bagian Indikator tematik, isi tema triwulan (maksimal 120 karakter) serta nama (maksimal 80 karakter) dan uraian perilaku (maksimal 600 karakter) untuk tepat lima butir. Nama butir tidak boleh sama.
3. Simpan. Penyimpanan berikutnya selama periode masih draf menggantikan seluruh butir, dan setiap perubahan tercatat di `audit_logs`.
4. Buka periode. Sejak itu butir tematik terkunci dan hanya dapat diubah langsung di database.

Periode baru menyalin tema dan butir tematik periode terbaru, jadi ganti isinya sebelum periode dibuka. Admin OPD dan ASN hanya dapat membaca indikator tematik. Rumuskan butir sebagai perilaku yang dapat diamati atasan, rekan, dan bawahan pada semua jenjang, dari pelaksana sampai kepala OPD.

Triwulan IV 2026 bertema Pemberantasan Korupsi (`database/tematik-triwulan-iv-2026.sql`): Menolak Gratifikasi dan Pungutan Liar; Mengelola Konflik Kepentingan; Jujur dalam Anggaran, Aset, dan Laporan; Transparan dan Taat Prosedur; serta Berani Menolak dan Melaporkan Penyimpangan.

## Perhitungan

Bobot bawaan mengikuti gambar pengguna, bukan klaim bobot universal yang diwajibkan regulasi. Bobot melekat pada tiap periode. Administrator kabupaten dapat mengubah bobot periode berstatus draf pada laman Metode (bilangan bulat 1–99, jumlah tiap kondisi 100%); sejak periode dibuka, bobotnya terkunci sehingga aturan tidak berubah selama penilaian berjalan maupun setelah nilai terlihat. Periode baru menyalin bobot periode terbaru.

| Komposisi | Atasan | Rekan sejawat | Bawahan |
| --- | ---: | ---: | ---: |
| Tiga peran | 60% | 25% | 15% |
| Atasan dan rekan | 75% | 25% | 0% |
| Atasan dan bawahan | 85% | 0% | 15% |

Setiap pegawai memiliki tepat satu penilai atasan. Kelompok rekan dan bawahan yang digunakan masing-masing minimal satu penilai. Komposisi di luar ketiga kondisi ditolak ketika periode dibuka. Kelompok berisi satu atau dua orang tetap dipakai agar pejabat dengan sedikit rekan atau bawahan (mis. kepala subbagian di sekretariat yang hanya memiliki dua subbagian) tetap menilai dan dinilai; konsekuensinya, penilai dalam kelompok kecil lebih mudah ditebak.

Untuk setiap indikator:

1. Hitung rata-rata skor dalam masing-masing kelompok penilai.
2. Kalikan rata-rata kelompok dengan bobotnya, lalu jumlahkan.
3. Kalikan nilai tertimbang dengan 20 untuk konversi skala 20–100.

Nilai akhir adalah rata-rata seluruh indikator periode sebelum pembulatan: tujuh BerAKHLAK ditambah lima tematik bila periode memilikinya, masing-masing berbobot sama, sehingga tematik menyumbang 5/12 nilai akhir. Halaman hasil juga menampilkan rata-rata tiap kategori sebagai rincian. Tampilan dibulatkan dua desimal. Contoh: seluruh indikator atasan 5, rekan 4, bawahan 3 menghasilkan `(5×0,60 + 4×0,25 + 3×0,15) × 20 = 89`.

Jumlah orang dalam kelompok tidak mengubah bobot kelompok. Penilaian yang belum masuk tidak diisi nol dan tidak memicu pengalihan bobot. Publikasi diblokir sampai seluruh penugasan pada periode telah terkirim dengan jawaban valid untuk seluruh indikator periode itu.

Skor adalah hasil kuantitatif rubrik. Aplikasi tidak menetapkan predikat resmi kinerja, keputusan kepegawaian, atau kelayakan manajemen talenta secara otomatis.

## Kerahasiaan dan batas implementasi

- Akun ASN menerima tugas miliknya dan hasil gabungan dirinya sendiri.
- Identitas penilai masuk dan nilai individualnya tidak dikirim ke halaman hasil pribadi.
- Administrator mengakses pegawai, pasangan penilai, dan status pengiriman. API ini tidak menyediakan pembacaan catatan/nilai individual orang lain untuk administrator.
- Relasi penilai dan jawaban tetap disimpan di database untuk kebutuhan integritas dan audit. Administrator database mempunyai akses teknis. Sistem tidak menjanjikan anonimitas terhadap pengelola server.
- Catatan pengembangan disimpan tetapi belum dipublikasikan kepada pegawai. Tinjauan/anonimisasi catatan belum dibuat.
- Login memakai akun lokal. SIMPEG, SSO, SKP/eKinerja, notifikasi email/WhatsApp, dan manajemen talenta belum diintegrasikan.
- Unit kerja berupa nama unit. Admin OPD hanya mengelola pegawai, struktur, dan penugasan OPD-nya; periode, bobot, indikator tematik, OPD, dan pengaturan hanya dikelola administrator kabupaten.
- Data audit tersedia di tabel `audit_logs`, belum ada halaman penampil audit.
- Tujuh indikator BerAKHLAK tetap; penggantiannya setelah ada data memerlukan versi instrumen/migrasi, bukan pengeditan langsung. Lima indikator tematik melekat pada tiap periode: administrator kabupaten mengganti tema dan butirnya pada laman Metode selama periode draf, dan sejak periode dibuka butir tematik terkunci. Periode yang dibuat sebelum v1.9 tidak memiliki indikator tematik. Bobot komposisi dapat diubah administrator kabupaten per periode selama draf dan terkunci sejak periode dibuka.

## Struktur proyek

| Lokasi | Fungsi |
| --- | --- |
| public/index.php | Halaman utama PHP |
| public/api.php | Endpoint JSON dan pengamanan HTTP |
| public/assets/app.js | Antarmuka bersama PHP dan pratinjau |
| resources/demo.js | Adapter pratinjau lokal, disalin ke dist/ saat build dan hanya dipanggil dalam mode demo |
| public/assets/app.css | CSS Tailwind/daisyUI hasil kompilasi |
| public/assets/theme.js | Pemasang tema terang/gelap sebelum halaman digambar |
| app/Api.php | Autentikasi, akses, dan operasi transaksi |
| app/Scoring.php | Validasi komposisi, jawaban, dan perhitungan |
| app/Database.php | Koneksi PDO MySQL |
| database/schema.sql | Struktur database dan tujuh indikator BerAKHLAK |
| database/upgrade-1.1-struktur.sql | Migrasi kolom supervisor_id untuk instalasi yang dibuat sebelum fitur struktur organisasi |
| database/upgrade-1.2-opd.sql | Migrasi multi-OPD (tabel opd, settings, employees.opd_id, peran admin_opd); tiap nilai unit lama menjadi satu OPD |
| database/upgrade-1.8-bobot.sql | Kolom periods.weights: bobot komposisi tiap periode (diubah di laman Metode selama draf) |
| database/upgrade-1.9-tematik.sql | Indikator tematik per periode: kolom indicators.category dan indicators.period_id, periods.theme, ID indikator SMALLINT |
| database/tematik-triwulan-iv-2026.sql | Lima indikator tematik Triwulan IV 2026, tema Pemberantasan Korupsi |
| config/app.php | Pembacaan konfigurasi lingkungan |
| scripts/install.php | Instalasi awal melalui CLI |
| scripts/seed-demo.php | Seeder data fiktif |
| scripts/import-siasn.py | Impor OPD, pegawai, akun, dan atasan langsung dari ekspor SIASN |
| resources/app.css | Sumber tema dan stylesheet |
| dist/ | Pratinjau statis |
| tests/scoring.php | Pengujian aturan penilaian, termasuk nilai akhir dengan indikator tematik |
| tests/*_flows.php | Pengujian alur HTTP terhadap database uji, mis. `thematic_flows.php` untuk indikator tematik |
| e2e/ | Pengujian browser Playwright, mis. `tematik.spec.ts` untuk formulir tematik dan formulir 12 indikator |
| docs/VALIDASI.md | Hasil verifikasi dan langkah uji server |

## Mengubah tampilan

```bash
npm ci
npm run build
```

Perintah membangun CSS lokal dan menyalin antarmuka ke `dist/`. Produksi tidak membutuhkan CDN. Sumber dan versi pasti dependency tercatat dalam `package-lock.json`. Dependensi tampilan memakai [instalasi resmi daisyUI](https://daisyui.com/docs/install/) dan [Tailwind CLI](https://tailwindcss.com/docs/installation/tailwind-cli). Ikon berasal dari Lucide. Lisensi dependensi disertakan pada `THIRD_PARTY_NOTICES.md`.

Warna di `resources/app.css` ditulis sebagai `var(--ui-token, #warna-terang)`. Token `--ui-*` hanya didefinisikan pada blok tema gelap (`:root[data-theme=dark]`), sehingga tema terang memakai nilai cadangan. Warna baru mengikuti pola yang sama: pakai token yang sudah ada, atau tambahkan token beserta nilai gelapnya pada blok tersebut.

## Pengujian perhitungan

```bash
php tests/scoring.php
```

Pengujian meliputi tiga rumus bobot, draf, jawaban tidak lengkap, skala, kelompok kecil, komposisi tidak didukung, jumlah penilai dalam kelompok, serta indikator tematik: nilai akhir rata-rata 12 indikator, rincian per kategori, dan penolakan jawaban tanpa butir tematik.

Regresi lengkap (unit, integrasi DB, alur HTTP, dan browser) memakai database `sikap360_test` dan server lokal yang sudah berjalan:

```bash
DB_DATABASE=sikap360_test APP_URL=http://127.0.0.1:8791 php -S 127.0.0.1:8791 -t public
bash .qa/bin/run-all.sh http://127.0.0.1:8791
```
