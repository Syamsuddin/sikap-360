#!/usr/bin/env bash
# Pola berisiko. [C]/[H]/[M]/[L] = severity awal; verifikasi sebelum dicatat sebagai temuan.
g(){ grep -rnE --include='*.php' --exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=storage --exclude-dir=tests --exclude-dir=.qa "$@" . 2>/dev/null | head -n 30; }
echo "## [C] SQL dari input mentah";      g '(mysqli_query|->query\(|->exec\(|->prepare\().*\$_(GET|POST|REQUEST|COOKIE)'
echo "## [H] raw SQL + variabel";         g '(DB::raw|whereRaw|selectRaw|orderByRaw|havingRaw|DB::(select|statement)\()[^;]*\$'
echo "## [H] SQL string berisi variabel";  g '(->(prepare|query|exec)|mysqli_query)\([^;]*"[^"]*(SELECT|INSERT|UPDATE|DELETE|WHERE|ORDER BY)[^"]*\$[A-Za-z_]'
echo "## [H] output tanpa escape";        g '\{!!'
echo "## [H] mass assignment";            g '(create|update|fill|forceFill)\(\s*\$request->(all|input)\(\)\s*\)'
echo "## [H] env() di luar config";       grep -rnE 'env\(' app routes 2>/dev/null | head -n 20
echo "## [C] hash sandi lemah";           g '(md5|sha1)\([^)]*(pass|sandi|pwd)'
echo "## [H] fungsi berbahaya";           g '\b(eval|unserialize|extract)\s*\(\s*\$'
echo "## [M] guarded kosong";             g 'guarded\s*=\s*\[\s*\]'
echo "## [L] debug tertinggal";           g '\b(dd|dump|var_dump|print_r)\('
echo "## [H] controller tulis tanpa otorisasi (tinjau)"
for f in $(grep -rlE 'function (update|destroy|edit|delete)\(' app/Http/Controllers 2>/dev/null); do
  grep -qE 'authorize|Gate::|->can\(|can:' "$f" || echo "$f"; done
