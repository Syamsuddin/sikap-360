<?php
// Bobot komposisi yang diubah administrator kabupaten (weights_save) dan salinan bobot saat publikasi (black-box HTTP + asersi DB).
// Prasyarat sama dengan api_invariants.php; jalankan reseed dulu. Jalankan: DB_DATABASE=sikap360_test php tests/weights_flows.php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php'; require __DIR__ . '/http_client.php';
$pdo = qa_pdo(); $q = static fn(string $sql) => $pdo->query($sql)->fetch();
$id = static fn(int $subject, int $rater, int $period): int => (int) $pdo->query("SELECT id FROM assignments WHERE period_id=$period AND subject_id=$subject AND rater_id=$rater")->fetchColumn();
$seven = static fn(int $s) => array_fill_keys(range(1, 7), $s);
$default = ['atasan,bawahan,rekan' => ['atasan' => 60, 'rekan' => 25, 'bawahan' => 15], 'atasan,rekan' => ['atasan' => 75, 'rekan' => 25], 'atasan,bawahan' => ['atasan' => 85, 'bawahan' => 15]];
$custom = ['atasan,bawahan,rekan' => ['atasan' => 50, 'rekan' => 30, 'bawahan' => 20], 'atasan,rekan' => ['atasan' => 60, 'rekan' => 40], 'atasan,bawahan' => ['atasan' => 80, 'bawahan' => 20]];
$with = static function (array $table, string $key, array $roles): array { $table[$key] = $roles; return $table; };

$adm = new QaHttp('wadm'); check($adm->login('pegawai1@example.test') === 200, 'W0 login admin kabupaten');
$asn = new QaHttp('wasn'); $asn->login('pegawai3@example.test');
$opd = new QaHttp('wopd'); $opd->login('pegawai2@example.test');
$b = $adm->bootstrap(); check($b['weights'] === $default && $b['period']['weights'] === $default && !array_key_exists('weights', $b['periods'][0]), 'W1 bootstrap: bobot bawaan, bobot periode terpilih, daftar periode tanpa salinan bobot');
[$c] = $asn->call('weights_save', ['weights' => $custom]); check($c === 403, "W2 ASN ubah bobot → 403 ($c)");
[$c] = $opd->call('weights_save', ['weights' => $custom]); check($c === 403, "W3 admin OPD ubah bobot → 403 ($c)");
foreach ([
 'jumlah 99' => $with($custom, 'atasan,rekan', ['atasan' => 60, 'rekan' => 39]),
 'jumlah 101' => $with($custom, 'atasan,bawahan,rekan', ['atasan' => 51, 'rekan' => 30, 'bawahan' => 20]),
 'pecahan' => $with($custom, 'atasan,rekan', ['atasan' => 60.5, 'rekan' => 39.5]),
 'teks angka' => $with($custom, 'atasan,rekan', ['atasan' => '60', 'rekan' => 40]),
 'nol' => $with($custom, 'atasan,bawahan', ['atasan' => 100, 'bawahan' => 0]),
 'peran hilang' => $with($custom, 'atasan,rekan', ['atasan' => 100]),
 'kondisi hilang' => array_diff_key($custom, ['atasan,bawahan' => true]),
 'bukan objek' => 'x',
] as $label => $weights) { [$c] = $adm->call('weights_save', ['weights' => $weights]); check($c === 422, "W4 bobot $label → 422 ($c)"); }
check((int) $q("SELECT COUNT(*) n FROM settings WHERE name='scoring_weights'")['n'] === 0, 'W5 bobot tidak valid tidak tersimpan');
[$c, $r] = $adm->call('weights_save', ['weights' => $custom]); check($c === 200 && $r['weights'] === $custom, "W6 admin kabupaten simpan bobot 50/30/20, 60/40, 80/20 ($c)");
check($adm->bootstrap()['weights'] === $custom && $asn->bootstrap()['weights'] === $custom, 'W7 bootstrap admin dan ASN memuat bobot baru');
$meta = json_decode((string) $q("SELECT metadata FROM audit_logs WHERE action='weights_saved' ORDER BY id DESC LIMIT 1")['metadata'], true);
check(($meta['from'] ?? null) == $default && ($meta['to'] ?? null) == $custom, 'W8 audit mencatat bobot lama dan baru');

// Periode terpublikasi dari seeder (salinan bobot kosong) tetap memakai bobot bawaan walau bobot sudah diubah.
$PUB = (int) $q("SELECT id FROM periods WHERE status='published' ORDER BY id LIMIT 1")['id'];
$b = $adm->bootstrap($PUB); check($b['period']['weights'] === $default && in_array($b['result']['weights'], $default, true), 'W9 periode terpublikasi lama tetap memakai bobot bawaan');

// Periode baru: pegawai 3 dinilai atasan (5) dan tiga rekan (4). Dipublikasikan dengan bobot 60/40 → (0,6·5 + 0,4·4)·20 = 92.
$pdo->exec("UPDATE periods SET name='Triwulan I 2020', start_date='2020-01-01', end_date='2020-03-31' WHERE id=1"); // kosongkan triwulan berjalan
[$c, $b] = $adm->call('period_create', ['year' => (int) date('Y'), 'quarter' => (int) ceil((int) date('n') / 3)]); $P = (int) ($b['id'] ?? 0); check($c === 200 && $P > 0, "W10 periode triwulan berjalan dibuat ($c)");
foreach ([[2, 'atasan'], [4, 'rekan'], [5, 'rekan'], [6, 'rekan']] as [$r, $role]) $adm->call('assignment_create', ['period_id' => $P, 'subject_id' => 3, 'rater_id' => $r, 'rater_role' => $role]);
[$c] = $adm->call('period_status', ['period_id' => $P, 'status' => 'open']); check($c === 200, "W11 periode dibuka ($c)");
foreach ([2 => 5, 4 => 4, 5 => 4, 6 => 4] as $rater => $score) { $cl = new QaHttp("wr$rater"); $cl->login("pegawai$rater@example.test"); $aid = $id(3, $rater, $P); $cl->call('save_draft', ['id' => $aid, 'version' => 1, 'answers' => $seven($score), 'feedback' => '']); $cl->call('submit', ['period_id' => $P, 'ids' => [$aid]]); }
check($asn->bootstrap($P)['period']['weights'] === $custom && $q("SELECT weights FROM periods WHERE id=$P")['weights'] === null, 'W12 periode belum terpublikasi mengikuti bobot yang berlaku, belum ada salinan');
$adm->call('period_status', ['period_id' => $P, 'status' => 'closed']); [$c] = $adm->call('period_status', ['period_id' => $P, 'status' => 'published']);
check($c === 200 && json_decode((string) $q("SELECT weights FROM periods WHERE id=$P")['weights'], true) == $custom, "W13 publikasi menyimpan salinan bobot yang berlaku ($c)");
$res = $asn->bootstrap($P)['result']; check($res['visible'] === true && (float) $res['score'] === 92.0 && $res['weights'] === ['atasan' => 60, 'rekan' => 40], 'W14 hasil memakai bobot 60/40 = 92');
[$c] = $adm->call('weights_save', ['weights' => $default]); $b = $asn->bootstrap($P); $res = $b['result'];
check($c === 200 && $b['weights'] === $default && $b['period']['weights'] === $custom && (float) $res['score'] === 92.0 && $res['weights'] === ['atasan' => 60, 'rekan' => 40], "W15 bobot diubah lagi: hasil periode terpublikasi tetap 92 dengan bobot 60/40 ($c)");
$pdo->exec("DELETE FROM settings WHERE name='scoring_weights'");
qa_summary();
