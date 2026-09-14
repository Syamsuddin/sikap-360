<?php
// Ringkas JUnit XML -> hanya kegagalan. Pakai: php .qa/bin/junit-sum.php .qa/tmp/junit.xml [maks=25]
$x = @simplexml_load_file($argv[1] ?? '') ?: exit("junit tidak terbaca\n");
$max = (int) ($argv[2] ?? 25); $t = $f = $s = 0; $out = [];
foreach ($x->xpath('//testcase') as $c) {
    $t++;
    if (isset($c->skipped)) { $s++; continue; }
    $e = isset($c->failure) ? $c->failure : (isset($c->error) ? $c->error : null);
    if ($e === null) continue;
    if (++$f > $max) continue;
    $id = "{$c['class']}::{$c['name']}";
    $lines = array_slice(array_values(array_filter(array_map('trim', explode("\n", (string) $e)), fn ($l) => $l !== '' && $l !== $id)), 0, 3);
    $out[] = "FAIL $id\n  " . substr(implode(' | ', $lines), 0, 300);
}
echo implode("\n", $out), ($out ? "\n" : ''), "TOTAL $t | PASS " . ($t - $f - $s) . " | FAIL $f | SKIP $s\n";
exit($f ? 1 : 0);
