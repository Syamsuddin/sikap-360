<?php
// QA — atasan lintas OPD: Bupati (pejabat non-ASN, kode BUPATI-HSS) sebagai atasan kepala OPD, rekan sesama kepala OPD, dan batas admin OPD. Prasyarat sama dengan functional_flows.php (reseed dulu).
declare(strict_types=1);
require __DIR__ . '/bootstrap.php'; require __DIR__ . '/http_client.php';
$pdo = qa_pdo(); $q = static fn(string $sql) => $pdo->query($sql)->fetch();
// Kelompok penilai per subjek; ORDER BY kolom ENUM mengikuti urutan enum (atasan, rekan, bawahan).
$roles = static fn(int $period, int $subject): array => $pdo->query("SELECT rater_role, GROUP_CONCAT(rater_id ORDER BY rater_id) FROM assignments WHERE period_id=$period AND subject_id=$subject GROUP BY rater_role ORDER BY rater_role")->fetchAll(PDO::FETCH_KEY_PAIR);
$disdik = (int) $q("SELECT id FROM opd WHERE code='DISDIKBUD'")['id'];

// Bupati: OPD tingkat kabupaten + kode non-NIP
$adm = new QaHttp('adm'); check($adm->login('pegawai1@example.test') === 200, 'X1 login admin kabupaten');
[$c, $b] = $adm->call('opd_save', ['name' => 'Pemerintah Kabupaten Hulu Sungai Selatan', 'code' => 'PEMKAB-HSS']); $pemkab = (int) ($b['id'] ?? 0); check($c === 200 && $pemkab > 0, "X2 OPD tingkat kabupaten dibuat ($c)");
$bupati = ['name' => 'Bupati Uji', 'position' => 'BUPATI HULU SUNGAI SELATAN', 'opd_id' => $pemkab, 'unit' => 'Pemerintah Kabupaten Hulu Sungai Selatan', 'grade' => '-', 'email' => 'bupati@example.test', 'password' => 'KataSandiBupati123'];
[$c] = $adm->call('employee_save', $bupati + ['nip' => 'bupati-hss']); check($c === 422, "X3 kode non-NIP huruf kecil → 422 ($c)");
[$c] = $adm->call('employee_save', $bupati + ['nip' => '12345']); check($c === 422, "X4 angka yang bukan 18 digit tetap → 422 ($c)");
[$c, $b] = $adm->call('employee_save', $bupati + ['nip' => 'BUPATI-HSS']); $B = (int) ($b['id'] ?? 0); check($c === 200 && $B > 0, "X5 Bupati dengan kode BUPATI-HSS dibuat ($c)");
$bu = new QaHttp('bupati'); check($bu->login('BUPATI-HSS', 'KataSandiBupati123') === 200 && (new QaHttp('bupati2'))->login('bupati@example.test', 'KataSandiBupati123') === 200, 'X6 Bupati masuk dengan kode maupun email');

// Kepala OPD demo (14 DISDIKBUD, 15 DINKES, 20 BKPSDM, 35 DISKOMINFO, 51 SETDA) di bawah Bupati
foreach ([14, 15, 20, 35, 51] as $h) { [$c] = $adm->call('supervisor_set', ['employee_id' => $h, 'supervisor_id' => $B]); check($c === 200 && (int) $q("SELECT supervisor_id s FROM employees WHERE id=$h")['s'] === $B, "X7 kepala OPD $h → atasan Bupati lintas OPD ($c)"); }
[$c] = $adm->call('supervisor_set', ['employee_id' => $B, 'supervisor_id' => 14]); check($c === 409, "X8 siklus lintas OPD (Bupati di bawah kepala OPD) → 409 ($c)");
$emp = array_column($adm->bootstrap()['employees'], null, 'id');
check($emp[14]['supervisor_name'] === 'Bupati Uji' && (int) $emp[14]['supervisor_opd_id'] === $pemkab && $emp[2]['supervisor_name'] === $emp[14]['name'], 'X9 bootstrap memuat nama dan OPD atasan, termasuk atasan lintas OPD');

// Generate per OPD: kepala DISDIKBUD dinilai Bupati + 4 kepala OPD lain (bawahan hanya 1 → dilewati). 75 penugasan lama DISDIKBUD + 5 = 80.
[$c, $b] = $adm->call('period_create', ['year' => 2034, 'quarter' => 1]); $P = (int) $b['id'];
[$c, $b] = $adm->call('assignment_generate', ['period_id' => $P, 'opd_id' => $disdik]);
$outside = (int) $q("SELECT COUNT(*) n FROM assignments a JOIN employees s ON s.id=a.subject_id WHERE a.period_id=$P AND s.opd_id<>$disdik")['n'];
check($c === 200 && $b['created'] === 80 && $outside === 0 && $roles($P, 14) === ['atasan' => (string) $B, 'rekan' => '15,20,35,51'], "X10 generate per OPD: kepala DISDIKBUD → atasan Bupati + rekan 15,20,35,51; subjek tetap DISDIKBUD saja ($c, {$b['created']}, luar=$outside, " . json_encode($roles($P, 14)) . ')');
check(count(array_filter($b['warnings'], static fn($w) => str_contains($w, 'Bambang') && str_contains($w, 'bawahan hanya 1'))) === 1 && !array_filter($b['warnings'], static fn($w) => str_contains($w, 'tanpa atasan')), 'X11 peringatan: bawahan kepala DISDIKBUD hanya 1; tak ada lagi "tanpa atasan" di DISDIKBUD');
$t = $bu->bootstrap($P)['tasks']; check(count($t) === 1 && $t[0]['subject_id'] === 14 && $t[0]['rater_role'] === 'atasan', 'X12 Bupati mendapat tugas menilai kepala DISDIKBUD');

// Generate seluruh OPD: 230 lama + kepala 14, 15, 51 (1+4) dan 20, 35 (1+4+4) = 263. Peringatan: 17 − 5 "tanpa atasan" kepala + 1 Bupati + 3 "bawahan hanya 1" = 16.
[$c, $b] = $adm->call('period_create', ['year' => 2034, 'quarter' => 2]); $P2 = (int) $b['id'];
[$c, $b] = $adm->call('assignment_generate', ['period_id' => $P2]);
check($c === 200 && $b['created'] === 263 && count($b['warnings']) === 16 && $roles($P2, 35) === ['atasan' => (string) $B, 'rekan' => '14,15,20,51', 'bawahan' => '36,37,38,39'], "X13 generate seluruh OPD → 263; kepala DISKOMINFO Kondisi 1 ($c, " . json_encode(['created' => $b['created'] ?? null, 'warnings' => count($b['warnings'] ?? []), '35' => $roles($P2, 35)]) . ')');
[$c] = $adm->call('period_status', ['period_id' => $P2, 'status' => 'open']); check($c === 200, "X14 periode dengan kepala OPD lolos validasi komposisi saat dibuka ($c)");

// Admin OPD DISKOMINFO (pegawai36): melihat atasan kepalanya, tetapi tidak dapat mengubah atau menambah atasan lintas OPD
$opd = new QaHttp('opd36'); check($opd->login('pegawai36@example.test') === 200, 'X15 login admin OPD DISKOMINFO');
$list = $opd->bootstrap()['employees']; $e35 = array_column($list, null, 'id')[35];
check(!in_array($B, array_map('intval', array_column($list, 'id')), true) && $e35['supervisor_name'] === 'Bupati Uji', 'X16 admin OPD: Bupati tidak masuk daftar pegawai, tetapi nama atasan kepala OPD tampil');
[$c] = $opd->call('supervisor_set', ['employee_id' => 35, 'supervisor_id' => null]); check($c === 422 && (int) $q('SELECT supervisor_id s FROM employees WHERE id=35')['s'] === $B, "X17 admin OPD melepas atasan lintas OPD → 422, tetap Bupati ($c)");
[$c] = $opd->call('employee_save', ['id' => 35, 'name' => $e35['name'], 'nip' => $e35['nip'], 'position' => $e35['position'], 'unit' => $e35['unit'], 'grade' => 'Pembina Utama Muda (IV/c)', 'email' => $e35['email'], 'password' => '', 'supervisor_id' => $B]);
check($c === 200 && $q('SELECT grade g FROM employees WHERE id=35')['g'] === 'Pembina Utama Muda (IV/c)' && (int) $q('SELECT supervisor_id s FROM employees WHERE id=35')['s'] === $B, "X18 admin OPD menyunting kepala OPD dengan atasan Bupati dipertahankan → 200 ($c)");
[$c] = $opd->call('supervisor_set', ['employee_id' => 42, 'supervisor_id' => $B]); check($c === 422, "X19 admin OPD menetapkan atasan lintas OPD baru → 422 ($c)");
qa_summary();
