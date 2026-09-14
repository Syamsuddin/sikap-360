#!/usr/bin/env bash
# Pakai: bash .qa/bin/smoke.sh https://staging.contoh.go.id  — /api/health sebaiknya memeriksa koneksi DB
set -e; BASE="${1:?URL}"
for p in / /login /api/health; do
  c=$(curl -s -o /dev/null -w "%{http_code}" "$BASE$p"); echo "$p -> $c"; [ "$c" = 200 ] || { echo "SMOKE GAGAL: $p"; exit 1; }
done
for p in /.env /.git/config /storage/logs/laravel.log; do
  c=$(curl -s -o /dev/null -w "%{http_code}" "$BASE$p"); if [ "$c" = 200 ]; then echo "BOCOR: $p"; exit 1; fi; done
echo "Smoke lulus"
