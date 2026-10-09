<?php
// Status aktif pegawai (employee_status): hak akses, dan aturan "penilaian hanya bagi pegawai aktif". Penugasan yang melibatkan pegawai nonaktif dikecualikan
// (tidak dihapus) dari daftar tugas, nilai, progres, dan syarat publikasi, lalu berlaku lagi saat pegawai diaktifkan kembali. Black-box HTTP + asersi DB.
// Prasyarat sama dengan weights_flows.php; jalankan reseed dulu. Jalankan: DB_DATABASE=sikap360_test php tests/employee_status_flows.php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php'; require __DIR__ . '/http_client.php';
$pdo = qa_pdo(); $q = static fn(string $sql) => $pdo->query($sql)->fetch();
$id = static fn(int $subject, int $rater, int $period): int => (int) $pdo->query("SELECT id FROM assignments WHERE period_id=$period AND subject_id=$subject AND rater_id=$rater")->fetchColumn();
$active = static fn(int $e): int => (int) $q("SELECT active FROM employees WHERE id=$e")['active'];
$seven = static fn(int $s) => array_fill_keys(range(1, 7), $s);
$status = static fn(QaHttp $c, int $e, int $on) => $c->call('employee_status', ['employee_id' => $e, 'active' => $on]);
$assign = static function (QaHttp $adm, int $period, array $pairs): void { foreach ($pairs as [$r, $role]) $adm->call('assignment_create', ['period_id' => $period, 'subject_id' => 3, 'rater_id' => $r, 'rater_role' => $role]); };
$rate = static function (int $rater, int $period, int $score) use ($id, $seven): int { $c = new QaHttp("sr$rater$period"); $c->login("pegawai$rater@example.test"); $a = $id(3, $rater, $period); $c->call('save_draft', ['id' => $a, 'version' => 1, 'answers' => $seven($score), 'feedback' => '']); return $c->call('submit', ['period_id' => $period, 'ids' => [$a]])[0]; };

$adm = new QaHttp('sadm'); check($adm->login('pegawai1@example.test') === 200, 'S0 login admin kabupaten');
$asn = new QaHttp('sasn'); $asn->login('pegawai3@example.test');
$opd = new QaHttp('sopd'); $opd->login('pegawai2@example.test');
$OPEN = (int) $q("SELECT id FROM periods WHERE status='open' ORDER BY id LIMIT 1")['id'];
$other = (int) $q("SELECT e.id FROM employees e WHERE e.opd_id<>(SELECT opd_id FROM employees WHERE id=2) ORDER BY e.id LIMIT 1")['id'];

// Hak akses dan validasi.
[$c] = $status($asn, 5, 0); check($c === 403 && $active(5) === 1, "S1 ASN ubah status → 403 ($c)");
[$c] = $status($opd, $other, 0); check($c === 403 && $active($other) === 1, "S2 admin OPD ubah status pegawai OPD lain → 403 ($c)");
[$c] = $status($opd, 1, 0); check($c === 403 && $active(1) === 1, "S3 admin OPD ubah status administrator kabupaten → 403 ($c)");
[$c] = $status($adm, 1, 0); check($c === 409 && $active(1) === 1, "S4 ubah status akun sendiri → 409 ($c)");
[$c] = $adm->call('employee_status', ['employee_id' => 5, 'active' => 'tidak']); check($c === 422 && $active(5) === 1, "S5 nilai status tidak sah → 422 ($c)");
[$c] = $status($adm, 999999, 0); check($c === 404, "S6 pegawai tidak ada → 404 ($c)");

// Pegawai dinilai nonaktif: hilang dari daftar tugas penilai, tidak dapat disimpan/dikirim, dan tidak dihitung pada progres.
$exp = $q("SELECT SUM(a.subject_id=5) AS s,SUM(a.rater_id=5) AS r FROM assignments a JOIN periods p ON p.id=a.period_id WHERE p.status<>'published' AND 5 IN (a.subject_id,a.rater_id)");
$T = $id(5, 3, $OPEN); check($T > 0 && in_array($T, array_map('intval', array_column($asn->bootstrap($OPEN)['tasks'], 'id')), true), 'S7 prasyarat: pegawai 3 menilai pegawai 5 di periode aktif');
[$c, $r] = $status($adm, 5, 0);
check($c === 200 && $active(5) === 0 && $r['as_subject'] === (int) $exp['s'] && $r['as_rater'] === (int) $exp['r'], "S8 admin kabupaten menonaktifkan pegawai 5; jumlah penugasan terdampak dilaporkan ($c)");
$audit = $q("SELECT entity_id,metadata FROM audit_logs WHERE action='employee_status_changed' ORDER BY id DESC LIMIT 1"); $meta = json_decode((string) $audit['metadata'], true);
check((int) $audit['entity_id'] === 5 && $meta['from'] === 1 && $meta['to'] === 0, 'S9 audit mencatat perubahan status');
check(!in_array($T, array_map('intval', array_column($asn->bootstrap($OPEN)['tasks'], 'id')), true), 'S10 penugasan menilai pegawai nonaktif hilang dari daftar tugas penilai');
[$c] = $asn->call('save_draft', ['id' => $T, 'version' => (int) $q("SELECT version FROM assignments WHERE id=$T")['version'], 'answers' => $seven(4), 'feedback' => '']); check($c === 409, "S11 simpan draf untuk pegawai nonaktif → 409 ($c)");
$pdo->exec("UPDATE assignments SET status='draft' WHERE id=$T"); foreach (range(1, 7) as $i) $pdo->exec("INSERT IGNORE INTO answers(assignment_id,indicator_id,score) VALUES($T,$i,4)");
[$c] = $asn->call('submit', ['period_id' => $OPEN, 'ids' => [$T]]); check($c === 409 && $q("SELECT status FROM assignments WHERE id=$T")['status'] === 'draft', "S12 kirim penilaian untuk pegawai nonaktif → 409 ($c)");
check((new QaHttp('s5'))->login('pegawai5@example.test') === 401, 'S13 pegawai nonaktif tidak dapat masuk');
$eff = (int) $q("SELECT COUNT(*) AS c FROM assignments a JOIN employees s ON s.id=a.subject_id AND s.active=1 JOIN employees r ON r.id=a.rater_id AND r.active=1 WHERE a.period_id=$OPEN")['c'];
$excl = (int) $q("SELECT COUNT(*) AS c FROM assignments WHERE period_id=$OPEN AND 5 IN (subject_id,rater_id)")['c'];
[, $dash] = $adm->call('admin_dashboard', ['period_id' => $OPEN]);
$per = array_values(array_filter($adm->bootstrap($OPEN)['periods'], static fn($p) => (int) $p['id'] === $OPEN))[0];
[, $list] = $adm->call('assignment_list', ['period_id' => $OPEN, 'q' => 'Siti Rahmah']);
check($dash['summary']['total'] === $eff && $per['assignments_total'] === $eff && $excl > 0, "S14 Dashboard admin dan daftar periode hanya menghitung penugasan berlaku ($eff; $excl dikecualikan)");
check($list['stats']['excluded'] === $excl && count(array_filter($list['rows'], static fn($x) => (int) $x['subject_active'] === 0 || (int) $x['rater_active'] === 0)) > 0, 'S15 distribusi penilai menandai penugasan yang dikecualikan dan menghitungnya');

// Diaktifkan kembali: semua penugasan berlaku lagi tanpa data hilang.
[$c, $r] = $status($adm, 5, 1);
check($c === 200 && $active(5) === 1 && in_array($T, array_map('intval', array_column($asn->bootstrap($OPEN)['tasks'], 'id')), true) && (new QaHttp('s5b'))->login('pegawai5@example.test') === 200, "S16 diaktifkan kembali: tugas tampil lagi dan pegawai dapat masuk ($c)");
[, $dash2] = $adm->call('admin_dashboard', ['period_id' => $OPEN]); check($dash2['summary']['total'] === $eff + $excl, 'S17 progres kembali menghitung seluruh penugasan');

// Penilai nonaktif dikecualikan dari nilai dan syarat publikasi. Periode D: atasan 2 (nilai 5), rekan 4 (nilai 4), rekan 6 (dinonaktifkan sebelum mengirim).
[, $b] = $adm->call('period_create', ['year' => 2035, 'quarter' => 1]); $D = (int) ($b['id'] ?? 0);
$assign($adm, $D, [[2, 'atasan'], [4, 'rekan'], [6, 'rekan']]); $adm->call('period_status', ['period_id' => $D, 'status' => 'open']);
check($rate(2, $D, 5) === 200 && $rate(4, $D, 4) === 200, 'S18 atasan dan satu rekan mengirim penilaian');
$status($adm, 6, 0); $adm->call('period_status', ['period_id' => $D, 'status' => 'closed']);
[$c] = $adm->call('period_status', ['period_id' => $D, 'status' => 'published']); $res = $asn->bootstrap($D)['result'];
check($c === 200 && $res['visible'] === true && (float) $res['score'] === 95.0 && $res['received'] === 2 && $res['expected'] === 2, "S19 publikasi lolos tanpa tugas penilai nonaktif; nilai dari atasan dan rekan aktif saja = 95 ($c)");
$status($adm, 6, 1);

// Atasan dinonaktifkan setelah periode dibuka: pegawai itu dikecualikan saat publikasi, bukan menahan seluruh periode.
[, $b] = $adm->call('period_create', ['year' => 2035, 'quarter' => 2]); $D2 = (int) ($b['id'] ?? 0);
$assign($adm, $D2, [[2, 'atasan'], [4, 'rekan']]); $adm->call('period_status', ['period_id' => $D2, 'status' => 'open']); $rate(4, $D2, 4);
$status($adm, 2, 0); $adm->call('period_status', ['period_id' => $D2, 'status' => 'closed']);
[$c, $b] = $adm->call('period_status', ['period_id' => $D2, 'status' => 'published']);
check($c === 200 && ($b['excluded_subjects'] ?? null) === 1, "S20 publikasi mengecualikan pegawai yang kehilangan atasan aktif ($c)");
// Periode draf: komposisi tidak sah karena atasan nonaktif menahan pembukaan dengan nama pegawai.
[, $b] = $adm->call('period_create', ['year' => 2035, 'quarter' => 3]); $D3 = (int) ($b['id'] ?? 0);
$pdo->exec("INSERT INTO assignments(period_id,subject_id,rater_id,rater_role) VALUES($D3,3,2,'atasan'),($D3,3,4,'rekan')");
[$c, $b] = $adm->call('period_status', ['period_id' => $D3, 'status' => 'open']);
check($c === 422 && str_contains($b['error'] ?? '', 'Rina Marlina') && $q("SELECT status FROM periods WHERE id=$D3")['status'] === 'draft', "S21 membuka draf dengan atasan nonaktif ditolak dan menyebut nama pegawai ($c)");
// Buat penugasan dari struktur melewati pegawai yang atasannya nonaktif.
[$c, $b] = $adm->call('assignment_generate', ['period_id' => $D3, 'opd_id' => (int) $q('SELECT opd_id FROM employees WHERE id=2')['opd_id']]);
check($c === 200 && count(array_filter($b['warnings'] ?? [], static fn($w) => str_contains($w, 'atasan langsung nonaktif'))) > 0 && (int) $q("SELECT COUNT(*) AS c FROM assignments WHERE period_id=$D3 AND rater_id=2 AND id NOT IN (SELECT id FROM (SELECT MIN(id) AS id FROM assignments WHERE period_id=$D3 AND subject_id=3 AND rater_id=2) x)")['c'] === 0, "S22 buat penugasan melewati pegawai beratasan nonaktif dan tidak memakai penilai nonaktif ($c)");
$status($adm, 2, 1);

// Admin OPD mengubah status pegawai OPD-nya; pegawai di OPD nonaktif tidak dapat diaktifkan.
[$c] = $status($opd, 9, 0); check($c === 200 && $active(9) === 0, "S23 admin OPD menonaktifkan pegawai OPD-nya ($c)"); $status($opd, 9, 1);
[, $b] = $adm->call('opd_save', ['name' => 'OPD Uji Status', 'code' => 'UJISTAT', 'active' => 1]); $O = (int) ($b['id'] ?? 0);
[, $b] = $adm->call('employee_save', ['name' => 'Pegawai Uji Status', 'nip' => '199001012020011099', 'position' => 'Pelaksana', 'unit' => 'Umum', 'grade' => 'III/a', 'email' => 'ujistatus@example.test', 'opd_id' => $O, 'password' => 'KataSandiUji2026!']); $E = (int) ($b['id'] ?? 0);
$status($adm, $E, 0); $adm->call('opd_save', ['id' => $O, 'name' => 'OPD Uji Status', 'code' => 'UJISTAT', 'active' => 0]);
[$c] = $status($adm, $E, 1); check($c === 409 && $active($E) === 0, "S24 pegawai di OPD nonaktif tidak dapat diaktifkan → 409 ($c)");
qa_summary();
