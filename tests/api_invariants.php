<?php
// Tahap 4 QA — invarian HTTP black-box: tamu, RBAC, CSRF, origin, fuzz field, IDOR, kebocoran.
// Prasyarat: bash .qa/bin/reseed.sh; server: DB_DATABASE=sikap360_test APP_URL=http://127.0.0.1:8765 php -S 127.0.0.1:8765 -t public
// Jalankan: DB_DATABASE=sikap360_test php tests/api_invariants.php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php'; require __DIR__ . '/http_client.php';
$pdo = qa_pdo();
$ALL = ['bootstrap', 'logout', 'password_change', 'save_draft', 'submit', 'employee_save', 'period_create', 'period_status', 'assignment_create', 'assignment_delete', 'supervisor_set', 'assignment_generate', 'opd_save', 'settings_save', 'weights_save', 'tidak_ada'];
$ADMIN = ['employee_save', 'period_create', 'period_status', 'assignment_create', 'assignment_delete', 'supervisor_set', 'assignment_generate', 'opd_save', 'settings_save', 'weights_save'];
$SUPER = ['period_create', 'period_status', 'opd_save', 'settings_save', 'weights_save'];
$guest = new QaHttp('guest'); $asn = new QaHttp('asn'); $admin = new QaHttp('admin');

// 1. Tamu (R-19 / TAMU-TEMBUS)
$bad = [];
foreach ($ALL as $a) { [$c, , $raw] = $guest->call($a, ['id' => 1], ['nocsrf' => true]); if ($c !== 401 || leak($raw)) $bad[] = "$a=$c"; }
check(!$bad, 'Tamu: semua aksi selain login → 401 ' . implode(',', $bad));

// 2. Lapisan transport (R-19)
[$c] = $guest->call('login', [], ['method' => 'GET', 'nocsrf' => true]); check($c === 405, "GET → 405 ($c)");
[$c] = $guest->call('login', 'a=b', ['ctype' => 'application/x-www-form-urlencoded', 'nocsrf' => true]); check($c === 415, "form-urlencoded → 415 ($c)");
[$c] = $guest->call('login', '{"email":', ['nocsrf' => true]); check($c === 400, "JSON rusak → 400 ($c)");
[$c] = $guest->call('login', '"string"', ['nocsrf' => true]); check($c === 422, "JSON skalar → 422 ($c)");
[$c] = $guest->call('login', str_repeat('[', 40) . str_repeat(']', 40), ['nocsrf' => true]); check($c === 400, "JSON kedalaman 40 → 400 ($c)");
[$c] = $guest->call('login', ['email' => str_repeat('a', 70000)], ['nocsrf' => true]); check($c === 413, "body > 64KB → 413 ($c)");
foreach (['http://evil.test', 'http://127.0.0.1:9999', 'https://127.0.0.1:8765', 'null', 'http://127.0.0.1:8765.evil.test'] as $o) { [$c] = $guest->call('login', ['email' => 'x', 'password' => 'y'], ['origin' => $o, 'nocsrf' => true]); check($c === 403, "Origin $o → 403 ($c)"); }

// 3. Login & RBAC (R-07)
check($asn->login('pegawai3@example.test') === 200, 'login ASN');
check($admin->login('pegawai1@example.test') === 200, 'login admin');
$bad = []; foreach ($ADMIN as $a) { [$c] = $asn->call($a, ['id' => 1]); if ($c !== 403) $bad[] = "$a=$c"; }
check(!$bad, 'ASN: semua aksi admin → 403 ' . implode(',', $bad));
[$c] = $asn->call('tidak_ada', []); check($c === 403, "ASN aksi asing → 403 (bukan 404) ($c)");
$opdAdmin = new QaHttp('opdadmin'); check($opdAdmin->login('pegawai2@example.test') === 200, 'login admin OPD (pegawai2)');
$bad = []; foreach ($SUPER as $a) { [$c] = $opdAdmin->call($a, ['id' => 1]); if ($c !== 403) $bad[] = "$a=$c"; }
check(!$bad, 'Admin OPD: aksi kabupaten (periode, OPD, pengaturan) → 403 ' . implode(',', $bad));
[$c] = $admin->call('tidak_ada', []); check($c === 404, "admin aksi asing → 404 ($c)");
// email login case-insensitive & trim
$tmp = new QaHttp('tmp'); check($tmp->login('  PEGAWAI3@Example.TEST ') === 200, 'login email huruf besar + spasi diterima (strtolower+trim)');
$nip = new QaHttp('nip3'); check($nip->login('DEMO-0003') === 200 && $nip->bootstrap()['user']['id'] === 3, 'login dengan NIP diterima dan masuk sebagai pegawai yang benar');
$nipSp = new QaHttp('nip3sp'); check($nipSp->login(' DEMO- 0003 ') === 200, 'login NIP dengan spasi diterima (spasi diabaikan)');
$legacy = new QaHttp('legacy'); check($legacy->login('pegawai3@example.test', 'SikapDemo2026!', 'email') === 200, 'kunci lama "email" tetap diterima');
check((new QaHttp('nipbad'))->login('DEMO-0003', 'salah-sandi-123') === 401, 'login NIP dengan kata sandi salah → 401');
check((new QaHttp('nipnone'))->login('199999999999999999') === 401, 'NIP tidak terdaftar → 401');

// 4. CSRF (R-09)
$bad = []; foreach (array_diff($ALL, ['bootstrap', 'tidak_ada']) as $a) { [$c1] = $asn->call($a, ['id' => 1], ['nocsrf' => true]); [$c2] = $asn->call($a, ['id' => 1], ['csrf' => 'salah']); if ($c1 !== 403 || $c2 !== 403) $bad[] = "$a=$c1/$c2"; }
check(!$bad, 'Mutasi tanpa/dengan CSRF salah → 403 ' . implode(',', $bad));
[$c] = $asn->call('bootstrap', [], ['nocsrf' => true]); check($c === 200, 'bootstrap tanpa CSRF boleh (baca)');

// 5. Kebocoran data pada bootstrap ASN (R-15)
$b = $asn->bootstrap(); $raw = json_encode($b);
check(!str_contains($raw, 'password') && !str_contains($raw, '$2y$'), 'bootstrap ASN tanpa password/hash');
check($b['employees'] === [] && !array_key_exists('assignments', $b), 'bootstrap ASN employees/assignments kosong');
$asnId = (int) $b['user']['id']; $ids = array_map(static fn($t) => (int) $t['id'], $b['tasks']);
$own = (int) $pdo->query('SELECT COUNT(*) FROM assignments WHERE id IN (' . implode(',', $ids) . ') AND rater_id=' . $asnId)->fetchColumn();
check($own === count($ids) && count($ids) > 0, 'bootstrap ASN tasks semuanya rater_id = diri sendiri (' . count($ids) . ')');
check($b['result']['score'] === null && $b['result']['dimensions'] === [] && $b['result']['visible'] === false, 'result periode open: score/dimensi disembunyikan (R-16)');
$pub = $asn->bootstrap(2); check($pub['result']['published'] === true && is_float($pub['result']['score']) && count($pub['result']['dimensions']) === 7, 'result periode published: skor + 7 dimensi tampil (' . json_encode($pub['result']['score']) . ')');
[$c, $b2] = $asn->call('bootstrap', ['period_id' => 999]); check($c === 200 && (int) $b2['period']['id'] === 1, 'bootstrap period_id asing → jatuh ke periode terbaru');
// BUG-002: periode draft masa depan tidak boleh menjadi default selama ada periode open
$pdo->exec("INSERT INTO periods(name,start_date,end_date) VALUES('QA Masa Depan (draft)','2030-01-01','2030-01-31')"); $future = (int) $pdo->lastInsertId();
[$c, $b3] = $asn->call('bootstrap', []); check($c === 200 && $b3['period']['status'] === 'open' && count($b3['tasks']) > 0, 'bootstrap default = periode open, bukan draft masa depan (BUG-002) → ' . $b3['period']['name'] . ' tasks=' . count($b3['tasks']));
[$c, $b4] = $asn->call('bootstrap', ['period_id' => $future]); check($c === 200 && (int) $b4['period']['id'] === $future, 'bootstrap period_id eksplisit ke periode draft tetap bisa');
$pdo->exec("DELETE FROM periods WHERE id=$future");

// 6. IDOR (R-08): ASN menyentuh tugas milik orang lain
$other = $pdo->query("SELECT a.id,a.period_id,a.version,a.status FROM assignments a WHERE a.period_id=1 AND a.rater_id<>$asnId AND a.status='draft' LIMIT 1")->fetch();
$before = $pdo->query('SELECT GROUP_CONCAT(CONCAT(indicator_id,":",score) ORDER BY indicator_id) FROM answers WHERE assignment_id=' . $other['id'])->fetchColumn();
[$c] = $asn->call('save_draft', ['id' => (int) $other['id'], 'version' => (int) $other['version'], 'answers' => [1 => 1, 2 => 1], 'feedback' => 'IDOR']);
$after = $pdo->query('SELECT GROUP_CONCAT(CONCAT(indicator_id,":",score) ORDER BY indicator_id) FROM answers WHERE assignment_id=' . $other['id'])->fetchColumn();
check($c === 404 && $before === $after, "IDOR save_draft tugas orang lain → 404, jawaban pemilik utuh ($c)");
[$c] = $asn->call('submit', ['period_id' => 1, 'ids' => [(int) $other['id']]]); $st = $pdo->query('SELECT status FROM assignments WHERE id=' . $other['id'])->fetchColumn();
check($c === 403 && $st === 'draft', "IDOR submit tugas orang lain → 403, status tetap draft ($c)");
$otherSub = (int) $pdo->query("SELECT id FROM assignments WHERE period_id=1 AND rater_id<>$asnId AND status='submitted' LIMIT 1")->fetchColumn();
[$c, $bb] = $asn->call('save_draft', ['id' => $otherSub, 'version' => 1, 'answers' => [], 'feedback' => '']); check($c === 404, "IDOR save_draft tugas submitted orang lain → 404 (tanpa membocorkan status) ($c)");

// 7. Fuzz semua field tiap aksi: tidak boleh 500 / bocor (R-20)
$vals = ['', "'", "' OR 1=1 -- ", '<script>x</script>', '0', '-1', 'A', '99999999999999999999', str_repeat('A', 5000), ['x'], ['a' => ['b' => ['c' => 1]]], null, true, 1.5, -1, 0, PHP_INT_MAX, 1e30];
$fields = [
 'login' => ['email' => 'pegawai3@example.test', 'password' => 'x'],
 'password_change' => ['current_password' => 'x', 'password' => 'y'],
 'save_draft' => ['id' => 1, 'version' => 1, 'answers' => [1 => 4], 'feedback' => 'f'],
 'submit' => ['period_id' => 1, 'ids' => [1]],
 'employee_save' => ['id' => 0, 'name' => 'n', 'nip' => '123456789012345678', 'position' => 'p', 'opd_id' => 1, 'unit' => 'u', 'grade' => 'g', 'email' => 'a@b.test', 'password' => 'SangatRahasia123', 'supervisor_id' => 2, 'role' => 'asn'],
 'supervisor_set' => ['employee_id' => 3, 'supervisor_id' => 2],
 'assignment_generate' => ['period_id' => 1, 'opd_id' => 1],
 'opd_save' => ['id' => 0, 'name' => 'OPD Fuzz', 'code' => 'FUZZ', 'active' => 1],
 'settings_save' => ['kabupaten_name' => 'Hulu Sungai Selatan'],
 'weights_save' => ['period_id' => 1, 'weights' => ['atasan,bawahan,rekan' => ['atasan' => 60, 'rekan' => 25, 'bawahan' => 15], 'atasan,rekan' => ['atasan' => 75, 'rekan' => 25], 'atasan,bawahan' => ['atasan' => 85, 'bawahan' => 15]]],
 'period_create' => ['year' => 2027, 'quarter' => 1],
 'period_status' => ['period_id' => 1, 'status' => 'closed'],
 'assignment_create' => ['period_id' => 3, 'subject_id' => 1, 'rater_id' => 2, 'rater_role' => 'rekan'],
 'assignment_delete' => ['id' => 0],
 'bootstrap' => ['period_id' => 1],
];
$bad = []; $n = 0;
foreach ($fields as $action => $valid) {
 $client = in_array($action, $ADMIN, true) ? $admin : ($action === 'login' ? $guest : $asn);
 foreach (array_keys($valid) as $k) foreach ($vals as $v) {
  $n++; [$c, , $raw] = $client->call($action, array_merge($valid, [$k => $v]), ['nocsrf' => $action === 'login']);
  if ($c >= 500 || $c === 0 || leak($raw)) { $bad[] = "$action.$k=" . substr(json_encode($v), 0, 20) . "→$c " . substr($raw, 0, 80); break; }
 }
 // seluruh body bertipe salah
 foreach (['[]', '[1,2]', '{"answers":"x"}', '{"ids":"x"}', '{"ids":{"a":1}}', '{"answers":[[1]]}', '{"status":["open"]}'] as $body) { $n++; [$c, , $raw] = $client->call($action, $body, ['nocsrf' => $action === 'login']); if ($c >= 500 || leak($raw)) $bad[] = "$action body=$body→$c"; }
 // action sebagai array / aneh
}
[$c, , $raw] = $asn->raw('POST', $asn->base . '/api.php?action[]=x', '{}', ['Content-Type: application/json', 'Origin: ' . $asn->base]); if ($c >= 500 || leak($raw)) $bad[] = "action[]→$c";
check(!$bad, "Fuzz $n permintaan: 0 status 500 / bocor " . implode(' | ', $bad));
// fuzz tidak boleh mengubah data ASN (hanya save_draft dengan nilai valid bisa) — cek angka jawaban tugas orang lain tetap
$after2 = $pdo->query('SELECT GROUP_CONCAT(CONCAT(indicator_id,":",score) ORDER BY indicator_id) FROM answers WHERE assignment_id=' . $other['id'])->fetchColumn();
check($before === $after2, 'Fuzz tidak menyentuh jawaban tugas orang lain');
check((int) $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn() === 60 || true, '[INFO] jumlah pegawai setelah fuzz: ' . $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn());

// 8. Header keamanan
[$c, , $raw] = $guest->raw('GET', $guest->base . '/', null, []); check($c === 200 && str_contains($raw, '<'), 'GET / → index.html 200');
$hdr = shell_exec('curl -sI ' . escapeshellarg($guest->base . '/') . ' 2>/dev/null') ?: '';
check(stripos($hdr, 'Content-Security-Policy') !== false && stripos($hdr, "script-src 'self'") !== false, 'CSP script-src self pada /');
$hdr2 = shell_exec('curl -si -X POST -H "Content-Type: application/json" -H "Origin: ' . $guest->base . '" -d "{}" ' . escapeshellarg($guest->base . '/api.php?action=login') . ' 2>/dev/null') ?: '';
check(preg_match('/Set-Cookie: SIKAPSESSID=[^;]+;.*HttpOnly/i', $hdr2) === 1 && stripos($hdr2, 'SameSite=Strict') !== false, 'cookie sesi HttpOnly + SameSite=Strict');
check(stripos($hdr2, 'Cache-Control: no-store') !== false, 'API Cache-Control no-store');
foreach (['/../.env', '/.env', '/../app/Api.php', '/api.php/../.env', '/index.php/.env'] as $p) { [$c, , $raw] = $guest->raw('GET', $guest->base . $p, null, []); check($c !== 200 || !str_contains($raw, 'DB_PASSWORD'), "path $p tidak membocorkan .env ($c)"); }
qa_summary();
