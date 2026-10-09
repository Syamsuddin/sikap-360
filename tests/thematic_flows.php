<?php
// Indikator tematik per periode (thematic_save): tepat lima butir bertema, diubah selama draf, terkunci sejak dibuka, disalin ke periode baru,
// wajib dijawab bersama tujuh BerAKHLAK, dan masuk nilai akhir dengan bobot setara (black-box HTTP + asersi DB).
// Prasyarat sama dengan weights_flows.php; jalankan reseed dulu. Jalankan: DB_DATABASE=sikap360_test php tests/thematic_flows.php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php'; require __DIR__ . '/http_client.php';
$pdo = qa_pdo(); $q = static fn(string $sql) => $pdo->query($sql)->fetch();
$id = static fn(int $subject, int $rater, int $period): int => (int) $pdo->query("SELECT id FROM assignments WHERE period_id=$period AND subject_id=$subject AND rater_id=$rater")->fetchColumn();
$names = static fn(int $period): array => $pdo->query("SELECT name FROM indicators WHERE period_id=$period ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
$ids = static fn(int $period): array => array_map('intval', $pdo->query("SELECT id FROM indicators WHERE period_id=$period ORDER BY id")->fetchAll(PDO::FETCH_COLUMN));
$theme = static fn(int $period) => $q("SELECT theme FROM periods WHERE id=$period")['theme'];
$items = static fn(string $prefix): array => array_map(static fn(int $n) => ['name' => "$prefix $n", 'description' => "Uraian perilaku $prefix $n."], range(1, 5));
// Jawaban seluruh indikator periode: $b untuk BerAKHLAK, $t untuk tematik.
$answers = static function (array $indicators, int $b, int $t): array { $out = []; foreach ($indicators as $i) $out[(int) $i['id']] = $i['category'] === 'tematik' ? $t : $b; return $out; };
$set = $items('Antikorupsi');

$adm = new QaHttp('tadm'); check($adm->login('pegawai1@example.test') === 200, 'T0 login admin kabupaten');
$asn = new QaHttp('tasn'); $asn->login('pegawai3@example.test');
$opd = new QaHttp('topd'); $opd->login('pegawai2@example.test');
$OPEN = (int) $q("SELECT id FROM periods WHERE status='open' ORDER BY id LIMIT 1")['id'];
$PUB = (int) $q("SELECT id FROM periods WHERE status='published' ORDER BY id LIMIT 1")['id'];
$b = $asn->bootstrap($OPEN); check(count($b['indicators']) === 7 && array_unique(array_column($b['indicators'], 'category')) === ['berakhlak'] && $b['period']['theme'] === null, 'T1 periode tanpa tematik: tujuh indikator BerAKHLAK, tanpa tema');

[$c, $b] = $adm->call('period_create', ['year' => 2031, 'quarter' => 1]); $D = (int) ($b['id'] ?? 0);
check($c === 200 && $D > 0 && $names($D) === [] && $theme($D) === null, "T2 periode baru dari periode terbaru tanpa tematik: tanpa tema dan butir ($c)");
[$c] = $asn->call('thematic_save', ['period_id' => $D, 'theme' => 'X', 'indicators' => $set]); check($c === 403, "T3 ASN ubah tematik → 403 ($c)");
[$c] = $opd->call('thematic_save', ['period_id' => $D, 'theme' => 'X', 'indicators' => $set]); check($c === 403, "T4 admin OPD ubah tematik → 403 ($c)");
foreach ([
 'empat butir' => ['Antikorupsi', array_slice($set, 0, 4)],
 'enam butir' => ['Antikorupsi', [...$set, ['name' => 'Lain', 'description' => 'x']]],
 'tanpa daftar' => ['Antikorupsi', null],
 'bukan daftar' => ['Antikorupsi', array_combine(['a', 'b', 'c', 'd', 'e'], $set)],
 'butir bukan objek' => ['Antikorupsi', ['a', 'b', 'c', 'd', 'e']],
 'tema kosong' => ['  ', $set],
 'tema 121 karakter' => [str_repeat('t', 121), $set],
 'nama kosong' => ['Antikorupsi', array_replace($set, [2 => ['name' => ' ', 'description' => 'x']])],
 'nama 81 karakter' => ['Antikorupsi', array_replace($set, [0 => ['name' => str_repeat('n', 81), 'description' => 'x']])],
 'nama bukan teks' => ['Antikorupsi', array_replace($set, [0 => ['name' => 5, 'description' => 'x']])],
 'uraian kosong' => ['Antikorupsi', array_replace($set, [4 => ['name' => 'Baru', 'description' => '']])],
 'uraian 601 karakter' => ['Antikorupsi', array_replace($set, [1 => ['name' => 'Baru', 'description' => str_repeat('u', 601)]])],
 'nama ganda' => ['Antikorupsi', array_replace($set, [1 => ['name' => 'ANTIKORUPSI 1', 'description' => 'x']])],
] as $label => [$t, $list]) { [$c, , $raw] = $adm->call('thematic_save', ['period_id' => $D, 'theme' => $t, 'indicators' => $list]); check($c === 422 && !leak($raw), "T5 tematik $label → 422 ($c)"); }
check($names($D) === [] && $theme($D) === null, 'T6 isian tidak valid tidak tersimpan');
[$c, $r] = $adm->call('thematic_save', ['period_id' => $D, 'theme' => ' Pemberantasan Korupsi ', 'indicators' => $set]);
check($c === 200 && ($r['theme'] ?? null) === 'Pemberantasan Korupsi' && $names($D) === array_column($set, 'name') && $theme($D) === 'Pemberantasan Korupsi', "T7 admin kabupaten simpan tema dan lima butir tematik periode draf ($c)");
$b = $asn->bootstrap($D);
check(array_column($b['indicators'], 'category') === [...array_fill(0, 7, 'berakhlak'), ...array_fill(0, 5, 'tematik')] && array_column(array_slice($b['indicators'], 7), 'name') === array_column($set, 'name') && $b['period']['theme'] === 'Pemberantasan Korupsi', 'T8 bootstrap periode bertematik: 7 BerAKHLAK lalu 5 tematik berurutan, beserta tema');
check(count($asn->bootstrap($OPEN)['indicators']) === 7 && count($asn->bootstrap($PUB)['indicators']) === 7, 'T9 tematik periode draf tidak memengaruhi periode lain');
$old = $ids($D); [$c] = $adm->call('thematic_save', ['period_id' => $D, 'theme' => 'Pemberantasan Korupsi', 'indicators' => $items('Integritas')]);
check($c === 200 && $names($D) === array_column($items('Integritas'), 'name') && (int) $q('SELECT COUNT(*) AS c FROM indicators WHERE id IN (' . implode(',', $old) . ')')['c'] === 0, "T10 simpan ulang menggantikan seluruh butir lama ($c)");
$audit = $q("SELECT entity_type,entity_id,metadata FROM audit_logs WHERE action='thematic_saved' ORDER BY id DESC LIMIT 1"); $meta = json_decode((string) $audit['metadata'], true);
check($audit['entity_type'] === 'period' && (int) $audit['entity_id'] === $D && $meta['from']['names'] === array_column($set, 'name') && $meta['to']['names'] === array_column($items('Integritas'), 'name') && $meta['to']['theme'] === 'Pemberantasan Korupsi', 'T11 audit mencatat tema dan butir lama serta baru');
$adm->call('thematic_save', ['period_id' => $D, 'theme' => 'Pemberantasan Korupsi', 'indicators' => $set]);
[$c] = $adm->call('thematic_save', ['period_id' => 999999, 'theme' => 'X', 'indicators' => $set]); check($c === 404, "T12 periode tidak ada → 404 ($c)");
[$c] = $adm->call('thematic_save', ['period_id' => $OPEN, 'theme' => 'X', 'indicators' => $set]); check($c === 409 && $names($OPEN) === [], "T13 periode aktif: tematik terkunci → 409 ($c)");
[$c] = $adm->call('thematic_save', ['period_id' => $PUB, 'theme' => 'X', 'indicators' => $set]); check($c === 409 && $names($PUB) === [], "T14 periode terpublikasi: tematik terkunci → 409 ($c)");

// Periode berikutnya menyalin tema dan butir tematik periode terbaru (D) dengan ID baru; mengubah salinan tidak mengubah asalnya.
[$c, $b] = $adm->call('period_create', ['year' => 2031, 'quarter' => 2]); $D2 = (int) ($b['id'] ?? 0);
check($c === 200 && $theme($D2) === 'Pemberantasan Korupsi' && $names($D2) === array_column($set, 'name') && !array_intersect($ids($D2), $ids($D)), "T15 periode baru menyalin tema dan lima butir tematik dengan ID baru ($c)");
[$c] = $adm->call('thematic_save', ['period_id' => $D2, 'theme' => 'Pelayanan Publik', 'indicators' => $items('Layanan')]);
check($c === 200 && $names($D) === array_column($set, 'name') && $theme($D) === 'Pemberantasan Korupsi', "T16 mengubah salinan tidak mengubah periode asal ($c)");

// Alur penuh pada D: pegawai 3 dinilai atasan (pegawai 2) dan tiga rekan (pegawai 4–6), Kondisi 2 bobot 75/25.
foreach ([[2, 'atasan'], [4, 'rekan'], [5, 'rekan'], [6, 'rekan']] as [$r, $role]) $adm->call('assignment_create', ['period_id' => $D, 'subject_id' => 3, 'rater_id' => $r, 'rater_role' => $role]);
[$c] = $adm->call('period_status', ['period_id' => $D, 'status' => 'open']); check($c === 200, "T17 periode bertematik dibuka ($c)");
[$c] = $adm->call('thematic_save', ['period_id' => $D, 'theme' => 'Lain', 'indicators' => $items('Lain')]); check($c === 409 && $names($D) === array_column($set, 'name'), "T18 tematik terkunci setelah periode dibuka → 409 ($c)");
$ind = $asn->bootstrap($D)['indicators']; $aid = $id(3, 2, $D);
$r2 = new QaHttp('tr2'); $r2->login('pegawai2@example.test');
[$c, , $raw] = $r2->call('save_draft', ['id' => $aid, 'version' => 1, 'answers' => [1 => 4, $ids($D2)[0] => 4], 'feedback' => '']);
check($c === 422 && !leak($raw) && (int) $q("SELECT COUNT(*) AS c FROM answers WHERE assignment_id=$aid")['c'] === 0, "T19 jawaban untuk indikator tematik periode lain → 422 ($c)");
[$c] = $r2->call('save_draft', ['id' => $aid, 'version' => 1, 'answers' => array_fill_keys(range(1, 7), 5), 'feedback' => '']); check($c === 200, "T20 draf berisi tujuh jawaban BerAKHLAK tersimpan ($c)");
[$c, $b] = $r2->call('submit', ['period_id' => $D, 'ids' => [$aid]]);
check($c === 422 && ($b['error'] ?? '') === 'Lengkapi seluruh 12 indikator sebelum mengirim.' && $q("SELECT status FROM assignments WHERE id=$aid")['status'] === 'draft', "T21 kirim tanpa jawaban tematik → 422 dan tetap draf ($c)");
// Atasan: BerAKHLAK 5, tematik 3; rekan: semua 4. Per indikator BerAKHLAK (0,75·5 + 0,25·4)·20 = 95, tematik (0,75·3 + 0,25·4)·20 = 65; nilai akhir (7·95 + 5·65) / 12 = 82,5.
foreach ([2 => [5, 3], 4 => [4, 4], 5 => [4, 4], 6 => [4, 4]] as $rater => [$bs, $ts]) {
 $cl = $rater === 2 ? $r2 : new QaHttp("tr$rater"); if ($rater !== 2) $cl->login("pegawai$rater@example.test");
 $a = $id(3, $rater, $D); $v = (int) $q("SELECT version FROM assignments WHERE id=$a")['version'];
 $cl->call('save_draft', ['id' => $a, 'version' => $v, 'answers' => $answers($ind, $bs, $ts), 'feedback' => '']); [$c] = $cl->call('submit', ['period_id' => $D, 'ids' => [$a]]);
 check($c === 200 && (int) $q("SELECT COUNT(*) AS c FROM answers WHERE assignment_id=$a")['c'] === 12, "T22 penilai $rater mengirim 12 jawaban ($c)");
}
$adm->call('period_status', ['period_id' => $D, 'status' => 'closed']);
[$c] = $adm->call('period_status', ['period_id' => $D, 'status' => 'published']); $res = $asn->bootstrap($D)['result'];
check($c === 200 && $res['visible'] === true && (float) $res['score'] === 82.5 && count($res['dimensions']) === 12, "T23 publikasi: nilai akhir rata-rata 12 indikator = 82,5 ($c)");
check($res['categories'] == [['category' => 'berakhlak', 'count' => 7, 'score' => 95], ['category' => 'tematik', 'count' => 5, 'score' => 65]] && $res['dimensions'][7]['category'] === 'tematik', 'T24 rincian kategori: BerAKHLAK 95, tematik 65');

// Penilaian terkirim dengan tujuh jawaban pada periode bertematik (penilaian sebelum tematik dipasang) tidak lengkap, sehingga publikasi diblokir.
foreach ([[2, 'atasan'], [4, 'rekan']] as [$r, $role]) $adm->call('assignment_create', ['period_id' => $D2, 'subject_id' => 3, 'rater_id' => $r, 'rater_role' => $role]);
$adm->call('period_status', ['period_id' => $D2, 'status' => 'open']);
foreach ([2, 4] as $r) { $a = $id(3, $r, $D2); foreach (range(1, 7) as $i) $pdo->exec("INSERT INTO answers(assignment_id,indicator_id,score) VALUES($a,$i,4)"); $pdo->exec("UPDATE assignments SET status='submitted',submitted_at=NOW() WHERE id=$a"); }
$adm->call('period_status', ['period_id' => $D2, 'status' => 'closed']);
[$c] = $adm->call('period_status', ['period_id' => $D2, 'status' => 'published']); check($c === 409 && $q("SELECT status FROM periods WHERE id=$D2")['status'] === 'closed', "T25 penilaian terkirim tanpa jawaban tematik memblokir publikasi ($c)");
qa_summary();
