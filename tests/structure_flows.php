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
// Rekan: pejabat (punya bawahan) → sesama pejabat seatasan; staf → staf lain satu unit (UNOR) dalam OPD yang sama.
// OPD 1: 14 → 2 → {1,3..8}; 1 → {9..13}. Subjek 2: atasan 14 + bawahan 7 → 8. Subjek 1: atasan 2 + bawahan 5 → 6 (tak ada pejabat lain di bawah 2). Subjek 3..8: atasan + 5 staf Sekretariat → 36. Subjek 9..13: atasan + 4 rekan → 25. Subtotal 75; 14 dilewati.
// DINKES: 16 (atasan 15 + bawahan 4) 5; 17,18,19,50 (atasan + 3 rekan) 16 → 21; peringatan 15.
// BKPSDM: pejabat di bawah 20 hanya 21, 22, 24 (23 tanpa bawahan dihitung staf) → rekan 2, dilewati. 21 (1+5 bawahan) 6; 22 (1+4) 5; 25,26,32,33,34 (1+4 staf Sekretariat) 25; 27–30 (1+3) 16 → 52; peringatan 20, 21 & 22 (rekan 2), 23 & 31 & 24 belum sah.
// DISKOMINFO: pejabat 36–39 saling rekan (3). 36, 38 (1+3+4) 16; 37, 39 (1+3; bawahan 1 dilewati) 8; 40,47,48,49 (1+3) 16; 42–45 (1+3) 16 → 56; peringatan 35, 37 & 39 (bawahan 1), 41, 46 belum sah.
// SETDA: 52, 53 (1+4 bawahan) 10; 57–60 (1+3) 16 → 26; peringatan 51, 54–56 belum sah (kepala bagian tanpa bawahan dihitung staf unitnya sendiri). Total 75+21+52+56+26 = 230, peringatan 17.
check($c === 200 && $b['created'] === 230 && $b['skipped'] === 0 && count($b['warnings']) === 17 && count(array_filter($b['warnings'], static fn($w) => str_contains($w, 'Bambang'))) === 1 && count(array_filter($b['warnings'], static fn($w) => str_contains($w, 'belum sah'))) === 8 && count(array_filter($b['warnings'], static fn($w) => str_contains($w, 'tanpa atasan'))) === 5, "S16 generate seluruh OPD → 230 penugasan, 17 peringatan ($c, " . json_encode($b) . ')');
$r = $q("SELECT COUNT(*) n, SUM(rater_role='atasan') a, SUM(rater_role='rekan') r, SUM(rater_role='bawahan') b FROM assignments WHERE period_id=$pid");
check((int) $r['n'] === 230 && (int) $r['a'] === 47 && (int) $r['r'] === 142 && (int) $r['b'] === 41, "S17 komposisi DB: 47 atasan, 142 rekan, 41 bawahan (" . json_encode($r) . ')');
check((int) $q("SELECT COUNT(*) n FROM assignments WHERE period_id=$pid AND subject_id=1 AND rater_role='bawahan'")['n'] === 5 && (int) $q("SELECT COUNT(*) n FROM assignments WHERE period_id=$pid AND subject_id=2 AND rater_role='bawahan'")['n'] === 7, 'S18 bawahan subjek 1 = 5, subjek 2 = 7');
[$c, $b] = $adm->call('assignment_generate', ['period_id' => $pid]); check($c === 200 && $b['created'] === 0 && $b['skipped'] === 230, "S19 generate ulang idempoten: 0 dibuat, 230 dilewati ($c)");
[$c] = $adm->call('period_status', ['period_id' => $pid, 'status' => 'open']); check($c === 200 && $q("SELECT status s FROM periods WHERE id=$pid")['s'] === 'open', "S20 periode hasil generate lolos validasi komposisi saat dibuka ($c)");
[$c] = $adm->call('period_status', ['period_id' => $pid, 'status' => 'closed']);

// Rekan staf mengikuti unit, bukan atasan: 11..13 pindah ke atasan 3 tetapi tetap di unit Subbagian Umum → subjek 9 tetap punya rekan staf 10..13.
// Subjek 1 kini hanya punya 2 bawahan dan 1 rekan pejabat (3) → komposisi belum sah, dilewati.
foreach ([11, 12, 13] as $id) $adm->call('supervisor_set', ['employee_id' => $id, 'supervisor_id' => 3]);
[$c, $b] = $adm->call('period_create', ['year' => 2031, 'quarter' => 1]); $pid2 = (int) $b['id'];
[$c, $b] = $adm->call('assignment_generate', ['period_id' => $pid2]);
$n9 = $q("SELECT COUNT(*) n, GROUP_CONCAT(CASE WHEN rater_role='rekan' THEN rater_id END ORDER BY rater_id) r FROM assignments WHERE period_id=$pid2 AND subject_id=9");
check($c === 200 && (int) $n9['n'] === 5 && $n9['r'] === '10,11,12,13', "S21 subjek 9: rekan satu unit 10..13 walau berbeda atasan (" . json_encode($n9) . ')');
$n1 = $q("SELECT SUM(rater_role='bawahan') b FROM assignments WHERE period_id=$pid2 AND subject_id=1");
check((int) $n1['b'] === 0 && count(array_filter($b['warnings'], static fn($w) => str_contains($w, 'Dina') && str_contains($w, 'bawahan'))) === 1, 'S22 bawahan 2 orang dilewati dengan peringatan');
qa_summary();
