<?php
// Klien HTTP kecil untuk test black-box terhadap server uji (php -S ... -t public dengan DB_DATABASE=*_test).
// Env: QA_BASE (default http://127.0.0.1:8765). Satu cookie jar per nama sesi.
declare(strict_types=1);
final class QaHttp
{
    public string $base; public string $jar; public string $csrf = '';
    public array $extraHeaders = [];
    public function __construct(string $session = 'default') { $this->base = rtrim(getenv('QA_BASE') ?: 'http://127.0.0.1:8765', '/'); $this->jar = sys_get_temp_dir() . '/qa_jar_' . $session . '_' . getmypid(); @unlink($this->jar); }
    /** @return array{0:int,1:array|null,2:string} [status, json, raw] */
    public function raw(string $method, string $url, ?string $body, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_TIMEOUT => 20, CURLOPT_COOKIEJAR => $this->jar, CURLOPT_COOKIEFILE => $this->jar, CURLOPT_HTTPHEADER => $headers]);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $raw = (string) curl_exec($ch); $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $json = json_decode($raw, true);
        return [$code, is_array($json) ? $json : null, $raw];
    }
    public function call(string $action, mixed $body = [], array $opt = []): array
    {
        $h = ['Content-Type: ' . ($opt['ctype'] ?? 'application/json'), 'Origin: ' . ($opt['origin'] ?? $this->base)];
        if (!($opt['nocsrf'] ?? false)) $h[] = 'X-CSRF-Token: ' . ($opt['csrf'] ?? $this->csrf);
        $payload = is_string($body) ? $body : json_encode($body, JSON_THROW_ON_ERROR);
        return $this->raw($opt['method'] ?? 'POST', $this->base . '/api.php?action=' . rawurlencode($action), $payload, array_merge($h, $this->extraHeaders));
    }
    public function login(string $username, string $password = 'SikapDemo2026!', string $field = 'username'): int
    {
        [$code] = $this->call('login', [$field => $username, 'password' => $password], ['nocsrf' => true]);
        if ($code === 200) { [, $b] = $this->call('bootstrap', [], ['nocsrf' => true]); $this->csrf = (string) ($b['csrf'] ?? ''); }
        return $code;
    }
    public function bootstrap(int $periodId = 0): array { [, $b] = $this->call('bootstrap', $periodId ? ['period_id' => $periodId] : []); $this->csrf = (string) ($b['csrf'] ?? $this->csrf); return $b ?? []; }
}
const QA_LEAK = '/(Fatal error|Warning:|Notice:|Deprecated:|Uncaught|SQLSTATE|PDOException|Stack trace|on line \d+|\.php)/i';
function leak(string $raw): bool { return (bool) preg_match(QA_LEAK, $raw); }
