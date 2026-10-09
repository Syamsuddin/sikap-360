-- SIKAP 360 v1.9: indikator tematik per triwulan. Jalankan sekali pada database yang dipasang sebelum v1.9, sebelum kode v1.9 dipasang.
-- Tujuh indikator BerAKHLAK menjadi kategori 'berakhlak' (period_id NULL, berlaku untuk semua periode). Indikator kategori 'tematik' melekat pada satu periode
-- dan bertema periods.theme; administrator kabupaten mengubahnya di laman Metode selama periode draf, dan terkunci sejak periode dibuka.
-- Periode lama tidak memiliki indikator tematik, sehingga penilaiannya tetap tujuh indikator.
ALTER TABLE periods ADD COLUMN theme VARCHAR(120) NULL AFTER weights;
-- Nama constraint answers → indicators dibaca dari information_schema agar tidak bergantung pada penamaan otomatis.
SET @fk := (SELECT constraint_name FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='answers' AND column_name='indicator_id' AND referenced_table_name='indicators' LIMIT 1);
SET @sql := CONCAT('ALTER TABLE answers DROP FOREIGN KEY `', @fk, '`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
-- ID indikator diperlebar ke SMALLINT: tiap triwulan menambah lima baris tematik.
ALTER TABLE indicators
 MODIFY id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
 ADD COLUMN category ENUM('berakhlak','tematik') NOT NULL DEFAULT 'berakhlak' AFTER id,
 ADD COLUMN period_id BIGINT UNSIGNED NULL AFTER category,
 ADD KEY period_indicators(period_id),
 ADD FOREIGN KEY(period_id) REFERENCES periods(id) ON DELETE CASCADE;
ALTER TABLE answers
 MODIFY indicator_id SMALLINT UNSIGNED NOT NULL,
 ADD CONSTRAINT answers_ibfk_2 FOREIGN KEY(indicator_id) REFERENCES indicators(id) ON DELETE RESTRICT;
-- MySQL 9 ikut menghapus indeks bawaan saat foreign key dilepas, sehingga indeks baru bernama answers_ibfk_2; MySQL 8.0 mempertahankan indeks indicator_id.
-- Nama indeks diseragamkan menjadi indicator_id agar struktur sama dengan instalasi baru.
SET @idx := (SELECT index_name FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='answers' AND column_name='indicator_id' AND seq_in_index=1 AND index_name<>'PRIMARY' LIMIT 1);
SET @sql := IF(@idx='indicator_id','DO 0',CONCAT('ALTER TABLE answers RENAME INDEX `',@idx,'` TO indicator_id'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
