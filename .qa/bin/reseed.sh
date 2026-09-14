#!/usr/bin/env bash
# Reseed database uji sikap360_test dengan data demo (idempoten).
set -e; cd "$(dirname "$0")/../.."
export MYSQL_PWD="${DB_PASSWORD:-Admin@123}"
mysql -h127.0.0.1 -uroot -e "DROP DATABASE IF EXISTS sikap360_test; CREATE DATABASE sikap360_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
DB_DATABASE=sikap360_test php scripts/install.php --demo >/dev/null
echo "reseed ok"
