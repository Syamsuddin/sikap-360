<?php
// Cek skema MySQL (read-only). Env: DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD
$pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST') ?: '127.0.0.1',
    getenv('DB_PORT') ?: '3306', getenv('DB_DATABASE')), getenv('DB_USERNAME'), getenv('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$S = "table_schema = DATABASE()";
$checks = [
    '[H] tabel tanpa PRIMARY KEY' => "SELECT t.table_name FROM information_schema.tables t
        WHERE t.$S AND t.table_type='BASE TABLE' AND NOT EXISTS (SELECT 1 FROM information_schema.table_constraints c
        WHERE c.table_schema=t.table_schema AND c.table_name=t.table_name AND c.constraint_type='PRIMARY KEY')",
    '[M] kolom *_id tanpa FOREIGN KEY (abaikan polymorphic)' => "SELECT c.table_name, c.column_name FROM information_schema.columns c
        WHERE c.$S AND c.column_name LIKE '%\\_id' AND c.column_name NOT LIKE '%able\\_id'
        AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k WHERE k.table_schema=c.table_schema
        AND k.table_name=c.table_name AND k.column_name=c.column_name AND k.referenced_table_name IS NOT NULL)",
    '[H] kandidat kolom unik tanpa UNIQUE (tinjau)' => "SELECT c.table_name, c.column_name FROM information_schema.columns c
        WHERE c.$S AND c.column_name REGEXP '^(nip|nik|npwp|email|username|kode|nomor|no_[a-z_]+)$'
        AND NOT EXISTS (SELECT 1 FROM information_schema.statistics s WHERE s.table_schema=c.table_schema
        AND s.table_name=c.table_name AND s.column_name=c.column_name AND s.non_unique=0)",
    '[M] kolom teks bukan utf8mb4' => "SELECT table_name, column_name, character_set_name FROM information_schema.columns
        WHERE $S AND character_set_name IS NOT NULL AND character_set_name <> 'utf8mb4'",
    '[M] tabel bukan InnoDB (tanpa transaksi/FK)' => "SELECT table_name, engine FROM information_schema.tables
        WHERE $S AND table_type='BASE TABLE' AND engine <> 'InnoDB'",
];
foreach ($checks as $label => $sql) {
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_NUM);
    printf("%s: %d\n", $label, count($rows));
    foreach (array_slice($rows, 0, 15) as $r) echo '  ', implode('.', $r), "\n";
}
