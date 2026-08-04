#!/usr/bin/env bash
set -u

EVIDENCE_DIR="${1:-evidence/php}"
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

run_phpunit_suite() {
  local gate="$1"
  local suite="$2"
  local junit="$3"
  run_gate "$gate" vendor/bin/phpunit \
    --testsuite="$suite" \
    --fail-on-empty-test-suite \
    --colors=never \
    --log-junit "$junit"
}

verify_frontend_manifest() {
  local manifest="public/build/manifest.json"
  test -s "$manifest"
  printf 'path=%s\n' "$manifest"
  printf 'bytes=%s\n' "$(wc -c < "$manifest" | tr -d '[:space:]')"
  printf 'sha256=%s\n' "$(sha256sum "$manifest" | awk '{print $1}')"
}

composer_dependency_audit() {
  git config --global --add safe.directory "$PWD"
  composer audit --locked --format=json > "$EVIDENCE_DIR/composer-audit.json"
}

set -e
run_gate composer-manifest-lock-validation composer validate --strict --no-check-publish
run_gate php-format vendor/bin/pint --test
run_gate php-static-analysis bash -o pipefail -c "vendor/bin/phpstan analyse --memory-limit=1G --error-format=json > '$EVIDENCE_DIR/phpstan.json'"
run_gate frontend-manifest-contract verify_frontend_manifest
run_gate fresh-migration php artisan migrate:fresh --seed --force
run_phpunit_suite phpunit-unit Unit "$EVIDENCE_DIR/phpunit-unit.xml"
run_phpunit_suite phpunit-feature Feature "$EVIDENCE_DIR/phpunit-feature.xml"
run_phpunit_suite postgresql-integration Integration "$EVIDENCE_DIR/phpunit-integration.xml"
run_phpunit_suite architecture-tests Architecture "$EVIDENCE_DIR/phpunit-architecture.xml"
run_phpunit_suite repository-safety "Repository Safety" "$EVIDENCE_DIR/phpunit-repository-safety.xml"
run_gate composer-dependency-audit composer_dependency_audit
run_gate repository-fallback-secret-scan php scripts/secret_scan.php

run_gate phpunit-evidence-summary php -r '
$files = [
    "Unit" => $argv[1]."/phpunit-unit.xml",
    "Feature" => $argv[1]."/phpunit-feature.xml",
    "Integration" => $argv[1]."/phpunit-integration.xml",
    "Architecture" => $argv[1]."/phpunit-architecture.xml",
    "Repository Safety" => $argv[1]."/phpunit-repository-safety.xml",
];
$suites = [];
$invalid = false;
foreach ($files as $name => $path) {
    $row = ["suite" => $name, "junit" => basename($path), "present" => is_file($path)];
    if ($row["present"]) {
        $document = new DOMDocument();
        if (@$document->load($path)) {
            $root = $document->documentElement;
            $summary = $root;
            if ($root->tagName === "testsuites") {
                foreach ($root->childNodes as $child) {
                    if ($child instanceof DOMElement && $child->tagName === "testsuite") {
                        $summary = $child;
                        break;
                    }
                }
            }
            foreach (["tests", "assertions", "failures", "errors", "skipped"] as $attribute) {
                $row[$attribute] = (int) $summary->getAttribute($attribute);
            }
            $row["duration_seconds"] = (float) $summary->getAttribute("time");
            $row["non_zero_test_count"] = $row["tests"] > 0;
            $invalid = $invalid || ! $row["non_zero_test_count"];
        } else {
            $row["parse_error"] = true;
            $invalid = true;
        }
    } else {
        $invalid = true;
    }
    $suites[] = $row;
}
file_put_contents($argv[1]."/phpunit-suite-summary.json", json_encode([
    "schema" => "cep.phpunit-suite-summary.v1",
    "suites" => $suites,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
exit($invalid ? 1 : 0);
' "$EVIDENCE_DIR"

php -r '
$lines = file($argv[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$header = str_getcsv(array_shift($lines), "\t", "\"", "\\");
$gates = [];
foreach ($lines as $line) {
    $gates[] = array_combine($header, str_getcsv($line, "\t", "\"", "\\"));
}
$failed = array_values(array_filter($gates, fn ($g) => $g["status"] !== "PASS"));
echo json_encode(["schema"=>"cep.gate-summary.v1","suite"=>"core-php","status"=>$failed ? "FAIL" : "PASS","gates"=>$gates], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES), PHP_EOL;
' "$status_file" > "$EVIDENCE_DIR/gate-summary.json"

exit "$failures"
