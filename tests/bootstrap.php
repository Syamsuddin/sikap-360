<?php
// tests/bootstrap.php — pengaman: test hanya boleh menyentuh database berakhiran _test.
declare(strict_types=1);
$db = (string) getenv('DB_DATABASE');
if (!str_ends_with($db, '_test')) {
    fwrite(STDERR, "BERHENTI: DB_DATABASE='$db' bukan database testing (harus berakhiran _test).\n");
    exit(1);
}
$GLOBALS['qa_passed'] = 0; $GLOBALS['qa_failed'] = 0;
function check(bool $condition, string $name): void
{
    if ($condition) { $GLOBALS['qa_passed']++; echo "PASS: $name\n"; }
    else { $GLOBALS['qa_failed']++; echo "FAIL: $name\n"; }
}
function rejects(callable $fn, string $name, string $class = DomainException::class): void
{
    try { $fn(); } catch (Throwable $e) { check($e instanceof $class, $name . ' (' . get_class($e) . ')'); return; }
    check(false, $name . ' (tidak melempar)');
}
function qa_summary(): void
{
    $p = $GLOBALS['qa_passed']; $f = $GLOBALS['qa_failed'];
    echo "Total: $p lulus, $f gagal.\n";
    exit($f > 0 ? 1 : 0);
}
function qa_pdo(): PDO
{
    $config = require dirname(__DIR__) . '/config/app.php';
    require_once dirname(__DIR__) . '/app/Database.php';
    return Database::connect($config);
}
