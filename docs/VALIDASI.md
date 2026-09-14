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

Gunakan database uji yang terpisah dari data ASN sebenarnya.

1. Jalankan instalasi demo, login dengan akun admin dan akun ASN di dua sesi browser terpisah.
2. Pastikan akun ASN tidak dapat menjalankan employee_save, period_status, atau assignment_create. Harapan: HTTP 403.
3. Kirim save_draft dengan ID tugas akun lain. Harapan: ditolak, jawaban pemilik tugas tidak berubah.
4. Kirim mutasi tanpa CSRF atau dengan token salah. Harapan: HTTP 403.
5. Isi draf sebagian, keluar, login lagi. Draf tetap tersimpan.
6. Kirim nilai 0, 6, pecahan, dan ID indikator tidak dikenal. Harapan: HTTP 422 tanpa perubahan data.
7. Coba mengirim draf dengan enam jawaban. Harapan: seluruh pengiriman ditolak.
8. Kirim tujuh jawaban valid. Pastikan submitted_at terisi dan tugas terkunci.
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

Uji otomatis perhitungan dapat diulang dengan `php tests/scoring.php`.
