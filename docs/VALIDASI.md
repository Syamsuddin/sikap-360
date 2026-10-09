# Validasi SIKAP 360

Tanggal: 12 September 2026.

## Pemeriksaan yang telah dijalankan

| Pemeriksaan | Hasil |
| --- | --- |
| Parsing sembilan berkas PHP dengan PHP 8.3 melalui runtime WebAssembly | Lulus |
| Pengujian Scoring.php | 17 kasus lulus |
| Syntax JavaScript antarmuka dan adapter demo | Lulus |
| Kompilasi Tailwind 4 dan daisyUI 5 | Lulus |
| Render struktur dashboard dan ikon dengan jsdom | Lulus |
| Pencarian pegawai, tujuh input indikator, progres, dan simpan draf | Lulus pada adapter demo |
| Konfirmasi kirim dan penguncian formulir terkirim | Lulus pada adapter demo |
| Hasil belum dipublikasikan disembunyikan, periode terdahulu menampilkan tujuh indikator | Lulus pada adapter demo |
| Ganti peran demo, tambah pegawai, dan tidak menyimpan kata sandi demo | Lulus |
| Membuat periode serta memperbarui pilihan periode | Lulus pada adapter demo |
| Registrasi antarmuka agen, pembacaan status, dan penolakan input tidak valid | Lulus dalam konteks DOM simulasi |
| Kesalahan runtime DOM pada alur utama | Tidak ditemukan |

Pengujian DOM tidak mengukur tata letak piksel dan tidak menggantikan pengujian browser asli. Lingkungan pengerjaan tidak menyediakan server MySQL atau Docker yang berjalan. Koneksi database, pemasangan container, integrasi API dengan MySQL, locking antar-koneksi, dan sesi HTTP PHP belum diuji langsung pada server MySQL. PHP runtime dipakai untuk memeriksa sintaks dan menjalankan perhitungan, bukan untuk menyatakan seluruh backend telah lulus integrasi.

## Uji penerimaan pada server tujuan

Gunakan database uji yang terpisah dari data ASN sebenarnya. Langkah 6–8 dan 20–23 diperbarui untuk indikator tematik (v1.9).

1. Jalankan instalasi demo, login dengan akun admin dan akun ASN di dua sesi browser terpisah.
2. Pastikan akun ASN tidak dapat menjalankan employee_save, period_status, atau assignment_create. Harapan: HTTP 403.
3. Kirim save_draft dengan ID tugas akun lain. Harapan: ditolak, jawaban pemilik tugas tidak berubah.
4. Kirim mutasi tanpa CSRF atau dengan token salah. Harapan: HTTP 403.
5. Isi draf sebagian, keluar, login lagi. Draf tetap tersimpan.
6. Kirim nilai 0, 6, pecahan, ID indikator tidak dikenal, dan ID indikator tematik milik periode lain. Harapan: HTTP 422 tanpa perubahan data.
7. Coba mengirim draf dengan enam jawaban, dan pada periode bertematik draf dengan tujuh jawaban BerAKHLAK saja. Harapan: seluruh pengiriman ditolak dengan pesan jumlah indikator yang wajib diisi.
8. Kirim jawaban valid untuk seluruh indikator periode (tujuh, atau 12 bila periode bertematik). Pastikan submitted_at terisi dan tugas terkunci.
9. Ulangi pengiriman tugas yang sama. Harapan: HTTP 409 dan tidak ada duplikasi.
10. Uji dua sesi mengedit versi draf yang sama. Penyimpanan pertama berhasil, kedua menerima HTTP 409.
11. Kirim batch berisi satu draf lengkap dan satu tugas tidak valid. Pastikan transaksi membatalkan seluruh batch.
12. Tutup periode dan coba menyimpan/kirim dari sesi lain. Harapan: ditolak. Buka kembali periode yang belum dipublikasikan untuk melanjutkan.
13. Coba membuka periode dengan satu rekan atau penilai diri sendiri. Harapan: ditolak.
14. Coba publikasi ketika masih ada tugas belum terkirim. Harapan: ditolak.
15. Lengkapi seluruh tugas, tutup, publikasikan, dan bandingkan hasil dengan hitungan manual menurut kelompok.
16. Periksa respons bootstrap akun ASN. Data penilai masuk individual, kata sandi, dan jawaban ASN lain tidak boleh ada.
17. Ganti kata sandi pada satu sesi. Sesi lama lain wajib diminta login kembali.
18. Verifikasi HTTPS, cookie Secure/HttpOnly/SameSite, document root public, akses .env, dan backup/restore MySQL.
19. Uji Chrome, Firefox, Safari, dan layar ponsel. Periksa menu, tabel bergulir, radio, dialog, serta cetak hasil.
20. Pada periode draf, isi tema dan lima indikator tematik di laman Metode sebagai administrator kabupaten. Coba juga empat butir, nama ganda, dan uraian kosong. Harapan: isian valid tersimpan dan tercatat di audit (thematic_saved); isian tidak valid HTTP 422 tanpa perubahan data.
21. Kirim thematic_save dari akun Admin OPD dan ASN. Harapan: HTTP 403. Kirim thematic_save untuk periode yang sudah dibuka. Harapan: HTTP 409.
22. Buat periode baru setelah periode bertematik. Harapan: tema dan lima butir tematik tersalin dengan ID baru, dan mengubah salinan tidak mengubah periode asal.
23. Lengkapi dan publikasikan periode bertematik. Harapan: formulir menampilkan dua kelompok bernomor 1–12, nilai akhir sama dengan rata-rata 12 indikator hitungan manual, dan halaman hasil menampilkan rata-rata BerAKHLAK dan tematik.
24. Buka Dashboard admin sebagai administrator kabupaten, lalu bandingkan kartu dan tabel per OPD dengan hitungan manual dari daftar penugasan. Akun Admin OPD dan ASN tidak melihat menu ini, dan admin_dashboard dari akun tersebut menghasilkan HTTP 403.
25. Pada laman Periode & penilai, buka periode draf dari baris tabel ketika periode lain masih aktif. Harapan: dialog memperingatkan periode aktif lain. Hapus periode draf tanpa penugasan dari tabel; coba hapus periode aktif dan draf yang sudah memiliki penugasan. Harapan: draf kosong terhapus dan tercatat di audit (period_deleted), sisanya HTTP 409 tanpa perubahan data.
26. Nonaktifkan pegawai yang sedang dinilai dan seorang penilai di periode aktif. Harapan: pegawai nonaktif tidak dapat masuk, tugas menilainya hilang dari daftar penilai (simpan/kirim 409), progres dan Dashboard admin tidak menghitungnya, distribusi penilai menandainya "Dikecualikan", dan publikasi tidak tertahan oleh tugas penilai nonaktif. Aktifkan kembali dan pastikan semuanya berlaku lagi.

Uji otomatis perhitungan dapat diulang dengan `php tests/scoring.php`. Bagian tematik pada langkah 6–8 serta langkah 20–23 juga dijalankan otomatis oleh `tests/thematic_flows.php` dan `e2e/tematik.spec.ts` (lihat Pengujian perhitungan di README). Langkah 24 dijalankan otomatis oleh `tests/admin_dashboard_flows.php` dan `e2e/dashboard-admin.spec.ts`, langkah 25 oleh `tests/period_flows.php` dan `e2e/periode-aksi.spec.ts`, langkah 26 oleh `tests/employee_status_flows.php` dan `e2e/pegawai-status.spec.ts`.
