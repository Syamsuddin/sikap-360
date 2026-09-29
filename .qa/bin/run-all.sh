#!/usr/bin/env bash
# Regresi penuh SIKAP 360. Pakai: bash .qa/bin/run-all.sh [BASE_URL]   (server harus sudah jalan ke sikap360_test)
cd "$(dirname "$0")/../.."; export DB_DATABASE=sikap360_test QA_BASE="${1:-http://127.0.0.1:8765}"; rc=0
for t in tests/scoring.php tests/unit_scoring_edge.php tests/unit_period.php tests/integration_db.php; do out=$(php "$t" 2>&1); echo "$t: $(echo "$out" | tail -1)"; echo "$out" | grep -E '^FAIL' ; echo "$out" | grep -qE '^FAIL|gagal\.$' && echo "$out" | grep -qE ' [1-9][0-9]* gagal' && rc=1; done
for t in tests/api_invariants.php tests/functional_flows.php tests/structure_flows.php tests/opd_flows.php tests/cross_opd_flows.php tests/weights_flows.php; do bash .qa/bin/reseed.sh >/dev/null; out=$(php "$t" 2>&1); echo "$t: $(echo "$out" | tail -1)"; echo "$out" | grep -E '^FAIL'; echo "$out" | grep -qE ' [1-9][0-9]* gagal' && rc=1; done
bash .qa/bin/reseed.sh >/dev/null; e2e=$(FORCE_COLOR=0 npx playwright test --reporter=line 2>&1 | grep -E 'passed|failed' | tail -1); echo "e2e (chromium+hp-android): $e2e"; echo "$e2e" | grep -q failed && rc=1
exit $rc
