<?php
// QA — struktur organisasi: supervisor_set, employee_save dengan supervisor_id, assignment_generate. Prasyarat sama dengan functional_flows.php (reseed dulu).
declare(strict_types=1);
require __DIR__ . '/bootstrap.php'; require __DIR__ . '/http_client.php';
$pdo = qa_pdo(); $q = static fn(string $sql) => $pdo->query($sql)->fetch();

$asn = new QaHttp('asn3'); $asn->login('pegawai3@example.test');
[$c] = $asn->call('supervisor_set', ['employee_id' => 3, 'supervisor_id' => 14]); check($c === 403, "S0 ASN tidak boleh mengubah atasan → 403 ($c)");
[$c] = $asn->call('assignment_generate', ['period_id' => 1]); check($c === 403, "S0b ASN tidak boleh membuat penugasan → 403 ($c)");

$adm = new QaHttp('adm'); check($adm->login('pegawai1@example.test') === 200, 'S1 login admin');
$disdik = (int) $q("SELECT id FROM opd WHERE code='DISDIKBUD'")['id'];
$emp = $adm->bootstrap()['employees']; $byId = array_column($emp, null, 'id');
check($byId[1]['supervisor_id'] === 2 && $byId[2]['supervisor_id'] === 14 && $byId[14]['supervisor_id'] === null && $byId[9]['supervisor_id'] === 1, 'S2 bootstrap admin memuat supervisor_id dari seeder');

[$c] = $adm->call('supervisor_set', ['employee_id' => 3, 'supervisor_id' => 3]); check($c === 422, "S3 atasan diri sendiri → 422 ($c)");
[$c] = $adm->call('supervisor_set', ['employee_id' => 3, 'supervisor_id' => 999]); check($c === 404, "S4 atasan tidak ada → 404 ($c)");
[$c] = $adm->call('supervisor_set', ['employee_id' => 999, 'supervisor_id' => 2]); check($c === 404, "S5 pegawai tidak ada → 404 ($c)");
[$c] = $adm->call('supervisor_set', ['employee_id' => 14, 'supervisor_id' => 9]); check($c === 409 && $q('SELECT supervisor_id s FROM employees WHERE id=14')['s'] === null, "S6 siklus (Kepala Dinas di bawah cucu bawahannya) → 409, tidak berubah ($c)");
[$c] = $adm->call('supervisor_set', ['employee_id' => 2, 'supervisor_id' => 1]); check($c === 409, "S7 siklus langsung (2↔1) → 409 ($c)");
[$c] = $adm->call('supervisor_set', ['employee_id' => 3, 'supervisor_id' => 1]); check($c === 200 && $q('SELECT supervisor_id s FROM employees WHERE id=3')['s'] === 1, "S8 pindah atasan sah → 200 ($c)");
[$c] = $adm->call('supervisor_set', ['employee_id' => 3, 'supervisor_id' => null]); check($c === 200 && $q('SELECT supervisor_id s FROM employees WHERE id=3')['s'] === null, "S9 lepas atasan (null) → 200 ($c)");
[$c] = $adm->call('supervisor_set', ['employee_id' => 3, 'supervisor_id' => 2]); check($c === 200, "S10 kembalikan atasan 3 → 2 ($c)");
check((int) $q("SELECT COUNT(*) n FROM audit_logs WHERE action='supervisor_changed' AND entity_id=3")['n'] === 3, 'S11 tiga perubahan atasan tercatat di audit');

[$c, $b] = $adm->call('employee_save', ['name' => 'Pegawai Baru', 'nip' => '199001012020011001', 'position' => 'Pelaksana', 'opd_id' => $disdik, 'unit' => 'Dinas', 'grade' => 'III/a', 'email' => 'baru@example.test', 'password' => 'PasswordAman123', 'supervisor_id' => 1]);
check($c === 200 && $q('SELECT supervisor_id s FROM employees WHERE id=' . (int) $b['id'])['s'] === 1, "S12 tambah pegawai dengan atasan → tersimpan ($c)"); $new = (int) $b['id'];
[$c] = $adm->call('employee_save', ['id' => 1, 'name' => $byId[1]['name'], 'nip' => $byId[1]['nip'], 'position' => $byId[1]['position'], 'opd_id' => $disdik, 'unit' => $byId[1]['unit'], 'grade' => $byId[1]['grade'], 'email' => $byId[1]['email'], 'password' => '', 'supervisor_id' => $new]);
check($c === 409 && $q('SELECT supervisor_id s FROM employees WHERE id=1')['s'] === 2, "S13 edit pegawai dengan atasan bawahannya sendiri → 409 (siklus lewat employee_save) ($c)");
$pdo->exec("DELETE FROM users WHERE employee_id=$new"); $pdo->exec("DELETE FROM employees WHERE id=$new");

// assignment_generate
[$c] = $adm->call('assignment_generate', ['period_id' => 1]); check($c === 409, "S14 generate pada periode open → 409 ($c)");
[$c, $b] = $adm->call('period_create', ['year' => 2030, 'quarter' => 1]); $pid = (int) $b['id']; check($c === 200, "S15 periode draf baru ($c)");
[$c, $b] = $adm->call('assignment_generate', ['period_id' => $pid]);
// OPD 1: 14 → 2 → {1,3..8}; 1 → {9..13}. Subjek 2: atasan 14 + bawahan 7 → 8. Subjek 1: atasan 2 + rekan 6 + bawahan 5 → 12. Subjek 3..8: atasan + 6 rekan → 42. Subjek 9..13: atasan + 4 rekan → 25. Subtotal 87; 14 dilewati.
// DINKES: 16 (atasan 15 + bawahan 4) 5; 17,18,19,50 (atasan + 3 rekan) 16 → 21; peringatan 15.
// BKPSDM: 21 (1+3+5) 9; 22 (1+3+4) 8; 23, 24 (1+3) 8; 25,26,32,33,34 (1+4) 25; 27–30 (1+3) 16 → 66; peringatan 20, 24 (bawahan 1), 31 (belum sah).
// DISKOMINFO: 36 (1+3+4) 8; 37, 39 (1+3) 8; 38 (1+3+4) 8; 40,47,48,49 dan 42–45 (1+3) 32 → 56; peringatan 35, 37 & 39 (bawahan 1), 41, 46 (belum sah).
// SETDA: 52 (atasan 51 + bawahan 4) 5; 53 (1+3+4) 8; 54,55,56 (1+3) 12; 57–60 (1+3) 16 → 41; peringatan 51. Total 87+21+66+56+41 = 271, peringatan 11.
check($c === 200 && $b['created'] === 271 && $b['skipped'] === 0 && count($b['warnings']) === 11 && count(array_filter($b['warnings'], static fn($w) => str_contains($w, 'Bambang'))) === 1 && count(array_filter($b['warnings'], static fn($w) => str_contains($w, 'belum sah'))) === 3 && count(array_filter($b['warnings'], static fn($w) => str_contains($w, 'tanpa atasan'))) === 5, "S16 generate seluruh OPD → 271 penugasan, 11 peringatan ($c, " . json_encode($b) . ')');
$r = $q("SELECT COUNT(*) n, SUM(rater_role='atasan') a, SUM(rater_role='rekan') r, SUM(rater_role='bawahan') b FROM assignments WHERE period_id=$pid");
check((int) $r['n'] === 271 && (int) $r['a'] === 52 && (int) $r['r'] === 178 && (int) $r['b'] === 41, "S17 komposisi DB: 52 atasan, 178 rekan, 41 bawahan (" . json_encode($r) . ')');
check((int) $q("SELECT COUNT(*) n FROM assignments WHERE period_id=$pid AND subject_id=1 AND rater_role='bawahan'")['n'] === 5 && (int) $q("SELECT COUNT(*) n FROM assignments WHERE period_id=$pid AND subject_id=2 AND rater_role='bawahan'")['n'] === 7, 'S18 bawahan subjek 1 = 5, subjek 2 = 7');
[$c, $b] = $adm->call('assignment_generate', ['period_id' => $pid]); check($c === 200 && $b['created'] === 0 && $b['skipped'] === 271, "S19 generate ulang idempoten: 0 dibuat, 271 dilewati ($c)");
[$c] = $adm->call('period_status', ['period_id' => $pid, 'status' => 'open']); check($c === 200 && $q("SELECT status s FROM periods WHERE id=$pid")['s'] === 'open', "S20 periode hasil generate lolos validasi komposisi saat dibuka ($c)");
[$c] = $adm->call('period_status', ['period_id' => $pid, 'status' => 'closed']);

// Kelompok kecil dilewati: 9..13 pindah ke atasan 3, kecuali 9 & 10 tetap di 1 → subjek 9 punya 1 rekan (10) dan tanpa bawahan → hanya atasan → dilewati seluruhnya.
foreach ([11, 12, 13] as $id) $adm->call('supervisor_set', ['employee_id' => $id, 'supervisor_id' => 3]);
[$c, $b] = $adm->call('period_create', ['year' => 2031, 'quarter' => 1]); $pid2 = (int) $b['id'];
[$c, $b] = $adm->call('assignment_generate', ['period_id' => $pid2]);
$n9 = $q("SELECT COUNT(*) n, GROUP_CONCAT(rater_role) r FROM assignments WHERE period_id=$pid2 AND subject_id=9");
check($c === 200 && (int) $n9['n'] === 0 && count(array_filter($b['warnings'], static fn($w) => str_contains($w, 'Fitri') && str_contains($w, 'belum sah'))) === 1, "S21 subjek 9 (rekan 1, bawahan 0) dilewati seluruhnya dengan peringatan komposisi (" . json_encode($n9) . ')');
$n1 = $q("SELECT SUM(rater_role='bawahan') b FROM assignments WHERE period_id=$pid2 AND subject_id=1");
check((int) $n1['b'] === 0 && count(array_filter($b['warnings'], static fn($w) => str_contains($w, 'Dina') && str_contains($w, 'bawahan'))) === 1, 'S22 bawahan 2 orang dilewati dengan peringatan');
qa_summary();
