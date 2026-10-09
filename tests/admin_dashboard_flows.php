<?php
// Dashboard admin (admin_dashboard): hanya administrator kabupaten; angka progres kabupaten dan per OPD sama dengan hitungan ulang dari baris penugasan mentah,
// OPD tanpa penugasan tetap tampil, dan respons hanya berisi jumlah (tanpa nilai maupun nama pegawai). Black-box HTTP + asersi DB.
// Prasyarat sama dengan weights_flows.php; jalankan reseed dulu. Jalankan: DB_DATABASE=sikap360_test php tests/admin_dashboard_flows.php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php'; require __DIR__ . '/http_client.php';
$pdo = qa_pdo(); $q = static fn(string $sql) => $pdo->query($sql)->fetch();
// Hitungan pembanding: dari baris penugasan mentah, dikelompokkan di PHP (bukan SQL yang sama dengan Api).
$expected = static function (int $period) use ($pdo): array {
    $opds = [];
    foreach ($pdo->query('SELECT o.id,(SELECT COUNT(*) FROM employees e WHERE e.opd_id=o.id AND e.active=1) AS employees FROM opd o')->fetchAll() as $o) $opds[(int) $o['id']] = ['employees' => (int) $o['employees'], 'total' => 0, 'submitted' => 0, 'draft' => 0, 'pending' => 0, 'subjects' => 0, 'subjects_done' => 0, 'raters' => 0, 'raters_done' => 0];
    $subjects = []; $raters = [];
    foreach ($pdo->query("SELECT a.subject_id,a.rater_id,a.status,s.opd_id AS s_opd,r.opd_id AS r_opd FROM assignments a JOIN employees s ON s.id=a.subject_id JOIN employees r ON r.id=a.rater_id WHERE a.period_id=$period")->fetchAll() as $a) {
        $o = &$opds[(int) $a['s_opd']]; $o['total']++; $o[$a['status'] === 'pending' ? 'pending' : $a['status']]++; unset($o);
        $subjects[(int) $a['subject_id']]['opd'] = (int) $a['s_opd']; $subjects[(int) $a['subject_id']]['open'] = ($subjects[(int) $a['subject_id']]['open'] ?? 0) + ($a['status'] !== 'submitted');
        $raters[(int) $a['rater_id']]['opd'] = (int) $a['r_opd']; $raters[(int) $a['rater_id']]['open'] = ($raters[(int) $a['rater_id']]['open'] ?? 0) + ($a['status'] !== 'submitted');
    }
    foreach ([['subjects', $subjects], ['raters', $raters]] as [$key, $list]) foreach ($list as $x) { $opds[$x['opd']][$key]++; if (!$x['open']) $opds[$x['opd']][$key . '_done']++; }
    return $opds;
};
$compare = static function (array $res, array $exp): bool {
    if (count($res['opds']) !== count($exp)) return false;
    foreach ($res['opds'] as $o) foreach ($exp[$o['id']] as $k => $v) if ($o[$k] !== $v) { echo "  beda OPD {$o['code']} $k: {$o[$k]} ≠ $v\n"; return false; }
    foreach (['total', 'submitted', 'draft', 'pending', 'subjects', 'subjects_done', 'raters', 'raters_done', 'employees'] as $k) if ($res['summary'][$k] !== array_sum(array_column($exp, $k))) { echo "  beda ringkasan $k\n"; return false; }
    return true;
};

$adm = new QaHttp('dadm'); check($adm->login('pegawai1@example.test') === 200, 'D0 login admin kabupaten');
$asn = new QaHttp('dasn'); $asn->login('pegawai3@example.test');
$opd = new QaHttp('dopd'); $opd->login('pegawai2@example.test');
$OPEN = (int) $q("SELECT id FROM periods WHERE status='open' ORDER BY id LIMIT 1")['id'];
[$c] = $asn->call('admin_dashboard', ['period_id' => $OPEN]); check($c === 403, "D1 ASN → 403 ($c)");
[$c] = $opd->call('admin_dashboard', ['period_id' => $OPEN]); check($c === 403, "D2 admin OPD → 403 ($c)");
[$c] = $adm->call('admin_dashboard', ['period_id' => 999999]); check($c === 404, "D3 periode tidak ada → 404 ($c)");

[$c, $r, $raw] = $adm->call('admin_dashboard', ['period_id' => $OPEN]);
check($c === 200 && $r['period']['id'] === $OPEN && $compare($r, $expected($OPEN)), "D4 angka per OPD dan ringkasan sama dengan hitungan ulang dari baris penugasan ($c)");
check($r['summary']['total'] === $r['summary']['submitted'] + $r['summary']['draft'] + $r['summary']['pending'] && array_sum(array_column($r['roles'], 'total')) === $r['summary']['total'] && array_sum(array_column($r['roles'], 'submitted')) === $r['summary']['submitted'] && array_column($r['roles'], 'role') === ['atasan', 'rekan', 'bawahan'], 'D5 terkirim + draf + belum = total, dan rincian per peran menjumlah ke total');
$names = $pdo->query('SELECT name FROM employees')->fetchAll(PDO::FETCH_COLUMN);
check(array_keys($r['opds'][0]) === ['id', 'name', 'code', 'active', 'employees', 'total', 'submitted', 'draft', 'pending', 'subjects', 'subjects_done', 'raters', 'raters_done'] && !preg_match('/"(score|answers|feedback|dimensions)"/', $raw) && !array_filter($names, static fn($n) => str_contains($raw, json_encode($n, JSON_UNESCAPED_UNICODE))), 'D6 respons hanya jumlah: tanpa nilai, jawaban, catatan, maupun nama pegawai');
check(array_column($r['opds'], 'name') === $pdo->query('SELECT name FROM opd ORDER BY name')->fetchAll(PDO::FETCH_COLUMN), 'D7 seluruh OPD tampil, urut nama');

// OPD baru tanpa penugasan tetap tampil dengan angka nol dan tidak dihitung sebagai OPD berpenugasan.
[$c, $b] = $adm->call('opd_save', ['name' => 'Kecamatan Uji Dashboard', 'code' => 'KECDASH', 'active' => 1]); $new = (int) ($b['id'] ?? 0);
[, $r2] = $adm->call('admin_dashboard', ['period_id' => $OPEN]); $row = array_values(array_filter($r2['opds'], static fn($o) => $o['id'] === $new))[0] ?? null;
check($c === 200 && $row !== null && $row['total'] === 0 && $row['subjects'] === 0 && $r2['summary']['opds'] === $r['summary']['opds'] + 1 && $r2['summary']['opds_assigned'] === $r['summary']['opds_assigned'], "D8 OPD tanpa penugasan tampil dengan angka nol ($c)");

// Pengiriman ASN menaikkan terkirim, dan pegawai yang seluruh penugasannya terkirim dihitung selesai dinilai.
$T = (int) $q("SELECT id FROM assignments WHERE period_id=$OPEN AND rater_id=3 AND status IN ('pending','draft') ORDER BY id LIMIT 1")['id'];
$v = (int) $q("SELECT version FROM assignments WHERE id=$T")['version'];
$asn->call('save_draft', ['id' => $T, 'version' => $v, 'answers' => array_fill_keys(range(1, 7), 4), 'feedback' => '']); [$c] = $asn->call('submit', ['period_id' => $OPEN, 'ids' => [$T]]);
[, $r3] = $adm->call('admin_dashboard', ['period_id' => $OPEN]);
check($c === 200 && $r3['summary']['submitted'] === $r2['summary']['submitted'] + 1 && $compare($r3, $expected($OPEN)), "D9 pengiriman ASN menaikkan terkirim sebanyak 1 dan angka tetap cocok ($c)");
$S = (int) $q("SELECT subject_id FROM assignments WHERE period_id=$OPEN GROUP BY subject_id HAVING SUM(status<>'submitted')>0 ORDER BY subject_id LIMIT 1")['subject_id'];
$pdo->exec("UPDATE assignments SET status='submitted',submitted_at=NOW() WHERE period_id=$OPEN AND subject_id=$S");
[, $r4] = $adm->call('admin_dashboard', ['period_id' => $OPEN]);
check($r4['summary']['subjects_done'] === $r3['summary']['subjects_done'] + 1 && $compare($r4, $expected($OPEN)), 'D10 pegawai yang seluruh penugasannya terkirim dihitung selesai dinilai');

// Periode draf tanpa penugasan: semua nol.
[, $b] = $adm->call('period_create', ['year' => 2033, 'quarter' => 1]); $D = (int) ($b['id'] ?? 0);
[$c, $r5] = $adm->call('admin_dashboard', ['period_id' => $D]);
check($c === 200 && $r5['period']['status'] === 'draft' && $r5['summary']['total'] === 0 && $r5['summary']['opds_assigned'] === 0 && $r5['summary']['raters'] === 0, "D11 periode draf tanpa penugasan: seluruh angka nol ($c)");
qa_summary();
