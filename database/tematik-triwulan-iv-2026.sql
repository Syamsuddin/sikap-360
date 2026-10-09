-- Indikator tematik Triwulan IV 2026 (Oktober–Desember): Pemberantasan Korupsi. Jalankan setelah upgrade-1.9-tematik.sql.
-- Lima butir dirumuskan sebagai perilaku yang dapat diamati atasan, rekan, dan bawahan pada semua jenjang, dari pelaksana sampai kepala OPD dan Sekretaris Daerah.
-- Idempoten: tidak menambah apa pun bila periodenya belum ada atau sudah memiliki indikator tematik.
SET @period := (SELECT id FROM periods WHERE start_date='2026-10-01' AND end_date='2026-12-31' LIMIT 1);
SET @fresh := @period IS NOT NULL AND NOT EXISTS (SELECT 1 FROM indicators WHERE period_id=@period);
UPDATE periods SET theme='Pemberantasan Korupsi' WHERE id=@period AND @fresh;
INSERT INTO indicators(category,period_id,name,description)
SELECT 'tematik',@period,t.name,t.description FROM (
 SELECT 1 AS n,'Menolak Gratifikasi dan Pungutan Liar' AS name,'Menolak pemberian dalam bentuk apa pun (uang, barang, fasilitas, jamuan, atau titipan) yang berkaitan dengan jabatan atau tugasnya, dan melaporkannya sesuai ketentuan bila tidak dapat ditolak. Tidak meminta atau menerima imbalan di luar ketentuan atas layanan dan pekerjaan yang menjadi kewajibannya.' AS description
 UNION ALL SELECT 2,'Mengelola Konflik Kepentingan','Memisahkan kepentingan pribadi, keluarga, atau kelompok dari pekerjaan dinas. Menyampaikan kepada atasan bila memiliki kepentingan dalam suatu urusan, dan tidak ikut memutuskan, menilai, atau memproses urusan tersebut.'
 UNION ALL SELECT 3,'Jujur dalam Anggaran, Aset, dan Laporan','Menggunakan uang, barang, kendaraan, dan fasilitas kantor hanya untuk keperluan dinas. Menyusun atau menandatangani laporan, pertanggungjawaban keuangan, dan daftar hadir sesuai keadaan sebenarnya, tanpa kegiatan, perjalanan, atau harga yang direkayasa.'
 UNION ALL SELECT 4,'Transparan dan Taat Prosedur','Menjalankan pekerjaan, layanan, dan keputusan sesuai prosedur dan persyaratan yang berlaku, serta terbuka terhadap pertanyaan dan pemeriksaan. Tidak mempercepat, memperlambat, atau mengistimewakan suatu urusan karena hubungan pribadi atau imbalan.'
 UNION ALL SELECT 5,'Berani Menolak dan Melaporkan Penyimpangan','Menolak ajakan, tekanan, atau perintah yang melanggar aturan, termasuk dari atasan. Menegur atau melaporkan dugaan korupsi melalui saluran resmi, menindaklanjuti laporan yang diterima sesuai kewenangannya, serta melindungi, bukan menekan atau mengucilkan, rekan yang melapor.'
) t WHERE @fresh ORDER BY t.n;
