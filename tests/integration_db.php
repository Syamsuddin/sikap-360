<?php
// Tahap 3 QA — constraint skema pada DB *_test. Setiap blok dalam transaksi yang di-rollback.
// Jalankan: DB_DATABASE=sikap360_test php tests/integration_db.php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$pdo = qa_pdo();
$pdoRejects = static function (callable $fn, string $name, string $sqlstate = '23000') use ($pdo): void {
    $pdo->beginTransaction();
    try { $fn(); $pdo->rollBack(); check(false, $name . ' (tidak ditolak)'); }
    catch (PDOException $e) { $pdo->rollBack(); check((string) $e->getCode() === $sqlstate || str_starts_with((string)$e->getCode(), 'HY'), $name . ' [' . $e->getCode() . ']'); }
};
$tx = static function (callable $fn) use ($pdo): void { $pdo->beginTransaction(); try { $fn(); } finally { $pdo->rollBack(); } };
$opd1 = (int) $pdo->query('SELECT MIN(id) FROM opd')->fetchColumn();
$emp = static fn(string $nip, string $email) => $pdo->prepare('INSERT INTO employees(name,nip,position,opd_id,unit,grade,email) VALUES(?,?,?,?,?,?,?)')->execute(['X', $nip, 'P', $opd1, 'U', '', $email]);
$pdoRejects(static fn() => $pdo->prepare('INSERT INTO employees(name,nip,position,opd_id,unit,grade,email) VALUES(?,?,?,?,?,?,?)')->execute(['X', '100000000000000001', 'P', 999999, 'U', '', 'fk-opd@t.test']), 'employees.opd_id FK menolak OPD fiktif');
$pdoRejects(static fn() => $pdo->prepare('INSERT INTO opd(name,code) VALUES(?,?)')->execute(['Dinas Kesehatan', 'LAIN']), 'opd.name UNIQUE menolak nama ganda');
$e1 = (int) $pdo->query('SELECT MIN(id) FROM employees')->fetchColumn();
$e2 = (int) $pdo->query('SELECT MAX(id) FROM employees')->fetchColumn();
$existing = $pdo->query('SELECT nip,email FROM employees LIMIT 1')->fetch();
$pidDraft = null;

// employees UNIQUE
$pdoRejects(static fn() => $emp($existing['nip'], 'baru@x.test'), 'employees.nip UNIQUE menolak duplikat');
$pdoRejects(static fn() => $emp('999999999999999999', $existing['email']), 'employees.email UNIQUE menolak duplikat');
$pdoRejects(static fn() => $emp('999999999999999999', strtoupper($existing['email'])), 'employees.email UNIQUE case-insensitive (collation unicode_ci)');
// users FK + UNIQUE
$pdoRejects(static fn() => $pdo->prepare("INSERT INTO users(employee_id,password_hash) VALUES(?,?)")->execute([999999, 'x']), 'users.employee_id FK menolak pegawai fiktif');
$pdoRejects(static fn() => $pdo->prepare("INSERT INTO users(employee_id,password_hash) VALUES(?,?)")->execute([$e1, 'x']), 'users.employee_id UNIQUE menolak akun ganda');
$pdoRejects(static fn() => $pdo->prepare('DELETE FROM employees WHERE id=?')->execute([$e1]), 'employees RESTRICT: tidak bisa dihapus selama punya akun/penugasan');
// periods CHECK
$pdoRejects(static fn() => $pdo->prepare('INSERT INTO periods(name,start_date,end_date) VALUES(?,?,?)')->execute(['x', '2026-02-01', '2026-01-01']), 'periods CHECK end>=start');
$pdoRejects(static fn() => $pdo->prepare("INSERT INTO periods(name,start_date,end_date,status) VALUES(?,?,?,'aktif')")->execute(['x', '2026-01-01', '2026-02-01']), 'periods.status ENUM menolak nilai asing', '01000');
// assignments UNIQUE / CHECK / FK
$tx(static function () use ($pdo, $e1, $e2, $pdoRejects) {
    $pdo->prepare('INSERT INTO periods(name,start_date,end_date) VALUES(?,?,?)')->execute(['QA', '2026-01-01', '2026-12-31']);
    $pid = (int) $pdo->lastInsertId();
    $ins = $pdo->prepare('INSERT INTO assignments(period_id,subject_id,rater_id,rater_role) VALUES(?,?,?,?)');
    $ins->execute([$pid, $e1, $e2, 'rekan']); $aid = (int) $pdo->lastInsertId();
    try { $ins->execute([$pid, $e1, $e2, 'atasan']); check(false, 'assignments UNIQUE(period,subject,rater) menolak duplikat'); } catch (PDOException $e) { check($e->getCode() === '23000', 'assignments UNIQUE(period,subject,rater) menolak duplikat walau peran beda'); }
    try { $ins->execute([$pid, $e1, $e1, 'rekan']); check(false, 'assignments CHECK subject<>rater'); } catch (PDOException $e) { check(true, 'assignments CHECK subject<>rater menolak menilai diri sendiri [' . $e->getCode() . ']'); }
    try { $ins->execute([$pid, 999999, $e1, 'rekan']); check(false, 'assignments FK subject'); } catch (PDOException $e) { check($e->getCode() === '23000', 'assignments FK subject_id menolak pegawai fiktif'); }
    $ans = $pdo->prepare('INSERT INTO answers(assignment_id,indicator_id,score) VALUES(?,?,?)');
    foreach ([0, 6, -1] as $bad) { try { $ans->execute([$aid, 1, $bad]); check(false, "answers CHECK score=$bad"); } catch (PDOException $e) { check(true, "answers CHECK menolak score=$bad [" . $e->getCode() . ']'); } }
    try { $ans->execute([$aid, 99, 3]); check(false, 'answers FK indicator'); } catch (PDOException $e) { check($e->getCode() === '23000', 'answers FK indicator_id menolak indikator 99'); }
    $ans->execute([$aid, 1, 3]);
    try { $ans->execute([$aid, 1, 4]); check(false, 'answers PK'); } catch (PDOException $e) { check($e->getCode() === '23000', 'answers PK(assignment,indicator) menolak jawaban ganda (menutup kasus kunci "1"/"01")'); }
    // cascade
    $pdo->prepare('DELETE FROM assignments WHERE id=?')->execute([$aid]);
    check((int) $pdo->query("SELECT COUNT(*) FROM answers WHERE assignment_id=$aid")->fetchColumn() === 0, 'answers ON DELETE CASCADE saat penugasan dihapus');
    // period RESTRICT
    $ins->execute([$pid, $e1, $e2, 'rekan']);
    try { $pdo->prepare('DELETE FROM periods WHERE id=?')->execute([$pid]); check(false, 'periods RESTRICT'); } catch (PDOException $e) { check($e->getCode() === '23000', 'periods RESTRICT: tidak bisa dihapus selama ada penugasan'); }
});
// utf8mb4 round trip + panjang kolom
$tx(static function () use ($pdo, $e1) {
    $pdo->prepare('UPDATE employees SET name=? WHERE id=?')->execute(['Dr. Ñoño 🇮🇩 — “tes”', $e1]);
    check($pdo->query("SELECT name FROM employees WHERE id=$e1")->fetchColumn() === 'Dr. Ñoño 🇮🇩 — “tes”', 'utf8mb4 round-trip emoji & tanda kutip pada employees.name');
    $aid = (int) $pdo->query('SELECT id FROM assignments LIMIT 1')->fetchColumn();
    $fb = str_repeat('é', 1000) . '🙂';
    $pdo->prepare('UPDATE assignments SET feedback=? WHERE id=?')->execute([$fb, $aid]);
    check($pdo->query("SELECT feedback FROM assignments WHERE id=$aid")->fetchColumn() === $fb, 'utf8mb4 round-trip feedback 1000 karakter multibyte');
    try { $pdo->prepare('UPDATE employees SET nip=? WHERE id=?')->execute([str_repeat('1', 19), $e1]); check(false, 'nip >18'); } catch (PDOException $e) { check(true, 'employees.nip VARCHAR(18) menolak 19 karakter (strict mode aktif) [' . $e->getCode() . ']'); }
});
// audit_logs FK actor RESTRICT + JSON valid
$pdoRejects(static fn() => $pdo->prepare("INSERT INTO audit_logs(actor_user_id,action,entity_type,metadata) VALUES(999999,'x','y','{}')")->execute(), 'audit_logs.actor_user_id FK menolak user fiktif');
$pdoRejects(static fn() => $pdo->prepare("INSERT INTO audit_logs(actor_user_id,action,entity_type,metadata) VALUES(NULL,'x','y','{bukan json')")->execute(), 'audit_logs.metadata JSON menolak JSON rusak', '22032');
// transaksi rollback benar-benar membatalkan
$before = (int) $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn();
$tx(static fn() => $pdo->exec("INSERT INTO employees(name,nip,position,opd_id,unit,grade,email) VALUES('t','111111111111111111','p',$opd1,'u','','t@t.test')"));
check((int) $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn() === $before, 'rollback transaksi tidak menyisakan baris');
// sql_mode strict?
$mode = (string) $pdo->query('SELECT @@sql_mode')->fetchColumn();
check(str_contains($mode, 'STRICT_TRANS_TABLES'), 'sql_mode STRICT_TRANS_TABLES aktif (' . $mode . ')');
// schema.sql idempoten (CREATE IF NOT EXISTS + INSERT IGNORE)
$pdo->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
check((int) $pdo->query('SELECT COUNT(*) FROM indicators')->fetchColumn() === 7, 'schema.sql idempoten: indikator tetap 7');
qa_summary();
