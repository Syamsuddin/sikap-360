-- SIKAP 360 v1.8: bobot komposisi penilai melekat pada tiap periode (periods.weights). Jalankan sekali pada database yang dipasang sebelum v1.8, sebelum kode v1.8 dipasang.
-- Administrator kabupaten mengubah bobot periode berstatus draf pada laman Metode; sejak periode dibuka bobotnya terkunci. Periode baru menyalin bobot periode terbaru.
-- Periode yang sudah ada diisi bobot bawaan (60/25/15, 75/25, 85/15), yaitu bobot yang dipakai sebelum v1.8.
ALTER TABLE periods ADD COLUMN weights JSON NULL AFTER published_at;
UPDATE periods SET weights=CAST('{"atasan,bawahan,rekan":{"atasan":60,"rekan":25,"bawahan":15},"atasan,rekan":{"atasan":75,"rekan":25},"atasan,bawahan":{"atasan":85,"bawahan":15}}' AS JSON) WHERE weights IS NULL;
-- Setelan bobot global dari pratinjau v1.8 tidak dipakai lagi.
DELETE FROM settings WHERE name='scoring_weights';
