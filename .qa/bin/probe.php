<?php
// Probe invarian HTTP black-box: status 500, error/SQL bocor ke layar, dan tamu menembus halaman terlindungi.
// Pakai: php probe.php BASE_URL endpoints.txt ["Cookie: nama=nilai"]
// Baris endpoints.txt: METHOD PATH [field1,field2=nilaiSah,...] [@auth]    (# = komentar)
//   field=nilaiSah : field lain diisi nilai sah saat satu field diserang, agar validasi awal tidak menghentikan uji.
//   @auth          : halaman wajib login; tanpa cookie, respons harus 301/302/401/403.
// Keluaran: maksimal satu bukti per (endpoint, field).
[, $base, $list] = $argv + [null, null, null];
if (!$base || !is_file((string) $list)) { fwrite(STDERR, "pakai: probe.php BASE_URL endpoints.txt [cookie]\n"); exit(2); }
$cookie = $argv[3] ?? '';
$leak = '/(Fatal error|Warning:|Notice:|Deprecated:|Uncaught|SQLSTATE|mysqli_|PDOException|Stack trace|Whoops|on line \d+)/i';
$vals = ['', "'", "' OR 1=1 -- ", '<script>x</script>', '0', '-1', 'A', '99999999999999999999', str_repeat('A', 5000), ['x']];
$defaultKeys = ['id', 'page', 'q', 'cari', 'search', 'sort', 'order', 'per_page', 'tahun', 'bulan'];
$bad = $n = 0;

function hit(string $m, string $url, array $p, string $cookie): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $m, CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_HTTPHEADER => $cookie ? [$cookie] : []]);
    if ($m !== 'GET') curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($p));
    $body = (string) curl_exec($ch);
    return [(int) curl_getinfo($ch, CURLINFO_HTTP_CODE), $body];
}

foreach (file($list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    $parts = preg_split('/\s+/', $line);
    $auth = in_array('@auth', $parts, true);
    $parts = array_values(array_filter($parts, fn ($x) => $x !== '@auth'));
    [$m, $path] = [strtoupper($parts[0]), $parts[1] ?? '/'];
    $spec = isset($parts[2]) ? explode(',', $parts[2]) : $defaultKeys;
    $valid = [];
    foreach ($spec as $f) { [$k, $v] = array_pad(explode('=', $f, 2), 2, null); if ($v !== null) $valid[$k] = $v; }
    $keys = array_map(fn ($f) => explode('=', $f, 2)[0], $spec);
    $n++;
    if ($auth && !$cookie) {   // invarian otorisasi: tamu harus ditolak/dialihkan
        [$code] = hit($m, rtrim($base, '/') . $path, [], '');
        if (!in_array($code, [301, 302, 303, 401, 403], true)) { $bad++; printf("%s %s [tamu] -> %d TAMU-TEMBUS\n", $m, $path, $code); }
        continue;
    }
    $groups = ['(tanpa parameter)' => [$valid]];
    foreach ($keys as $k) foreach ($vals as $v) $groups[$k][] = array_merge($valid, [$k => $v]);
    foreach ($groups as $label => $cases) {
        foreach ($cases as $p) {
            $url = rtrim($base, '/') . $path . ($m === 'GET' && $p ? '?' . http_build_query($p) : '');
            [$code, $body] = hit($m, $url, $p, $cookie);
            if ($code >= 500 || $code === 0 || preg_match($leak, strip_tags($body), $h)) {
                $bad++;
                $shown = $label === '(tanpa parameter)' ? '[]' : json_encode([$label => $p[$label]]);
                printf("%s %s %s -> %d %s\n", $m, $path, substr($shown, 0, 60), $code, $h[1] ?? '');
                break; // satu bukti per (endpoint, field)
            }
        }
    }
}
echo "PROBE: $n endpoint, $bad temuan\n";
exit($bad ? 1 : 0);
