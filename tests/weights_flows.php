<?php
// Bobot komposisi per periode (weights_save): diubah selama draf, terkunci sejak periode dibuka, disalin ke periode baru (black-box HTTP + asersi DB).
// Prasyarat sama dengan api_invariants.php; jalankan reseed dulu. Jalankan: DB_DATABASE=sikap360_test php tests/weights_flows.php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php'; require __DIR__ . '/http_client.php';
$pdo = qa_pdo(); $q = static fn(string $sql) => $pdo->query($sql)->fetch();
$id = static fn(int $subject, int $rater, int $period): int => (int) $pdo->query("SELECT id FROM assignments WHERE period_id=$period AND subject_id=$subject AND rater_id=$rater")->fetchColumn();
$dbw = static fn(int $period) => json_decode((string) $q("SELECT weights FROM periods WHERE id=$period")['weights'], true);
$seven = static fn(int $s) => array_fill_keys(range(1, 7), $s);
$default = ['atasan,bawahan,rekan' => ['atasan' => 60, 'rekan' => 25, 'bawahan' => 15], 'atasan,rekan' => ['atasan' => 75, 'rekan' => 25], 'atasan,bawahan' => ['atasan' => 85, 'bawahan' => 15]];
$custom = ['atasan,bawahan,rekan' => ['atasan' => 50, 'rekan' => 30, 'bawahan' => 20], 'atasan,rekan' => ['atasan' => 60, 'rekan' => 40], 'atasan,bawahan' => ['atasan' => 80, 'bawahan' => 20]];
$with = static function (array $table, string $key, array $roles): array { $table[$key] = $roles; return $table; };
// Pegawai 3 dinilai atasan (pegawai 2) dan tiga rekan (pegawai 4–6): komposisi Kondisi 2.
$assign = static function (QaHttp $adm, int $period): void { foreach ([[2, 'atasan'], [4, 'rekan'], [5, 'rekan'], [6, 'rekan']] as [$r, $role]) $adm->call('assignment_create', ['period_id' => $period, 'subject_id' => 3, 'rater_id' => $r, 'rater_role' => $role]); };

$adm = new QaHttp('wadm'); check($adm->login('pegawai1@example.test') === 200, 'W0 login admin kabupaten');
$asn = new QaHttp('wasn'); $asn->login('pegawai3@example.test');
$opd = new QaHttp('wopd'); $opd->login('pegawai2@example.test');
$OPEN = (int) $q("SELECT id FROM periods WHERE status='open' ORDER BY id LIMIT 1")['id'];
$PUB = (int) $q("SELECT id FROM periods WHERE status='published' ORDER BY id LIMIT 1")['id'];
$b = $adm->bootstrap($OPEN); check(!array_key_exists('weights', $b) && $b['period']['weights'] === $default && !array_key_exists('weights', $b['periods'][0]), 'W1 bootstrap: bobot hanya melekat pada periode terpilih (bawaan), tanpa bobot global, daftar periode tanpa bobot');

// Periode draf baru menyalin bobot periode terbaru; periode seeder tanpa bobot berarti bobot bawaan.
[$c, $b] = $adm->call('period_create', ['year' => 2030, 'quarter' => 1]); $D = (int) ($b['id'] ?? 0);
check($c === 200 && $D > 0 && $dbw($D) == $default, "W2 periode draf baru menyimpan bobot bawaan dari periode terbaru ($c)");
[$c] = $asn->call('weights_save', ['period_id' => $D, 'weights' => $custom]); check($c === 403, "W3 ASN ubah bobot → 403 ($c)");
[$c] = $opd->call('weights_save', ['period_id' => $D, 'weights' => $custom]); check($c === 403, "W4 admin OPD ubah bobot → 403 ($c)");
foreach ([
 'jumlah 99' => $with($custom, 'atasan,rekan', ['atasan' => 60, 'rekan' => 39]),
 'jumlah 101' => $with($custom, 'atasan,bawahan,rekan', ['atasan' => 51, 'rekan' => 30, 'bawahan' => 20]),
 'pecahan' => $with($custom, 'atasan,rekan', ['atasan' => 60.5, 'rekan' => 39.5]),
 'teks angka' => $with($custom, 'atasan,rekan', ['atasan' => '60', 'rekan' => 40]),
 'nol' => $with($custom, 'atasan,bawahan', ['atasan' => 100, 'bawahan' => 0]),
 'peran hilang' => $with($custom, 'atasan,rekan', ['atasan' => 100]),
 'kondisi hilang' => array_diff_key($custom, ['atasan,bawahan' => true]),
 'bukan objek' => 'x',
] as $label => $weights) { [$c] = $adm->call('weights_save', ['period_id' => $D, 'weights' => $weights]); check($c === 422, "W5 bobot $label → 422 ($c)"); }
check($dbw($D) == $default, 'W6 bobot tidak valid tidak tersimpan');
[$c, $r] = $adm->call('weights_save', ['period_id' => $D, 'weights' => $custom]); check($c === 200 && $r['weights'] === $custom && $dbw($D) == $custom, "W7 admin kabupaten simpan bobot 50/30/20, 60/40, 80/20 untuk periode draf ($c)");
check($adm->bootstrap($D)['period']['weights'] === $custom && $asn->bootstrap($D)['period']['weights'] === $custom && $asn->bootstrap($OPEN)['period']['weights'] === $default && $q("SELECT weights FROM periods WHERE id=$OPEN")['weights'] === null, 'W8 bobot baru hanya berlaku untuk periode draf itu; periode aktif tidak berubah');
$audit = $q("SELECT entity_type,entity_id,metadata FROM audit_logs WHERE action='weights_saved' ORDER BY id DESC LIMIT 1"); $meta = json_decode((string) $audit['metadata'], true);
check($audit['entity_type'] === 'period' && (int) $audit['entity_id'] === $D && ($meta['from'] ?? null) == $default && ($meta['to'] ?? null) == $custom, 'W9 audit mencatat periode, bobot lama, dan bobot baru');
[$c] = $adm->call('weights_save', ['period_id' => $OPEN, 'weights' => $custom]); check($c === 409 && $q("SELECT weights FROM periods WHERE id=$OPEN")['weights'] === null, "W10 periode aktif: bobot terkunci → 409 ($c)");
[$c] = $adm->call('weights_save', ['period_id' => $PUB, 'weights' => $custom]); check($c === 409, "W11 periode terpublikasi: bobot terkunci → 409 ($c)");
[$c] = $adm->call('weights_save', ['period_id' => 999999, 'weights' => $custom]); check($c === 404, "W12 periode tidak ada → 404 ($c)");
$b = $adm->bootstrap($PUB); check($b['period']['weights'] === $default && in_array($b['result']['weights'], $default, true), 'W13 periode terpublikasi lama (bobot kosong) memakai bobot bawaan');

// Periode berikutnya menyalin bobot periode terbaru, yaitu D dengan bobot kustom.
[$c, $b] = $adm->call('period_create', ['year' => 2030, 'quarter' => 2]); $D2 = (int) ($b['id'] ?? 0);
check($c === 200 && $dbw($D2) == $custom, "W14 periode baru menyalin bobot periode terbaru (kustom) ($c)");

// Alur penuh pada D: atasan menilai 5 dan tiga rekan menilai 4. Bobot 60/40 → (0,6·5 + 0,4·4)·20 = 92.
$assign($adm, $D);
[$c] = $adm->call('period_status', ['period_id' => $D, 'status' => 'open']); check($c === 200 && $dbw($D) == $custom, "W15 periode dibuka: bobot kustom tetap, tidak ditimpa ($c)");
[$c] = $adm->call('weights_save', ['period_id' => $D, 'weights' => $default]); check($c === 409 && $dbw($D) == $custom, "W16 bobot terkunci setelah periode dibuka → 409 ($c)");
foreach ([2 => 5, 4 => 4, 5 => 4, 6 => 4] as $rater => $score) { $cl = new QaHttp("wr$rater"); $cl->login("pegawai$rater@example.test"); $aid = $id(3, $rater, $D); $cl->call('save_draft', ['id' => $aid, 'version' => 1, 'answers' => $seven($score), 'feedback' => '']); $cl->call('submit', ['period_id' => $D, 'ids' => [$aid]]); }
$adm->call('period_status', ['period_id' => $D, 'status' => 'closed']);
[$c] = $adm->call('weights_save', ['period_id' => $D, 'weights' => $default]); check($c === 409 && $dbw($D) == $custom, "W17 periode ditutup: bobot tetap terkunci → 409 ($c)");
[$c] = $adm->call('period_status', ['period_id' => $D, 'status' => 'published']); $res = $asn->bootstrap($D)['result'];
check($c === 200 && $dbw($D) == $custom && $res['visible'] === true && (float) $res['score'] === 92.0 && $res['weights'] === ['atasan' => 60, 'rekan' => 40], "W18 publikasi: hasil memakai bobot periode 60/40 = 92 ($c)");

// Periode tanpa bobot (data lama) mendapat bobot bawaan secara tertulis saat dibuka.
$pdo->exec("UPDATE periods SET weights=NULL WHERE id=$D2"); $assign($adm, $D2);
[$c] = $adm->call('period_status', ['period_id' => $D2, 'status' => 'open']); check($c === 200 && $dbw($D2) == $default, "W19 periode tanpa bobot menyimpan bobot bawaan saat dibuka ($c)");
qa_summary();
