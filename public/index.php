<?php
// Public entrypoint. Database and session access are confined to api.php.
declare(strict_types=1);
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
header('Cache-Control: no-cache');
// Aset di-cache lama oleh server web; penanda versi (waktu ubah berkas) memaksa browser mengambil berkas baru setiap rilis.
echo preg_replace_callback('#(src|href)="(assets/[^"?]+)"#', static fn(array $m): string => $m[1] . '="' . $m[2] . '?v=' . (@filemtime(__DIR__ . '/' . $m[2]) ?: 0) . '"', (string) file_get_contents(__DIR__ . '/index.html'));
