-- SIKAP 360. MySQL 8.0.16+ / InnoDB / utf8mb4.
-- Satu instalasi = satu kabupaten (nama di settings) dengan banyak OPD (Organisasi Perangkat Daerah).
CREATE TABLE IF NOT EXISTS settings (
 name VARCHAR(60) PRIMARY KEY,
 value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO settings(name,value) VALUES('kabupaten_name','Hulu Sungai Selatan');
CREATE TABLE IF NOT EXISTS opd (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(160) NOT NULL UNIQUE,
 code VARCHAR(20) NOT NULL UNIQUE,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS employees (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(160) NOT NULL,
 nip VARCHAR(18) NOT NULL UNIQUE,
 position VARCHAR(160) NOT NULL,
 opd_id BIGINT UNSIGNED NOT NULL,
 unit VARCHAR(160) NOT NULL,
 grade VARCHAR(80) NOT NULL DEFAULT '',
 email VARCHAR(160) NOT NULL UNIQUE,
 supervisor_id BIGINT UNSIGNED NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY supervisor(supervisor_id),
 KEY opd(opd_id),
 FOREIGN KEY(opd_id) REFERENCES opd(id) ON DELETE RESTRICT,
 FOREIGN KEY(supervisor_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 employee_id BIGINT UNSIGNED NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','admin_opd','asn') NOT NULL DEFAULT 'asn',
 auth_version INT UNSIGNED NOT NULL DEFAULT 1,
 FOREIGN KEY(employee_id) REFERENCES employees(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS periods (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 start_date DATE NOT NULL,
 end_date DATE NOT NULL,
 status ENUM('draft','open','closed','published') NOT NULL DEFAULT 'draft',
 published_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CHECK(end_date >= start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS indicators (
 id TINYINT UNSIGNED PRIMARY KEY,
 name VARCHAR(80) NOT NULL,
 description TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS assignments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 period_id BIGINT UNSIGNED NOT NULL,
 subject_id BIGINT UNSIGNED NOT NULL,
 rater_id BIGINT UNSIGNED NOT NULL,
 rater_role ENUM('atasan','rekan','bawahan') NOT NULL,
 status ENUM('pending','draft','submitted') NOT NULL DEFAULT 'pending',
 version INT UNSIGNED NOT NULL DEFAULT 1,
 feedback TEXT NULL,
 submitted_at DATETIME NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY unique_rater(period_id,subject_id,rater_id),
 KEY rater_tasks(rater_id,period_id,status),
 FOREIGN KEY(period_id) REFERENCES periods(id) ON DELETE RESTRICT,
 FOREIGN KEY(subject_id) REFERENCES employees(id) ON DELETE RESTRICT,
 FOREIGN KEY(rater_id) REFERENCES employees(id) ON DELETE RESTRICT,
 CHECK(subject_id <> rater_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS answers (
 assignment_id BIGINT UNSIGNED NOT NULL,
 indicator_id TINYINT UNSIGNED NOT NULL,
 score TINYINT UNSIGNED NOT NULL,
 PRIMARY KEY(assignment_id,indicator_id),
 FOREIGN KEY(assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
 FOREIGN KEY(indicator_id) REFERENCES indicators(id) ON DELETE RESTRICT,
 CHECK(score BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 actor_user_id BIGINT UNSIGNED NULL,
 action VARCHAR(60) NOT NULL,
 entity_type VARCHAR(40) NOT NULL,
 entity_id BIGINT UNSIGNED NULL,
 metadata JSON NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY audit_time(created_at),
 FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS login_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 account_hash CHAR(64) NOT NULL,
 ip_hash CHAR(64) NOT NULL,
 attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY account_window(account_hash,attempted_at),
 KEY ip_window(ip_hash,attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO indicators(id,name,description) VALUES
(1,'Berorientasi Pelayanan','Memberikan pelayanan prima, ramah, cekatan, solutif, dapat diandalkan, serta melakukan perbaikan secara berkelanjutan.'),
(2,'Akuntabel','Melaksanakan tugas dengan jujur, bertanggung jawab, cermat, disiplin, dan berintegritas. Menggunakan kewenangan serta sumber daya secara bertanggung jawab.'),
(3,'Kompeten','Terus belajar dan mengembangkan kemampuan, membantu orang lain belajar, serta melaksanakan tugas dengan kualitas terbaik.'),
(4,'Harmonis','Menghargai perbedaan, peduli dan membantu orang lain, serta membangun lingkungan kerja yang kondusif.'),
(5,'Loyal','Memegang teguh Pancasila dan UUD 1945, mengutamakan kepentingan bangsa dan negara, serta menjaga nama baik ASN dan instansi.'),
(6,'Adaptif','Berinovasi, menyesuaikan diri menghadapi perubahan, dan bertindak proaktif dalam menyelesaikan pekerjaan.'),
(7,'Kolaboratif','Memberi kesempatan berbagai pihak untuk berkontribusi, terbuka dalam bekerja sama, serta menggerakkan sumber daya untuk tujuan bersama.');
