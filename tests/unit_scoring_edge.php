<?php
// Tahap 2 QA — kasus batas & invarian Scoring (tanpa DB). Jalankan: DB_DATABASE=sikap360_test php tests/unit_scoring_edge.php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require dirname(__DIR__) . '/app/Scoring.php';

$ind = array_map(static fn($id) => ['id' => $id, 'name' => 'I' . $id], range(1, 7));
$ids = range(1, 7);
$make = static fn($role, $score, $status = 'submitted') => ['rater_role' => $role, 'status' => $status, 'answers' => array_fill_keys(range(1, 7), $score)];
$full = [$make('atasan', 5), $make('rekan', 4), $make('rekan', 4), $make('rekan', 4), $make('bawahan', 3), $make('bawahan', 3), $make('bawahan', 3)];

// --- weights: invarian tidak melempar untuk input aneh ---
check(Scoring::weights([]) === null, 'weights([]) null');
check(Scoring::weights(['atasan']) === null, 'weights atasan saja null (R-01/02)');
check(Scoring::weights(['atasan', 'atasan', 'rekan']) === ['atasan' => 75, 'rekan' => 25], 'weights duplikat peran di-unique');
check(Scoring::weights(['Atasan', 'rekan']) === null, 'weights peka huruf besar → null');
check(Scoring::weights(['atasan', 'rekan', 'xyz']) === null, 'weights peran asing null');

// --- validateAnswers: tipe salah tidak boleh TypeError, harus DomainException (R-04) ---
foreach ([[1 => '4'], [1 => true], [1 => null], [1 => [4]], [1 => -1], [1 => PHP_INT_MAX], ['abc' => 4], [0 => 4], [-1 => 4], [8 => 4], [1 => 4.0]] as $i => $bad) {
    rejects(static fn() => Scoring::validateAnswers($bad, $ids, false), 'validateAnswers tolak kasus #' . $i . ' ' . json_encode($bad));
}
$okPartial = [1 => 4, 7 => 5];
Scoring::validateAnswers($okPartial, $ids, false); check(true, 'validateAnswers parsial (draft) diterima');
Scoring::validateAnswers(array_fill_keys($ids, 3), $ids, true); check(true, 'validateAnswers lengkap 7 diterima');
rejects(static fn() => Scoring::validateAnswers([], $ids, true), 'validateAnswers kosong + complete ditolak (R-05)');
rejects(static fn() => Scoring::validateAnswers(array_fill_keys(range(1, 6), 3), $ids, true), 'validateAnswers 6 jawaban + complete ditolak (R-05)');
// key string numerik dengan nol di depan: (int)'01'==1 lolos — dicatat sebagai perilaku, bukan harapan
$dupKey = ['1' => 4, '01' => 5, 2 => 3, 3 => 3, 4 => 3, 5 => 3, 6 => 3, 7 => 3];
rejects(static fn() => Scoring::validateAnswers($dupKey, $ids, true), 'validateAnswers kunci "01" duplikat + complete ditolak');
rejects(static fn() => Scoring::validateAnswers($dupKey, $ids, false), 'validateAnswers kunci "01" ditolak juga pada mode draft (OBS-09)');
rejects(static fn() => Scoring::validateAnswers([' 1' => 4], $ids, false), 'validateAnswers kunci " 1" ditolak (OBS-09)');

// --- validateComposition (R-03) ---
rejects(static fn() => Scoring::validateComposition([]), 'komposisi kosong ditolak');
rejects(static fn() => Scoring::validateComposition([$make('atasan', 4), $make('bawahan', 4), $make('bawahan', 4)]), '2 bawahan ditolak');
rejects(static fn() => Scoring::validateComposition([$make('rekan', 4), $make('rekan', 4), $make('rekan', 4)]), 'tanpa atasan ditolak');
Scoring::validateComposition([$make('atasan', 4), $make('bawahan', 4), $make('bawahan', 4), $make('bawahan', 4)]); check(true, 'atasan+3 bawahan diterima');

// --- calculate: invarian (tidak DivisionByZero/TypeError), nilai dari spec R-06 ---
$r = Scoring::calculate($full, []); check($r['complete'] === false && $r['score'] === null, 'calculate indikator kosong → tidak complete, tanpa DivisionByZero');
$r = Scoring::calculate([$make('atasan', 5)], $ind); check($r['complete'] === false && $r['score'] === null && $r['weights'] === null, 'calculate atasan saja → weights null, score null');
$pending = array_map(static fn($x) => array_replace($x, ['status' => 'pending', 'answers' => []]), $full);
$r = Scoring::calculate($pending, $ind); check($r['received'] === 0 && $r['expected'] === 7 && $r['score'] === null, 'calculate semua pending → received 0, score null');
$r = Scoring::calculate($full, $ind); check($r['score'] === 89.0 && count($r['dimensions']) === 7 && $r['groups'][0]['weight'] === 60, 'calculate 89 + 7 dimensi + grup 60/25/15 (R-01)');
$mixed = $full; $mixed[0]['answers'] = [1 => 5, 2 => 1, 3 => 5, 4 => 1, 5 => 5, 6 => 1, 7 => 5];
$r = Scoring::calculate($mixed, $ind); check($r['complete'] === true && $r['dimensions'][1]['score'] === 41.0, 'dimensi per indikator: atasan 1, rekan 4, bawahan 3 → (0.6·1+0.25·4+0.15·3)×20 = 41 (R-01/R-06)');
$badComp = [$make('atasan', 5), $make('rekan', 4), $make('rekan', 4)];
$r = Scoring::calculate($badComp, $ind); check($r['complete'] === false && $r['score'] === null, 'komposisi tak valid (2 rekan) → complete false walau semua submitted');
$strScore = $full; $strScore[0]['answers'][1] = '5';
$r = Scoring::calculate($strScore, $ind); check($r['complete'] === false, 'skor string "5" pada baris submitted → complete false (strict)');
$missingKey = $full; $missingKey[0]['answers'] = [];
$r = Scoring::calculate($missingKey, $ind); check($r['complete'] === false && $r['score'] === null, 'submitted tanpa jawaban → complete false, tanpa warning undefined index');
qa_summary();
