#!/usr/bin/env bash
set -u

EVIDENCE_DIR="${1:-evidence/frontend}"
mkdir -p "$EVIDENCE_DIR"
status_file="$EVIDENCE_DIR/gate-status.tsv"
printf 'gate\tstatus\texit_code\n' > "$status_file"
failures=0

run_gate() {
  local gate="$1"
  shift
  local log="$EVIDENCE_DIR/${gate}.log"
  printf '::group::%s\n' "$gate"
  set +e
  "$@" >"$log" 2>&1
  local code=$?
  set -e
  cat "$log"
  printf '::endgroup::\n'
  if [[ $code -eq 0 ]]; then
    printf '%s\tPASS\t0\n' "$gate" >> "$status_file"
  else
    printf '%s\tFAIL\t%s\n' "$gate" "$code" >> "$status_file"
    failures=$((failures + 1))
  fi
}

set -e
run_gate npm-clean-install npm ci --ignore-scripts
run_gate javascript-vue-format npm run format:check
run_gate eslint npm run lint
run_gate vue-typescript-no-emit npm run typecheck
run_gate vitest bash -o pipefail -c "npm test -- --reporter=default --reporter=junit --outputFile.junit='$EVIDENCE_DIR/vitest-junit.xml'"
run_gate npm-dependency-audit bash -o pipefail -c "npm audit --audit-level=high --json > '$EVIDENCE_DIR/npm-audit.json'"
run_gate vite-production-build npm run build

node -e '
const fs = require("fs");
const lines = fs.readFileSync(process.argv[1], "utf8").trim().split(/\r?\n/);
const headers = lines.shift().split("\t");
const gates = lines.filter(Boolean).map(line => Object.fromEntries(line.split("\t").map((value, i) => [headers[i], value])));
const failed = gates.filter(g => g.status !== "PASS");
fs.writeFileSync(process.argv[2], JSON.stringify({schema:"cep.gate-summary.v1",suite:"core-frontend",status:failed.length ? "FAIL" : "PASS",gates}, null, 2) + "\n");
' "$status_file" "$EVIDENCE_DIR/gate-summary.json"

exit "$failures"
