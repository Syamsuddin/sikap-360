<?php
declare(strict_types=1);
final class Database
{
 public static function connect(array $config): PDO
 {
  if (!preg_match('/^[a-zA-Z0-9_]+$/', $config['db_name'])) throw new RuntimeException('Invalid database name.');
  $offset = (new DateTimeImmutable('now', new DateTimeZone($config['timezone'])))->format('P');
  return new PDO('mysql:host=' . $config['db_host'] . ';port=' . $config['db_port'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4', $config['db_user'], $config['db_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_STRINGIFY_FETCHES => false, PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone='" . $offset . "'"]);
 }
}
