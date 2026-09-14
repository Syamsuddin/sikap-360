-- SIKAP 360 v1.2: multi-OPD dalam satu kabupaten. Jalankan sekali pada database yang dipasang sebelum v1.2 (setelah upgrade-1.1 bila perlu).
-- Setiap nilai kolom employees.unit yang berbeda menjadi satu OPD; kolom unit tetap tersimpan sebagai unit kerja di dalam OPD.
CREATE TABLE IF NOT EXISTS settings (name VARCHAR(60) PRIMARY KEY, value VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO settings(name,value) VALUES('kabupaten_name','Hulu Sungai Selatan');
CREATE TABLE IF NOT EXISTS opd (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(160) NOT NULL UNIQUE,
 code VARCHAR(20) NOT NULL UNIQUE,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO opd(name,code) SELECT unit, CONCAT('OPD', LPAD(ROW_NUMBER() OVER (ORDER BY unit), 3, '0')) FROM (SELECT DISTINCT unit FROM employees) u;
ALTER TABLE employees ADD COLUMN opd_id BIGINT UNSIGNED NULL AFTER position;
UPDATE employees e JOIN opd o ON o.name=e.unit SET e.opd_id=o.id;
ALTER TABLE employees MODIFY opd_id BIGINT UNSIGNED NOT NULL, ADD KEY opd(opd_id), ADD FOREIGN KEY(opd_id) REFERENCES opd(id) ON DELETE RESTRICT;
ALTER TABLE users MODIFY role ENUM('admin','admin_opd','asn') NOT NULL DEFAULT 'asn';
