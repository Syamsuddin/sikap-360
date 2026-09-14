<?php
// Unit — Period: triwulan kalender (nama, rentang tanggal, batas, pergeseran).
declare(strict_types=1);
require __DIR__ . '/bootstrap.php'; require dirname(__DIR__) . '/app/Period.php';
$q = Period::quarter(2026, 1); check($q === ['name' => 'Triwulan I 2026', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31'], 'Q1 Jan–Mar');
$q = Period::quarter(2026, 2); check($q['start_date'] === '2026-04-01' && $q['end_date'] === '2026-06-30' && $q['name'] === 'Triwulan II 2026', 'Q2 Apr–Jun');
$q = Period::quarter(2026, 3); check($q['start_date'] === '2026-07-01' && $q['end_date'] === '2026-09-30', 'Q3 Jul–Sep');
$q = Period::quarter(2026, 4); check($q['start_date'] === '2026-10-01' && $q['end_date'] === '2026-12-31' && $q['name'] === 'Triwulan IV 2026', 'Q4 Okt–Des');
check(Period::quarter(2028, 1)['end_date'] === '2028-03-31', 'tahun kabisat tidak mengubah akhir triwulan I');
foreach ([[2026, 0], [2026, 5], [1999, 1], [2101, 4]] as [$y, $n]) rejects(static fn() => Period::quarter($y, $n), "tolak $y/$n");
check(Period::at(new DateTimeImmutable('2026-09-13')) === ['year' => 2026, 'quarter' => 3], 'at: 13 Sep 2026 = 2026/3');
check(Period::at(new DateTimeImmutable('2026-01-01'), -1) === ['year' => 2025, 'quarter' => 4], 'at -1 dari Q1 = Q4 tahun lalu');
check(Period::at(new DateTimeImmutable('2026-10-01'), 1) === ['year' => 2027, 'quarter' => 1], 'at +1 dari Q4 = Q1 tahun depan');
check(Period::at(new DateTimeImmutable('2026-06-30'), 2) === ['year' => 2026, 'quarter' => 4], 'at +2 dari Q2 = Q4');
qa_summary();
