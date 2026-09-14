-- SIKAP 360 v1.1: struktur organisasi (atasan langsung). Jalankan sekali pada database yang dipasang sebelum v1.1.
-- Instalasi baru tidak memerlukan berkas ini karena schema.sql sudah memuat kolom supervisor_id.
ALTER TABLE employees
 ADD COLUMN supervisor_id BIGINT UNSIGNED NULL AFTER email,
 ADD KEY supervisor(supervisor_id),
 ADD FOREIGN KEY(supervisor_id) REFERENCES employees(id) ON DELETE SET NULL;
