<?php
// Pengelolaan periode di laman Periode & penilai: hapus periode (period_delete) hanya untuk administrator kabupaten dan hanya periode draf tanpa penugasan;
// daftar periode bootstrap memuat jumlah penugasan dan yang terkirim per periode bagi admin (admin OPD: OPD-nya), tidak bagi ASN. Black-box HTTP + asersi DB.
// Prasyarat sama dengan weights_flows.php; jalankan reseed dulu. Jalankan: DB_DATABASE=sikap360_test php tests/period_flows.php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php'; require __DIR__ . '/http_client.php';
$pdo = qa_pdo(); $q = static fn(string $sql) => $pdo->query($sql)->fetch();
$exists = static fn(int $id): bool => (bool) $q("SELECT COUNT(*) AS c FROM periods WHERE id=$id")['c'];
$items = array_map(static fn(int $n) => ['name' => "Butir $n", 'description' => "Uraian butir $n."], range(1, 5));

$adm = new QaHttp('padm'); check($adm->login('pegawai1@example.test') === 200, 'P0 login admin kabupaten');
$asn = new QaHttp('pasn'); $asn->login('pegawai3@example.test');
$opd = new QaHttp('popd'); $opd->login('pegawai2@example.test');
$OPEN = (int) $q("SELECT id FROM periods WHERE status='open' ORDER BY id LIMIT 1")['id'];
$PUB = (int) $q("SELECT id FROM periods WHERE status='published' ORDER BY id LIMIT 1")['id'];

// Jumlah penugasan per periode di daftar periode: admin kabupaten seluruh kabupaten, admin OPD hanya pegawai dinilai di OPD-nya, ASN tidak menerima angka.
$counts = static fn(?int $opd) => array_column($pdo->query('SELECT a.period_id,COUNT(*) AS total,SUM(a.status=\'submitted\') AS submitted FROM assignments a JOIN employees s ON s.id=a.subject_id' . ($opd ? " WHERE s.opd_id=$opd" : '') . ' GROUP BY a.period_id')->fetchAll(), null, 'period_id');
$match = static function (array $periods, array $exp): bool { foreach ($periods as $p) { $e = $exp[$p['id']] ?? ['total' => 0, 'submitted' => 0]; if ($p['assignments_total'] !== (int) $e['total'] || $p['assignments_submitted'] !== (int) $e['submitted']) return false; } return true; };
check($match($adm->bootstrap()['periods'], $counts(null)), 'P1 daftar periode admin kabupaten: jumlah penugasan dan terkirim per periode sesuai database');
$opdId = (int) $q('SELECT opd_id FROM employees WHERE id=2')['opd_id'];
check($match($opd->bootstrap()['periods'], $counts($opdId)) && $counts($opdId) != $counts(null), 'P2 daftar periode admin OPD: jumlah dibatasi pegawai dinilai di OPD-nya');
check(!array_key_exists('assignments_total', $asn->bootstrap()['periods'][0]), 'P3 daftar periode ASN tidak memuat jumlah penugasan');

// Hapus periode: hak akses dan status.
[$c, $b] = $adm->call('period_create', ['year' => 2034, 'quarter' => 1]); $D = (int) ($b['id'] ?? 0);
$adm->call('thematic_save', ['period_id' => $D, 'theme' => 'Uji hapus', 'indicators' => $items]);
[$c] = $asn->call('period_delete', ['period_id' => $D]); check($c === 403 && $exists($D), "P4 ASN hapus periode → 403 ($c)");
[$c] = $opd->call('period_delete', ['period_id' => $D]); check($c === 403 && $exists($D), "P5 admin OPD hapus periode → 403 ($c)");
[$c] = $adm->call('period_delete', ['period_id' => 999999]); check($c === 404, "P6 periode tidak ada → 404 ($c)");
[$c, $b] = $adm->call('period_delete', ['period_id' => $OPEN]); check($c === 409 && $exists($OPEN) && str_contains($b['error'] ?? '', 'draf'), "P7 periode aktif tidak dapat dihapus → 409 ($c)");
[$c] = $adm->call('period_delete', ['period_id' => $PUB]); check($c === 409 && $exists($PUB), "P8 periode terpublikasi tidak dapat dihapus → 409 ($c)");
// Draf yang sudah memiliki penugasan ditolak dan penugasannya utuh.
[, $b] = $adm->call('period_create', ['year' => 2034, 'quarter' => 2]); $D2 = (int) ($b['id'] ?? 0);
$adm->call('assignment_create', ['period_id' => $D2, 'subject_id' => 3, 'rater_id' => 2, 'rater_role' => 'atasan']);
[$c, $b] = $adm->call('period_delete', ['period_id' => $D2]);
check($c === 409 && $exists($D2) && (int) $q("SELECT COUNT(*) AS c FROM assignments WHERE period_id=$D2")['c'] === 1 && str_contains($b['error'] ?? '', '1 penugasan'), "P9 draf yang sudah memiliki penugasan → 409, penugasan utuh ($c)");
// Draf kosong terhapus beserta indikator tematiknya, dan tercatat di audit.
check((int) $q("SELECT COUNT(*) AS c FROM indicators WHERE period_id=$D")['c'] === 5, 'P10 prasyarat: draf memiliki lima indikator tematik');
[$c] = $adm->call('period_delete', ['period_id' => $D]);
check($c === 200 && !$exists($D) && (int) $q("SELECT COUNT(*) AS c FROM indicators WHERE period_id=$D")['c'] === 0, "P11 draf tanpa penugasan terhapus beserta indikator tematiknya ($c)");
$audit = $q("SELECT entity_type,entity_id,metadata FROM audit_logs WHERE action='period_deleted' ORDER BY id DESC LIMIT 1"); $meta = json_decode((string) $audit['metadata'], true);
check($audit['entity_type'] === 'period' && (int) $audit['entity_id'] === $D && ($meta['name'] ?? '') === 'Triwulan I 2034' && ($meta['theme'] ?? '') === 'Uji hapus', 'P12 audit mencatat nama dan tema periode yang dihapus');
[$c] = $adm->call('period_delete', ['period_id' => $D]); check($c === 404, "P13 menghapus ulang periode yang sudah terhapus → 404 ($c)");
[$c] = $adm->call('period_create', ['year' => 2034, 'quarter' => 1]); check($c === 200, "P14 triwulan yang dihapus dapat dibuat ulang ($c)");
// Status periode lain dapat diubah dengan period_id eksplisit (tombol aksi per baris), tanpa memilih periode itu lebih dulu.
[$c] = $adm->call('period_status', ['period_id' => $D2, 'status' => 'open']); check($c === 422 || $c === 409, "P15 membuka draf dengan komposisi tidak sah tetap ditolak validasi ($c)");
$adm->call('assignment_create', ['period_id' => $D2, 'subject_id' => 3, 'rater_id' => 4, 'rater_role' => 'rekan']);
[$c] = $adm->call('period_status', ['period_id' => $D2, 'status' => 'open']); check($c === 200 && $q("SELECT status FROM periods WHERE id=$D2")['status'] === 'open' && $q("SELECT status FROM periods WHERE id=$OPEN")['status'] === 'open', "P16 periode lain dibuka lewat period_id; periode aktif sebelumnya tetap aktif ($c)");
qa_summary();
