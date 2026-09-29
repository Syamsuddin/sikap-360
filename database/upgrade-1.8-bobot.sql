-- SIKAP 360 v1.8: bobot komposisi penilai dapat diubah administrator kabupaten pada laman Metode. Jalankan sekali pada database yang dipasang sebelum v1.8.
-- Bobot yang berlaku disimpan di settings.scoring_weights; periode menyimpan salinan bobot saat dipublikasikan agar hasil yang sudah diumumkan tidak ikut berubah.
-- Periode yang sudah dipublikasikan sebelum upgrade dibiarkan NULL dan tetap dihitung dengan bobot bawaan (60/25/15, 75/25, 85/15) yang dipakai saat itu.
ALTER TABLE periods ADD COLUMN weights JSON NULL AFTER published_at;
