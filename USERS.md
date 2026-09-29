# Akun Login Seeder Demo SIKAP 360

Sumber: `scripts/seed-demo.php` (dibuat oleh `php scripts/install.php --demo`). Username = alamat email atau NIP (`DEMO-0001` untuk pegawai1, dan seterusnya). Kata sandi semua akun `SikapDemo2026!`. Jangan gunakan akun ini di produksi.

Kabupaten Hulu Sungai Selatan, lima OPD: DISDIKBUD (id 1–14), DINKES (15–19, 50), BKPSDM (20–34), DISKOMINFO (35–49), SETDA (51–60). Struktur organisasi tiap OPD terisi (kepala → sekretaris/kabid/kabag → kasubbag/fungsional → pelaksana); pasangan penilai OPD selain DISDIKBUD diturunkan dari struktur.

| No | Role | OPD | Nama | Jabatan | Username |
|---|---|---|---|---|---|
| 1 | admin (kabupaten) | DISDIKBUD | Dina Puspitasari, S.Sos. | Kasubbag Umum dan Kepegawaian | pegawai1@example.test |
| 2 | admin_opd | DISDIKBUD | Ahmad Fauzi, S.STP., M.Si. | Sekretaris Dinas | pegawai2@example.test |
| 3 | asn | DISDIKBUD | Rina Marlina, S.E. | Analis SDM Aparatur | pegawai3@example.test |
| 4 | asn | DISDIKBUD | Muhammad Rizki, S.Kom. | Pranata Komputer Ahli Muda | pegawai4@example.test |
| 5 | asn | DISDIKBUD | Siti Rahmah, S.Pd. | Analis SDM Aparatur | pegawai5@example.test |
| 6 | asn | DISDIKBUD | Budi Santoso, S.Sos. | Analis SDM Aparatur | pegawai6@example.test |
| 7 | asn | DISDIKBUD | Nur Aisyah, S.E. | Analis SDM Aparatur | pegawai7@example.test |
| 8 | asn | DISDIKBUD | Hendra Saputra, S.Kom. | Analis SDM Aparatur | pegawai8@example.test |
| 9 | asn | DISDIKBUD | Fitri Handayani, S.A.P. | Pelaksana | pegawai9@example.test |
| 10 | asn | DISDIKBUD | Arif Rahman, S.Kom. | Pelaksana | pegawai10@example.test |
| 11 | asn | DISDIKBUD | Dewi Lestari, S.E. | Pelaksana | pegawai11@example.test |
| 12 | asn | DISDIKBUD | Rizal Maulana, S.A.P. | Pelaksana | pegawai12@example.test |
| 13 | asn | DISDIKBUD | Nadia Putri, A.Md. | Pelaksana | pegawai13@example.test |
| 14 | asn | DISDIKBUD | Bambang Prasetyo, M.Si. | Kepala Dinas | pegawai14@example.test |
| 15 | asn | DINKES | drg. Lina Kartika, M.Kes. | Kepala Dinas | pegawai15@example.test |
| 16 | admin_opd | DINKES | Yusuf Hidayat, S.KM., M.M. | Sekretaris Dinas | pegawai16@example.test |
| 17 | asn | DINKES | Ratna Sari, S.KM. | Analis Kesehatan | pegawai17@example.test |
| 18 | asn | DINKES | Fajar Nugroho, S.Kep. | Perawat Ahli Pertama | pegawai18@example.test |
| 19 | asn | DINKES | Mira Anggraini, A.Md.Keb. | Bidan Terampil | pegawai19@example.test |
| 20 | asn | BKPSDM | Drs. H. Syahrial Anwar, M.A.P. | Kepala Badan | pegawai20@example.test |
| 21 | admin_opd | BKPSDM | Hj. Norhayati, S.Sos., M.M. | Sekretaris Badan | pegawai21@example.test |
| 22 | asn | BKPSDM | Rahmadi Noor, S.IP., M.Si. | Kepala Bidang Pengadaan, Pemberhentian dan Informasi Kepegawaian | pegawai22@example.test |
| 23 | asn | BKPSDM | Ery Wahyudi, S.STP., M.A.P. | Kepala Bidang Mutasi dan Promosi | pegawai23@example.test |
| 24 | asn | BKPSDM | Sri Wahyuni, S.Psi., M.Psi. | Kepala Bidang Pengembangan Kompetensi Aparatur | pegawai24@example.test |
| 25 | asn | BKPSDM | Muhammad Yani, S.E. | Kasubbag Umum dan Kepegawaian | pegawai25@example.test |
| 26 | asn | BKPSDM | Lisa Fitriani, S.E., Ak. | Kasubbag Perencanaan dan Keuangan | pegawai26@example.test |
| 27 | asn | BKPSDM | Abdul Hakim, S.A.P. | Analis Kepegawaian Ahli Muda | pegawai27@example.test |
| 28 | admin (kabupaten) | BKPSDM | Wahyu Kurniawan, S.Kom. | Pranata Komputer Ahli Pertama | pegawai28@example.test |
| 29 | asn | BKPSDM | Mariana Ulfah, A.Md. | Pengelola Data Kepegawaian | pegawai29@example.test |
| 30 | asn | BKPSDM | Rudi Hartono, S.Sos. | Analis Kepegawaian Ahli Pertama | pegawai30@example.test |
| 31 | asn | BKPSDM | Nurul Huda, S.Pd., M.Pd. | Analis Pengembangan Kompetensi | pegawai31@example.test |
| 32 | asn | BKPSDM | Zainal Abidin | Pelaksana | pegawai32@example.test |
| 33 | asn | BKPSDM | Rina Wulandari, A.Md. | Pelaksana | pegawai33@example.test |
| 34 | asn | BKPSDM | Taufik Hidayat | Pelaksana | pegawai34@example.test |
| 35 | asn | DISKOMINFO | Ir. H. Gusti Rahmat Fadillah, M.T. | Kepala Dinas | pegawai35@example.test |
| 36 | admin_opd | DISKOMINFO | Dra. Hj. Mahrita, M.M. | Sekretaris Dinas | pegawai36@example.test |
| 37 | asn | DISKOMINFO | Andi Saputra, S.I.Kom., M.I.Kom. | Kepala Bidang Informasi dan Komunikasi Publik | pegawai37@example.test |
| 38 | asn | DISKOMINFO | Deni Pratama, S.T., M.Kom. | Kepala Bidang Aplikasi Informatika | pegawai38@example.test |
| 39 | asn | DISKOMINFO | Herlina Sari, S.Si., M.Stat. | Kepala Bidang Persandian dan Statistik | pegawai39@example.test |
| 40 | asn | DISKOMINFO | Ahmad Rifani, S.A.P. | Kasubbag Umum dan Kepegawaian | pegawai40@example.test |
| 41 | asn | DISKOMINFO | Maya Sari Dewi, S.I.Kom. | Pranata Humas Ahli Muda | pegawai41@example.test |
| 42 | asn | DISKOMINFO | Rizky Ramadhan, S.Kom. | Pranata Komputer Ahli Muda | pegawai42@example.test |
| 43 | asn | DISKOMINFO | Fahmi Aziz, S.Kom. | Pranata Komputer Ahli Pertama | pegawai43@example.test |
| 44 | asn | DISKOMINFO | Indah Permatasari, S.T. | Analis Sistem Informasi dan Jaringan | pegawai44@example.test |
| 45 | asn | DISKOMINFO | Bayu Setiawan, S.ST. | Analis Keamanan Informasi | pegawai45@example.test |
| 46 | asn | DISKOMINFO | Yulia Rahmawati, S.Si. | Statistisi Ahli Pertama | pegawai46@example.test |
| 47 | asn | DISKOMINFO | Hendri Gunawan | Pelaksana | pegawai47@example.test |
| 48 | asn | DISKOMINFO | Siti Nurjanah, A.Md. | Pelaksana | pegawai48@example.test |
| 49 | asn | DISKOMINFO | Rahmat Hidayatullah | Pelaksana | pegawai49@example.test |
| 50 | asn | DINKES | Andi Firmansyah, S.Farm., Apt. | Apoteker Ahli Pertama | pegawai50@example.test |
| 51 | asn | SETDA | Drs. H. Muhammad Noor, M.AP. | Sekretaris Daerah | pegawai51@example.test |
| 52 | asn | SETDA | Hj. Siti Aminah, S.Sos., M.Si. | Asisten Administrasi Umum | pegawai52@example.test |
| 53 | asn | SETDA | Rahmadi, S.IP. | Kepala Bagian Umum | pegawai53@example.test |
| 54 | admin_opd | SETDA | Norhalimah, S.STP., M.AP. | Kepala Bagian Organisasi | pegawai54@example.test |
| 55 | asn | SETDA | Akhmad Rifani, S.H., M.H. | Kepala Bagian Hukum | pegawai55@example.test |
| 56 | asn | SETDA | Gusti Rina Wulandari, S.I.Kom. | Kepala Bagian Protokol dan Komunikasi Pimpinan | pegawai56@example.test |
| 57 | asn | SETDA | Muhammad Ilham, S.A.P. | Analis Tata Usaha | pegawai57@example.test |
| 58 | asn | SETDA | Nurul Hikmah, S.E. | Pengelola Keuangan | pegawai58@example.test |
| 59 | asn | SETDA | Riduan, A.Md. | Pengadministrasi Umum | pegawai59@example.test |
| 60 | asn | SETDA | Mahmudah, S.Sos. | Analis Tata Usaha | pegawai60@example.test |
