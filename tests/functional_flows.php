<?php
// Tahap 5 QA — alur bisnis multi-langkah (black-box HTTP + asersi DB). Prasyarat sama dengan api_invariants.php; jalankan reseed dulu.
declare(strict_types=1);
require __DIR__ . '/bootstrap.php'; require __DIR__ . '/http_client.php';
$pdo = qa_pdo(); $q = static fn(string $sql) => $pdo->query($sql)->fetch();
$id = static fn(int $subject, int $rater, int $period = 1): int => (int) $pdo->query("SELECT id FROM assignments WHERE period_id=$period AND subject_id=$subject AND rater_id=$rater")->fetchColumn();
$seven = static fn(int $s) => array_fill_keys(range(1, 7), $s);

// ================= ALUR A — ASN mengisi & mengirim penilaian =================
$asn = new QaHttp('asn3'); check($asn->login('pegawai3@example.test') === 200, 'A0 login ASN pegawai3');
$T = $id(4, 3); $T2 = $id(5, 3); $T3 = $id(6, 3);
[$c, $b] = $asn->call('save_draft', ['id' => $T, 'version' => 1, 'answers' => [1 => 4, 2 => 5, 3 => 3], 'feedback' => 'sebagian']); check($c === 200 && $b['version'] === 2, "A1 simpan draf 3 jawaban → version 2 ($c)");
$asn->call('logout', []); $asn2 = new QaHttp('asn3b'); $asn2->login('pegawai3@example.test'); $t = array_values(array_filter($asn2->bootstrap()['tasks'], static fn($x) => $x['id'] === $T))[0];
check($t['status'] === 'draft' && $t['version'] === 2 && $t['answers'] === ['1' => 4, '2' => 5, '3' => 3] && $t['feedback'] === 'sebagian', 'A2 keluar-masuk: draf, versi, jawaban, catatan tetap tersimpan (VALIDASI #5)');
$asn = $asn2;
[$c] = $asn->call('save_draft', ['id' => $T, 'version' => 1, 'answers' => [1 => 1], 'feedback' => '']); check($c === 409 && $q("SELECT score FROM answers WHERE assignment_id=$T AND indicator_id=1")['score'] === 4, "A3 versi usang → 409, jawaban tidak berubah (R-11) ($c)");
[$c] = $asn->call('save_draft', ['id' => $T, 'version' => 2, 'answers' => array_fill_keys(range(1, 6), 4), 'feedback' => '']); check($c === 200, "A4 draf 6 jawaban tersimpan ($c)");
[$c] = $asn->call('submit', ['period_id' => 1, 'ids' => [$T]]); check($c === 422 && $q("SELECT status FROM assignments WHERE id=$T")['status'] === 'draft', "A5 kirim dengan 6 jawaban → 422, tetap draft (R-05) ($c)");
[$c] = $asn->call('save_draft', ['id' => $T, 'version' => 3, 'answers' => $seven(5), 'feedback' => str_repeat('x', 1001)]); check($c === 422, "A6 catatan 1001 karakter → 422 ($c)");
[$c] = $asn->call('save_draft', ['id' => $T, 'version' => 3, 'answers' => $seven(5), 'feedback' => str_repeat('é', 1000)]); check($c === 200, "A7 catatan 1000 karakter multibyte → 200 ($c)");
[$c] = $asn->call('save_draft', ['id' => $T2, 'version' => 1, 'answers' => $seven(4), 'feedback' => '']); check($c === 200, "A8 draf T2 lengkap ($c)");
[$c] = $asn->call('save_draft', ['id' => $T3, 'version' => 1, 'answers' => [1 => 2, 2 => 2], 'feedback' => '']); check($c === 200, "A9 draf T3 2 jawaban ($c)");
[$c] = $asn->call('submit', ['period_id' => 1, 'ids' => [$T, $T3]]); $st = $q("SELECT GROUP_CONCAT(status ORDER BY id) s FROM assignments WHERE id IN ($T,$T3)")['s'];
check($c === 422 && $st === 'draft,draft', "A10 batch [lengkap, tak lengkap] → seluruh batch batal (VALIDASI #11) ($c, $st)");
[$c] = $asn->call('submit', ['period_id' => 1, 'ids' => [$T2, $T2]]); check($c === 422, "A11 ids duplikat → 422 ($c)");
[$c] = $asn->call('submit', ['period_id' => 1, 'ids' => range(1, 501)]); check($c === 422, "A12 ids 501 → 422 ($c)");
[$c] = $asn->call('submit', ['period_id' => 2, 'ids' => [$T]]); check($c === 409, "A13 kirim ke periode published → 409 ($c)");
[$c] = $asn->call('submit', ['period_id' => 1, 'ids' => [$id(2, 3)]]); check($c === 409, "A14 kirim tugas pending (tanpa draf) → 409 ($c)");
[$c, $b] = $asn->call('submit', ['period_id' => 1, 'ids' => [$T, $T2]]); $r = $q("SELECT COUNT(*) n, MIN(submitted_at IS NOT NULL) sa, MIN(version) v FROM assignments WHERE id IN ($T,$T2) AND status='submitted'");
check($c === 200 && $b['count'] === 2 && (int) $r['n'] === 2 && (int) $r['sa'] === 1 && (int) $r['v'] >= 2, "A15 kirim 2 tugas lengkap → submitted, submitted_at terisi (VALIDASI #8) ($c)");
[$c] = $asn->call('save_draft', ['id' => $T, 'version' => 5, 'answers' => $seven(1), 'feedback' => '']); check($c === 409 && $q("SELECT score FROM answers WHERE assignment_id=$T AND indicator_id=1")['score'] === 5, "A16 draf pada tugas terkirim → 409, terkunci (R-10) ($c)");
[$c] = $asn->call('submit', ['period_id' => 1, 'ids' => [$T]]); check($c === 409 && (int) $q("SELECT COUNT(*) n FROM audit_logs WHERE action='assessment_submitted' AND entity_id=$T")['n'] === 1, "A17 kirim ulang → 409, tanpa duplikasi audit (VALIDASI #9) ($c)");
$pubTask = (int) $pdo->query('SELECT id FROM assignments WHERE period_id=2 AND rater_id=3 LIMIT 1')->fetchColumn();
[$c] = $asn->call('save_draft', ['id' => $pubTask, 'version' => 1, 'answers' => [], 'feedback' => '']); check($c === 409, "A18 draf pada periode published → 409 (R-12) ($c)");
[$c, $b] = $asn->call('save_draft', ['id' => $T3, 'version' => 2, 'answers' => ['1' => 4, '01' => 5, 2 => 3], 'feedback' => '']); check($c === 422, "A19 kunci '1' dan '01' (indikator sama) → 422 (OBS-09) ($c: " . ($b['error'] ?? '') . ')');
[$c] = $asn->call('save_draft', ['id' => $T3, 'version' => 2, 'answers' => [], 'feedback' => '']); check($c === 200 && (int) $q("SELECT COUNT(*) n FROM answers WHERE assignment_id=$T3")['n'] === 0, "A20 draf jawaban kosong mengosongkan jawaban lama ($c)");
$res = $asn->bootstrap(1)['result']; check($res['visible'] === false && $res['score'] === null, 'A21 hasil sendiri periode open tetap tersembunyi (R-16)');

// ================= ALUR B — Admin siklus periode + hitung manual =================
$adm = new QaHttp('adm'); check($adm->login('pegawai1@example.test') === 200, 'B0 login admin');
foreach ([[2026, 5], [2026, 0], [1999, 1], ['', 1], [2026, 'x'], ['2026-09-01', 3]] as [$y, $qq]) { [$c] = $adm->call('period_create', ['year' => $y, 'quarter' => $qq]); check($c === 422, "B1 tahun/triwulan '$y'/'$qq' → 422 ($c)"); }
$nowQ = (int) ceil((int) date('n') / 3); $nowY = (int) date('Y');
[$c] = $adm->call('period_create', ['year' => $nowY, 'quarter' => $nowQ]); check($c === 409, "B1b triwulan berjalan sudah dibuat seeder → 409 ($c)");
$pdo->exec("UPDATE periods SET name='Triwulan I 2020', start_date='2020-01-01', end_date='2020-03-31' WHERE id=1"); // kosongkan triwulan berjalan agar alur B memakai periode buatan sendiri
[$c, $b] = $adm->call('period_create', ['year' => $nowY, 'quarter' => $nowQ]); $P = (int) ($b['id'] ?? 0); $row = $q("SELECT name, start_date, end_date, status FROM periods WHERE id=$P");
check($c === 200 && $P > 0 && $row['status'] === 'draft' && $row['name'] === 'Triwulan ' . ['I', 'II', 'III', 'IV'][$nowQ - 1] . " $nowY" && $row['start_date'] === sprintf('%d-%02d-01', $nowY, ($nowQ - 1) * 3 + 1) && $row['end_date'] === date('Y-m-t', mktime(0, 0, 0, $nowQ * 3, 1, $nowY)), "B2 periode triwulan baru: nama & rentang diturunkan server, status draft ($c, " . json_encode($row) . ')');
$mk = static fn(int $s, int $r, string $role) => $adm->call('assignment_create', ['period_id' => $P, 'subject_id' => $s, 'rater_id' => $r, 'rater_role' => $role]);
[$c] = $mk(3, 3, 'rekan'); check($c === 422, "B3 menilai diri sendiri → 422 (VALIDASI #13) ($c)");
[$c] = $mk(3, 999, 'rekan'); check($c === 404, "B4 penilai tidak ada → 404 ($c)");
[$c] = $mk(3, 2, 'boss'); check($c === 422, "B5 peran asing → 422 ($c)");
[$c] = $mk(3, 2, 'atasan'); check($c === 200, "B6 atasan dibuat ($c)");
[$c] = $mk(3, 2, 'rekan'); check($c === 409, "B7 penilai ganda → 409 ($c)");
[$c] = $mk(3, 4, 'rekan'); check($c === 200, "B8 1 rekan dibuat ($c)");
[$c, $b] = $adm->call('period_status', ['period_id' => $P, 'status' => 'open']); check($c === 422 && $q("SELECT status FROM periods WHERE id=$P")['status'] === 'draft', "B9 buka dengan 1 rekan → 422, tetap draft (R-03) ($c)");
$mk(3, 5, 'rekan'); $mk(3, 6, 'rekan');
[$c] = $adm->call('period_status', ['period_id' => $P, 'status' => 'published']); check($c === 409, "B10 draft→published → 409 (R-13) ($c)");
[$c] = $adm->call('period_status', ['period_id' => $P, 'status' => 'open']); check($c === 200, "B11 draft→open ($c)");
[$c] = $mk(3, 7, 'rekan'); check($c === 409, "B12 tambah penilai saat open → 409 (R-21) ($c)");
$A4 = $id(3, 4, $P); [$c] = $adm->call('assignment_delete', ['id' => $A4]); check($c === 409, "B13 hapus penilai saat open → 409 (R-21) ($c)");
[$c] = $adm->call('period_status', ['period_id' => $P, 'status' => 'draft']); check($c === 409, "B14 open→draft → 409 ($c)");
$r4 = new QaHttp('r4'); $r4->login('pegawai4@example.test');
[$c] = $r4->call('save_draft', ['id' => $A4, 'version' => 1, 'answers' => $seven(4), 'feedback' => '']); check($c === 200, "B15 rekan simpan draf saat open ($c)");
[$c] = $adm->call('period_status', ['period_id' => $P, 'status' => 'closed']); check($c === 200, "B16 open→closed ($c)");
[$c] = $r4->call('save_draft', ['id' => $A4, 'version' => 2, 'answers' => $seven(1), 'feedback' => '']); check($c === 409, "B17 simpan saat closed → 409 (VALIDASI #12) ($c)");
[$c] = $r4->call('submit', ['period_id' => $P, 'ids' => [$A4]]); check($c === 409, "B18 kirim saat closed → 409 ($c)");
[$c] = $adm->call('period_status', ['period_id' => $P, 'status' => 'published']); check($c === 409, "B19 publikasi dengan tugas belum terkirim → 409 (VALIDASI #14) ($c)");
[$c] = $adm->call('period_status', ['period_id' => $P, 'status' => 'open']); check($c === 200, "B20 closed→open ($c)");
foreach ([2 => 5, 4 => 4, 5 => 4, 6 => 4] as $rater => $score) { $cl = new QaHttp("b21r$rater"); $cl->login("pegawai$rater@example.test"); $aid = $id(3, $rater, $P); $v = (int) $q("SELECT version FROM assignments WHERE id=$aid")['version']; $cl->call('save_draft', ['id' => $aid, 'version' => $v, 'answers' => $seven($score), 'feedback' => '']); [$c] = $cl->call('submit', ['period_id' => $P, 'ids' => [$aid]]); check($c === 200, "B21 penilai $rater kirim ($c)"); }
[$c] = $adm->call('period_status', ['period_id' => $P, 'status' => 'closed']); [$c2] = $adm->call('period_status', ['period_id' => $P, 'status' => 'published']);
check($c === 200 && $c2 === 200 && $q("SELECT published_at FROM periods WHERE id=$P")['published_at'] !== null, "B22 closed→published, published_at terisi ($c/$c2)");
$res = $asn->bootstrap($P)['result']; $dims = array_map('floatval', array_column($res['dimensions'], 'score'));
check($res['visible'] === true && (float) $res['score'] === 95.0 && $res['weights'] === ['atasan' => 75, 'rekan' => 25] && count($dims) === 7 && min($dims) === 95.0 && max($dims) === 95.0, 'B23 hasil = (0.75·5+0.25·4)·20 = 95 sesuai hitungan manual (VALIDASI #15) → ' . json_encode($res['score']));
[$c] = $adm->call('period_status', ['period_id' => $P, 'status' => 'open']); check($c === 409, "B24 published final → 409 (R-13) ($c)");
[$c, $b] = $adm->call('period_create', ['year' => 2027, 'quarter' => 1]); $PF = (int) $b['id'];
foreach ([[2, 'atasan'], [4, 'rekan'], [5, 'rekan'], [6, 'rekan']] as [$r, $role]) $adm->call('assignment_create', ['period_id' => $PF, 'subject_id' => 3, 'rater_id' => $r, 'rater_role' => $role]);
$adm->call('period_status', ['period_id' => $PF, 'status' => 'open']);
[$c] = $r4->call('save_draft', ['id' => $id(3, 4, $PF), 'version' => 1, 'answers' => $seven(4), 'feedback' => '']); check($c === 200, "B25 periode open di luar rentang tanggal tetap menerima draf; status yang menentukan ($c)");
$adminBoot = $adm->bootstrap($P); check(count($adminBoot['employees']) === 60 && !array_key_exists('assignments', $adminBoot) && !str_contains(json_encode($adminBoot), '$2y$'), 'B26 bootstrap admin: 60 pegawai (5 OPD), tanpa dump penugasan, tanpa hash');
[$c, $al] = $adm->call('assignment_list', ['period_id' => $P]); check($c === 200 && $al['total'] === 4 && $al['stats']['total'] === 4 && count($al['rows']) === 4 && isset($al['rows'][0]['subject_name'], $al['rows'][0]['rater_name']), "B26b assignment_list admin: 4 penugasan berhalaman + nama ($c)");
[$c] = $asn->call('assignment_list', ['period_id' => $P]); check($c === 403, "B26c assignment_list ditolak untuk ASN ($c)");

// ================= ALUR C — Akun, kata sandi, nonaktif, pembatasan login =================
$emp = ['name' => 'Uji Coba', 'nip' => '199001012020011001', 'position' => 'Analis', 'opd_id' => 1, 'unit' => 'QA', 'grade' => 'III/a', 'email' => 'UJI@example.test', 'password' => 'KataSandiUji123'];
foreach ([['nip' => '12345'], ['email' => 'bukan-email'], ['password' => 'pendek'], ['name' => ''], ['name' => str_repeat('a', 161)], ['nip' => '1990010120200110011'], ['opd_id' => 999], ['opd_id' => ''], ['role' => 'root']] as $over) { [$c] = $adm->call('employee_save', array_merge($emp, $over)); check($c === 422, 'C1 employee_save ' . json_encode($over) . " → 422 ($c)"); }
[$c, $b] = $adm->call('employee_save', $emp); $E = (int) ($b['id'] ?? 0); check($c === 200 && $E > 0 && $q("SELECT email FROM employees WHERE id=$E")['email'] === 'uji@example.test' && $q("SELECT role FROM users WHERE employee_id=$E")['role'] === 'asn', "C2 pegawai baru dibuat, email lowercase, role asn ($c)");
[$c] = $adm->call('employee_save', array_merge($emp, ['nip' => '199001012020011002'])); check($c === 409, "C3 email duplikat → 409 ($c)");
$u = new QaHttp('uji'); check($u->login('uji@example.test', 'KataSandiUji123') === 200, 'C4 akun baru bisa login');
[$c] = $adm->call('employee_save', array_merge($emp, ['nip' => '199001012020011003', 'email' => 'nip@example.test', 'password' => ''])); $n = new QaHttp('nip');
check($c === 200 && $n->login('nip@example.test', 'salah-sandi') === 401 && $n->login('nip@example.test', '199001012020011003') === 200, "C4b pegawai baru tanpa sandi: kata sandi awal = NIP ($c)");
[$c] = $adm->call('employee_save', array_merge($emp, ['id' => $E, 'password' => '', 'position' => 'Analis Madya'])); check($c === 200 && $q("SELECT position FROM employees WHERE id=$E")['position'] === 'Analis Madya', "C5 edit tanpa sandi: profil berubah ($c)");
[$c] = $u->call('bootstrap', []); check($c === 200, "C6 sesi tetap hidup setelah edit tanpa sandi ($c)");
[$c] = $adm->call('employee_save', array_merge($emp, ['id' => $E, 'password' => 'SandiBaruAdmin123'])); [$c2] = $u->call('bootstrap', []); check($c === 200 && $c2 === 401, "C7 admin reset sandi → sesi lama pegawai 401 ($c/$c2)");
check($u->login('uji@example.test', 'KataSandiUji123') === 401 && $u->login('uji@example.test', 'SandiBaruAdmin123') === 200, 'C8 sandi lama ditolak, sandi baru diterima');
[$c] = $adm->call('employee_save', array_merge($emp, ['id' => 999999])); check($c === 404, "C9 edit pegawai fiktif → 404 ($c)");
$s1 = new QaHttp('p7a'); $s2 = new QaHttp('p7b'); $s1->login('pegawai7@example.test'); $s2->login('pegawai7@example.test');
[$c] = $s1->call('password_change', ['current_password' => 'salah', 'password' => 'SandiPanjangBaru123']); check($c === 403, "C10 sandi saat ini salah → 403 ($c)");
[$c] = $s1->call('password_change', ['current_password' => 'SikapDemo2026!', 'password' => 'pendek']); check($c === 422, "C11 sandi baru < 12 → 422 ($c)");
[$c] = $s1->call('password_change', ['current_password' => 'SikapDemo2026!', 'password' => 'SandiPanjangBaru123']); [$c1] = $s1->call('bootstrap', []); [$c2] = $s2->call('bootstrap', []);
check($c === 200 && $c1 === 200 && $c2 === 401, "C12 ganti sandi: sesi sendiri hidup, sesi lain 401 (VALIDASI #17) ($c/$c1/$c2)");
$s8 = new QaHttp('p8'); $s8->login('pegawai8@example.test'); $pdo->exec('UPDATE employees SET active=0 WHERE id=8'); [$c] = $s8->call('bootstrap', []); $l = (new QaHttp('p8b'))->login('pegawai8@example.test'); $pdo->exec('UPDATE employees SET active=1 WHERE id=8');
check($c === 401 && $l === 401, "C13 pegawai nonaktif: sesi putus + login ditolak (R-23) ($c/$l)");
$pdo->exec('DELETE FROM login_attempts'); $g = new QaHttp('rl'); $codes = []; for ($i = 0; $i < 11; $i++) $codes[] = $g->login('pegawai9@example.test', 'salah-' . $i);
check(array_slice($codes, 0, 10) === array_fill(0, 10, 401) && $codes[10] === 429, 'C14 percobaan ke-11 → 429 (R-18) ' . implode(',', $codes));
check((new QaHttp('rl2'))->login('pegawai9@example.test') === 429, 'C15 sandi benar saat terkunci → tetap 429');
check((new QaHttp('rl3'))->login('pegawai10@example.test') === 200, 'C16 akun lain dari IP yang sama tidak ikut terkunci oleh 10 kegagalan (OBS-01: ambang IP 100)');
$pdo->exec("INSERT INTO login_attempts(account_hash,ip_hash) SELECT SHA2(CONCAT('x',n),256),SHA2('127.0.0.1',256) FROM (SELECT @r:=@r+1 n FROM information_schema.columns,(SELECT @r:=0) i LIMIT 100) t");
check((new QaHttp('rl5'))->login('pegawai11@example.test') === 429, 'C16b 100 kegagalan dari satu IP → 429 walau akun berbeda');
$old = (int) $pdo->query("SELECT COUNT(*) FROM login_attempts")->fetchColumn(); $pdo->exec("UPDATE login_attempts SET attempted_at=DATE_SUB(NOW(),INTERVAL 2 DAY)");
(new QaHttp('rl6'))->login('pegawai11@example.test', 'salah'); check((int) $pdo->query("SELECT COUNT(*) FROM login_attempts")->fetchColumn() === 1, "C16c baris login_attempts > 1 hari dibersihkan saat login (OBS-04): $old → 1");
$pdo->exec('DELETE FROM login_attempts'); check((new QaHttp('rl4'))->login('pegawai10@example.test') === 200, 'C17 setelah jendela dibersihkan, login normal');
// OBS-05: zona waktu sesi DB = zona aplikasi
$cfg = require dirname(__DIR__) . '/config/app.php'; $phpNow = (new DateTimeImmutable('now', new DateTimeZone($cfg['timezone'])))->format('Y-m-d H:i');
check(substr((string) $pdo->query('SELECT NOW()')->fetchColumn(), 0, 16) === $phpNow, "C18 NOW() MySQL sama dengan waktu PHP zona {$cfg['timezone']} (OBS-05)");
// OBS-01: X-Forwarded-For diabaikan bila REMOTE_ADDR bukan proxy tepercaya (server uji tanpa TRUSTED_PROXIES)
$pdo->exec('DELETE FROM login_attempts'); $x = new QaHttp('xff'); $x->extraHeaders = ['X-Forwarded-For: 203.0.113.9']; $x->login('pegawai12@example.test', 'salah');
check($pdo->query("SELECT ip_hash FROM login_attempts LIMIT 1")->fetchColumn() === hash('sha256', '127.0.0.1'), 'C19 X-Forwarded-For dari klien tak tepercaya diabaikan; IP tercatat = REMOTE_ADDR (OBS-01)');
$pdo->exec('DELETE FROM login_attempts');
qa_summary();
