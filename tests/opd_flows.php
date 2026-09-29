<?php
// QA — multi-OPD: info publik, pengaturan kabupaten, OPD CRUD, lingkup admin OPD, larangan lintas OPD. Prasyarat sama dengan functional_flows.php (reseed dulu).
declare(strict_types=1);
require __DIR__ . '/bootstrap.php'; require __DIR__ . '/http_client.php';
$pdo = qa_pdo(); $q = static fn(string $sql) => $pdo->query($sql)->fetch();

// info publik
$guest = new QaHttp('guest'); [$c, $b] = $guest->call('info', [], ['nocsrf' => true]); check($c === 200 && $b['kabupaten_name'] === 'Hulu Sungai Selatan', "O1 info publik: kabupaten default Hulu Sungai Selatan ($c)");

// admin kabupaten
$adm = new QaHttp('adm'); check($adm->login('pegawai1@example.test') === 200, 'O2 login admin kabupaten');
$boot = $adm->bootstrap(); check(count($boot['opds']) === 5 && count($boot['employees']) === 60 && $boot['settings']['kabupaten_name'] === 'Hulu Sungai Selatan' && $boot['user']['opd_name'] === 'Dinas Pendidikan dan Kebudayaan', 'O3 bootstrap admin: 5 OPD, 60 pegawai, settings, opd_name pengguna');
$kominfo = (int) $q("SELECT id FROM opd WHERE code='DISKOMINFO'")['id']; $disdik = (int) $q("SELECT id FROM opd WHERE code='DISDIKBUD'")['id'];
check((int) array_values(array_filter($boot['opds'], static fn($o) => $o['code'] === 'DISKOMINFO'))[0]['employee_count'] === 15, 'O4 employee_count DISKOMINFO = 15');
[$c] = $adm->call('settings_save', ['kabupaten_name' => '']); check($c === 422, "O5 nama kabupaten kosong → 422 ($c)");
[$c] = $adm->call('settings_save', ['kabupaten_name' => 'Hulu Sungai Utara']); [$c2, $b] = $guest->call('info', [], ['nocsrf' => true]);
check($c === 200 && $b['kabupaten_name'] === 'Hulu Sungai Utara', "O6 ganti nama kabupaten → tampil di info publik ($c)");
$adm->call('settings_save', ['kabupaten_name' => 'Hulu Sungai Selatan']);
[$c] = $adm->call('opd_save', ['name' => 'Dinas Sosial', 'code' => 'dinsos!']); check($c === 422, "O7 kode OPD tidak valid → 422 ($c)");
[$c, $b] = $adm->call('opd_save', ['name' => 'Dinas Sosial', 'code' => 'dinsos']); $dinsos = (int) ($b['id'] ?? 0); check($c === 200 && $q("SELECT code FROM opd WHERE id=$dinsos")['code'] === 'DINSOS', "O8 OPD baru dibuat, kode diubah ke huruf besar ($c)");
[$c] = $adm->call('opd_save', ['name' => 'Dinas Kesehatan', 'code' => 'LAIN']); check($c === 409, "O9 nama OPD duplikat → 409 ($c)");
[$c] = $adm->call('opd_save', ['id' => $kominfo, 'name' => 'Dinas Komunikasi dan Informatika', 'code' => 'DISKOMINFO', 'active' => 0]); check($c === 409, "O10 nonaktifkan OPD berpegawai → 409 ($c)");
[$c] = $adm->call('opd_save', ['id' => $dinsos, 'name' => 'Dinas Sosial', 'code' => 'DINSOS', 'active' => 0]); check($c === 200 && (int) $q("SELECT active FROM opd WHERE id=$dinsos")['active'] === 0, "O11 nonaktifkan OPD kosong → 200 ($c)");
$emp = ['name' => 'Pegawai Dinsos', 'nip' => '199001012020011009', 'position' => 'Pelaksana', 'unit' => 'Sekretariat', 'grade' => 'III/a', 'email' => 'dinsos@example.test', 'password' => 'KataSandiUji123'];
[$c] = $adm->call('employee_save', $emp + ['opd_id' => $dinsos]); check($c === 422, "O12 tambah pegawai ke OPD nonaktif → 422 ($c)");
[$c] = $adm->call('supervisor_set', ['employee_id' => 42, 'supervisor_id' => 2]); check($c === 200 && (int) $q('SELECT supervisor_id s FROM employees WHERE id=42')['s'] === 2, "O13 admin kabupaten boleh menetapkan atasan dari OPD lain (pimpinan lintas OPD) → 200 ($c)");
$adm->call('supervisor_set', ['employee_id' => 42, 'supervisor_id' => 38]);
[$c, $b] = $adm->call('employee_save', $emp + ['opd_id' => $kominfo, 'supervisor_id' => 36, 'role' => 'admin_opd']); $E = (int) ($b['id'] ?? 0);
check($c === 200 && (int) $q("SELECT opd_id FROM employees WHERE id=$E")['opd_id'] === $kominfo && $q("SELECT role FROM users WHERE employee_id=$E")['role'] === 'admin_opd', "O14 admin membuat pegawai DISKOMINFO berperan admin_opd ($c)");
[$c] = $adm->call('employee_save', $emp + ['id' => 38, 'opd_id' => $disdik, 'password' => '', 'nip' => 'DEMO-0038', 'email' => 'pegawai38@example.test']); check($c === 409, "O15 pindah OPD pegawai yang punya bawahan → 409 ($c)");
[$c] = $adm->call('employee_save', ['id' => 1, 'name' => 'Dina Puspitasari, S.Sos.', 'nip' => 'DEMO-0001', 'position' => 'Kasubbag', 'opd_id' => $disdik, 'unit' => 'Umum', 'grade' => 'IV/a', 'email' => 'pegawai1@example.test', 'password' => '', 'supervisor_id' => 2, 'role' => 'asn']);
check($c === 200 && $q('SELECT role FROM users WHERE employee_id=1')['role'] === 'admin', "O16 admin tidak dapat menurunkan peran akunnya sendiri ($c)");
[$c, $b] = $adm->call('period_create', ['year' => 2032, 'quarter' => 1]); $P = (int) $b['id'];
[$c] = $adm->call('assignment_create', ['period_id' => $P, 'subject_id' => 3, 'rater_id' => 17, 'rater_role' => 'rekan']); check($c === 422, "O17 penugasan lintas OPD → 422 ($c)");

// admin OPD (pegawai16 = Sekretaris Dinkes)
$opd = new QaHttp('opd36'); check($opd->login('pegawai36@example.test') === 200, 'O18 login admin OPD DISKOMINFO (pegawai36)');
$b = $opd->bootstrap(); $ids = array_map('intval', array_column($b['employees'], 'id'));
check($b['user']['role'] === 'admin_opd' && count($ids) === 16 && !in_array(1, $ids, true) && in_array(42, $ids, true) && count(array_unique(array_column($b['employees'], 'opd_id'))) === 1, 'O19 bootstrap admin OPD: hanya pegawai DISKOMINFO (15 + 1 baru), tanpa pegawai DISDIKBUD');
[$c, $al] = $opd->call('assignment_list', ['period_id' => (int) $b['period']['id'], 'opd_id' => 1]); check($c === 200 && $al['total'] === 56 && $al['stats']['total'] === 56 && count($al['rows']) === 10 && count(array_unique(array_column($al['rows'], 'opd_id'))) === 1, "O20 assignment_list admin OPD hanya subjek DISKOMINFO (56), opd_id kiriman diabaikan ($c)");
[$c] = $opd->call('supervisor_set', ['employee_id' => 3, 'supervisor_id' => 1]); check($c === 403, "O21 admin OPD ubah atasan pegawai OPD lain → 403 ($c)");
[$c] = $opd->call('supervisor_set', ['employee_id' => 42, 'supervisor_id' => 2]); check($c === 422, "O22 admin OPD pilih atasan dari OPD lain → 422 ($c)");
[$c] = $opd->call('supervisor_set', ['employee_id' => 42, 'supervisor_id' => 35]); check($c === 200 && (int) $q('SELECT supervisor_id s FROM employees WHERE id=42')['s'] === 35, "O23 admin OPD ubah atasan pegawai sendiri → 200 ($c)");
$opd->call('supervisor_set', ['employee_id' => 42, 'supervisor_id' => 38]);
[$c] = $opd->call('employee_save', ['id' => 3, 'name' => 'x', 'nip' => 'DEMO-0003', 'position' => 'p', 'unit' => 'u', 'grade' => 'g', 'email' => 'pegawai3@example.test', 'password' => '']); check($c === 403, "O24 admin OPD edit pegawai OPD lain → 403 ($c)");
[$c, $b] = $opd->call('employee_save', ['name' => 'Staf Kominfo', 'nip' => '199001012020011010', 'position' => 'Pelaksana', 'opd_id' => $disdik, 'unit' => 'u', 'grade' => 'g', 'email' => 'stafkominfo@example.test', 'password' => 'KataSandiUji123', 'role' => 'admin']);
$E2 = (int) ($b['id'] ?? 0); check($c === 200 && (int) $q("SELECT opd_id FROM employees WHERE id=$E2")['opd_id'] === $kominfo && $q("SELECT role FROM users WHERE employee_id=$E2")['role'] === 'asn', "O25 admin OPD tambah pegawai: opd_id dipaksa ke OPD sendiri, peran dipaksa asn ($c)");
foreach (['period_create' => ['year' => 2033, 'quarter' => 1], 'period_status' => ['period_id' => 1, 'status' => 'closed'], 'opd_save' => ['name' => 'X', 'code' => 'XX'], 'settings_save' => ['kabupaten_name' => 'Y']] as $a => $body) { [$c] = $opd->call($a, $body); check($c === 403, "O26 admin OPD $a → 403 ($c)"); }
[$c] = $opd->call('assignment_create', ['period_id' => $P, 'subject_id' => 3, 'rater_id' => 4, 'rater_role' => 'rekan']); check($c === 403, "O27 admin OPD tetapkan penilai OPD lain → 403 ($c)");
[$c] = $opd->call('assignment_create', ['period_id' => $P, 'subject_id' => 42, 'rater_id' => 43, 'rater_role' => 'rekan']); check($c === 200, "O28 admin OPD tetapkan penilai OPD sendiri → 200 ($c)");
$aid = (int) $q("SELECT id FROM assignments WHERE period_id=$P AND subject_id=42 AND rater_id=43")['id'];
$adm->call('assignment_create', ['period_id' => $P, 'subject_id' => 3, 'rater_id' => 4, 'rater_role' => 'rekan']); $aid2 = (int) $q("SELECT id FROM assignments WHERE period_id=$P AND subject_id=3 AND rater_id=4")['id'];
[$c] = $opd->call('assignment_delete', ['id' => $aid2]); check($c === 403, "O29 admin OPD hapus penugasan OPD lain → 403 ($c)");
[$c] = $opd->call('assignment_delete', ['id' => $aid]); check($c === 200, "O30 admin OPD hapus penugasan OPD sendiri → 200 ($c)");
[$c, $b] = $opd->call('assignment_generate', ['period_id' => $P, 'opd_id' => $disdik]);
// opd_id dari body diabaikan untuk admin OPD. DISKOMINFO + E (bawahan 36) + E2 (tanpa atasan):
// Rekan: pejabat 36–39 saling rekan; staf satu unit. 36: 1+3+5 = 9; 37, 39: 1+3 = 4 (+catatan bawahan 1); 38: 1+3+4 = 8; 40,47,48,49,E (staf Sekretariat): 1+4 = 25; 42–45: 16 → 66. Peringatan: 35, E2 (tanpa atasan), 37, 39 (bawahan 1), 41, 46 (belum sah) = 6.
$outside = (int) $q("SELECT COUNT(*) n FROM assignments a JOIN employees s ON s.id=a.subject_id WHERE a.period_id=$P AND s.opd_id<>$kominfo")['n'];
check($c === 200 && $b['created'] === 66 && count($b['warnings']) === 6 && $outside === 1, "O31 admin OPD generate: 66 penugasan DISKOMINFO saja, opd_id lain diabaikan ($c, " . json_encode($b) . ", luar=$outside)");
[$c, $b] = $adm->call('assignment_generate', ['period_id' => $P, 'opd_id' => $disdik]); check($c === 200 && $b['created'] === 74 && !array_filter($b['warnings'], static fn($w) => str_contains($w, 'DISKOMINFO')), "O32 admin kabupaten generate per OPD (DISDIKBUD): 74 dibuat (1 sudah ada), tanpa peringatan DISKOMINFO ($c, {$b['created']})");
[$c] = $adm->call('assignment_generate', ['period_id' => $P, 'opd_id' => 999]); check($c === 404, "O33 generate OPD fiktif → 404 ($c)");
// bersihkan
$pdo->exec("DELETE FROM assignments WHERE period_id=$P"); $pdo->exec("DELETE FROM periods WHERE id=$P");
foreach ([$E, $E2] as $x) { $pdo->exec("DELETE FROM users WHERE employee_id=$x"); $pdo->exec("DELETE FROM employees WHERE id=$x"); }
qa_summary();
