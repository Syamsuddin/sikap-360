<?php
declare(strict_types=1);
$envFile = dirname(__DIR__) . '/.env';
$local = is_file($envFile) ? parse_ini_file($envFile, false, INI_SCANNER_RAW) : [];
$env = static fn(string $key, string $default = ''): string => (string) (getenv($key) !== false ? getenv($key) : ($local[$key] ?? $default));
return [
 'db_host' => $env('DB_HOST', '127.0.0.1'),
 'db_port' => $env('DB_PORT', '3306'),
 'db_name' => $env('DB_DATABASE', 'sikap360'),
 'db_user' => $env('DB_USERNAME', 'sikap'),
 'db_password' => $env('DB_PASSWORD'),
 'app_url' => rtrim($env('APP_URL', 'http://localhost:8080'), '/'),
 'timezone' => $env('APP_TIMEZONE', 'Asia/Makassar'),
 'secure_cookie' => $env('SESSION_SECURE', '0') === '1',
 'session_ttl' => 1800,
 'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', $env('TRUSTED_PROXIES'))))),
 'login_limit_account' => 10,
 'login_limit_ip' => 100,
];
