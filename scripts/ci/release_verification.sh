#!/usr/bin/env bash
set -euo pipefail

EVIDENCE_DIR="${EVIDENCE_DIR:-evidence/release}"
RELEASE_ENV_FILE="${RELEASE_ENV_FILE:?RELEASE_ENV_FILE is required}"
BROWSER_PASSWORD="${BROWSER_PASSWORD:?BROWSER_PASSWORD is required}"
APP_URL="${APP_URL:-http://127.0.0.1:8081}"
mkdir -p "$EVIDENCE_DIR"
status_file="$EVIDENCE_DIR/gate-status.tsv"
printf 'gate\tstatus\texit_code\n' > "$status_file"
failures=0

compose() { docker compose --env-file "$RELEASE_ENV_FILE" -f compose.release.yaml "$@"; }

start_release_services() {
  local code=0
  compose up -d --wait --wait-timeout 120 postgres app || code=$?
  if [[ $code -ne 0 ]]; then
    return "$code"
  fi
  compose up -d --no-deps queue
}

run_gate() {
  local gate="$1"; shift
  local log="$EVIDENCE_DIR/${gate}.log"
  printf '::group::%s\n' "$gate"
  set +e
  "$@" >"$log" 2>&1
  local code=$?
  set -e
  sed -E 's/(APP_KEY|DB_PASSWORD|TASK010_BROWSER_PASSWORD|BROWSER_PASSWORD)=([^[:space:]]+)/\1=[REDACTED]/g' "$log"
  printf '::endgroup::\n'
  if [[ $code -eq 0 ]]; then
    printf '%s\tPASS\t0\n' "$gate" >> "$status_file"
  else
    printf '%s\tFAIL\t%s\n' "$gate" "$code" >> "$status_file"
    failures=$((failures + 1))
  fi
}

run_gate release-image-build compose build --pull app queue
run_gate release-services-start start_release_services
run_gate release-container-liveness bash -o pipefail -c '
  for i in $(seq 1 60); do
    services="$(docker compose --env-file "'"$RELEASE_ENV_FILE"'" -f compose.release.yaml ps --services --filter status=running)"
    if grep -qx app <<<"$services" && grep -qx postgres <<<"$services" && grep -qx queue <<<"$services"; then
      exit 0
    fi
    sleep 2
  done
  docker compose --env-file "'"$RELEASE_ENV_FILE"'" -f compose.release.yaml ps
  exit 1
'
run_gate fresh-release-migrations compose exec -T app php artisan migrate:fresh --seed --force
run_gate release-http-readiness bash -o pipefail -c '
  for i in $(seq 1 60); do
    curl --fail --silent --show-error "'"$APP_URL"'/health/live" > /dev/null && exit 0
    sleep 2
  done
  docker compose --env-file "'"$RELEASE_ENV_FILE"'" -f compose.release.yaml ps
  exit 1
'
run_gate synthetic-release-owner compose exec -T -e TASK010_BROWSER_PASSWORD="$BROWSER_PASSWORD" app php artisan platform:release-gate-owner
run_gate release-readiness bash -o pipefail -c 'docker compose --env-file "'"$RELEASE_ENV_FILE"'" -f compose.release.yaml exec -T app php artisan platform:release-check --json | tee "'"$EVIDENCE_DIR"'/release-health-result.json"'

queue_create_code='require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); $run=App\Modules\Platform\Processing\ProcessingRun::query()->create(["type"=>"ci_foundation_smoke","input_digest"=>hash("sha256","cep-ci-queue-smoke"),"idempotency_key"=>"ci:queue-smoke:".getenv("GITHUB_RUN_ID").":".getenv("GITHUB_RUN_ATTEMPT"),"status"=>"pending","attempt_count"=>0]); App\Modules\Platform\Queue\FoundationSmokeJob::dispatch((string)$run->id); echo (string)$run->id;'
set +e
QUEUE_RUN_ID="$(compose exec -T -e GITHUB_RUN_ID="${GITHUB_RUN_ID:-0}" -e GITHUB_RUN_ATTEMPT="${GITHUB_RUN_ATTEMPT:-0}" app php -r "$queue_create_code" 2>"$EVIDENCE_DIR/queue-create.stderr")"
queue_create_status=$?
set -e
if [[ $queue_create_status -ne 0 || -z "$QUEUE_RUN_ID" ]]; then
  printf 'queue-smoke\tFAIL\t%s\n' "$queue_create_status" >> "$status_file"
  failures=$((failures + 1))
else
  queue_status='pending'
  for _ in $(seq 1 30); do
    queue_status="$(compose exec -T app php -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo App\Modules\Platform\Processing\ProcessingRun::query()->findOrFail($argv[1])->status;' "$QUEUE_RUN_ID")"
    [[ "$queue_status" == 'completed' ]] && break
    sleep 1
  done
  printf '{"schema":"cep.queue-smoke.v1","processing_run_id":"%s","status":"%s"}\n' "$QUEUE_RUN_ID" "$queue_status" > "$EVIDENCE_DIR/queue-smoke-result.json"
  if [[ "$queue_status" == 'completed' ]]; then
    printf 'queue-smoke\tPASS\t0\n' >> "$status_file"
  else
    printf 'queue-smoke\tFAIL\t1\n' >> "$status_file"
    failures=$((failures + 1))
  fi
fi

package_code='require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); $owner=App\Modules\IdentityAccess\Models\OwnerAccount::query()->where("is_active",true)->firstOrFail(); $service=$app->make(App\Modules\Platform\Backup\BackupService::class); $created=$service->create((string)$owner->id); $path=storage_path("app/private/".$created["blob_key"]); $stream=fopen($path,"rb"); try{$staged=$service->stage($stream,(string)$owner->id);}finally{if(is_resource($stream))fclose($stream);} echo json_encode(["status"=>"PASS","export_package_id"=>(string)$created["package_id"],"staged_restore_id"=>(string)$staged->id,"archive_path"=>$path],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);'
set +e
package_output="$(compose exec -T app php -r "$package_code" 2>"$EVIDENCE_DIR/safe-package-check.stderr")"
package_status=$?
set -e
if [[ $package_status -eq 0 ]]; then
  printf '%s\n' "$package_output" | sed -E 's#"archive_path":"[^"]+"#"archive_path":"[REDACTED_RUNTIME_PATH]"#' > "$EVIDENCE_DIR/safe-import-export-result.json"
  printf 'safe-import-export\tPASS\t0\n' >> "$status_file"
else
  printf 'safe-import-export\tFAIL\t%s\n' "$package_status" >> "$status_file"
  failures=$((failures + 1))
fi

restore_log="$EVIDENCE_DIR/restore-drill.log"
set +e
{
  compose exec -T postgres createdb -U cyber_platform cyber_platform_ci_test
  compose exec -T postgres createdb -U cyber_platform cyber_platform_ci_restore_drill
  prepare_json="$(compose exec -T -e APP_ENV=testing -e APP_PROFILE=test -e DB_DATABASE=cyber_platform_ci_test -e TEST_DATABASE_ALLOWED_CONNECTIONS=pgsql -e TEST_DATABASE_ALLOWED_HOSTS=postgres app php artisan migrate:fresh --seed --force >/dev/null && compose exec -T -e APP_ENV=testing -e APP_PROFILE=test -e DB_DATABASE=cyber_platform_ci_test -e TEST_DATABASE_ALLOWED_CONNECTIONS=pgsql -e TEST_DATABASE_ALLOWED_HOSTS=postgres app php artisan platform:restore-drill-prepare)"
  archive_path="$(printf '%s' "$prepare_json" | php -r '$j=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR); echo $j["archive_path"];')"
  actor_id="$(printf '%s' "$prepare_json" | php -r '$j=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR); echo $j["actor_id"];')"
  compose exec -T -e APP_ENV=testing -e APP_PROFILE=test -e DB_DATABASE=cyber_platform_ci_restore_drill -e TEST_DATABASE_ALLOWED_CONNECTIONS=pgsql -e TEST_DATABASE_ALLOWED_HOSTS=postgres app php artisan migrate:fresh --force >/dev/null
  restore_json="$(compose exec -T -e APP_ENV=testing -e APP_PROFILE=test -e DB_DATABASE=cyber_platform_ci_restore_drill -e TEST_DATABASE_ALLOWED_CONNECTIONS=pgsql -e TEST_DATABASE_ALLOWED_HOSTS=postgres app php artisan platform:restore-apply "$archive_path" "$actor_id")"
  printf '%s\n' "$restore_json" > "$EVIDENCE_DIR/migration-restore-result.json"
} >"$restore_log" 2>&1
restore_status=$?
compose exec -T postgres dropdb --if-exists -U cyber_platform cyber_platform_ci_restore_drill >/dev/null 2>&1 || true
compose exec -T postgres dropdb --if-exists -U cyber_platform cyber_platform_ci_test >/dev/null 2>&1 || true
set -e
if [[ $restore_status -eq 0 ]]; then
  printf 'isolated-backup-restore-drill\tPASS\t0\n' >> "$status_file"
else
  printf 'isolated-backup-restore-drill\tFAIL\t%s\n' "$restore_status" >> "$status_file"
  failures=$((failures + 1))
fi

compose ps --format json > "$EVIDENCE_DIR/container-health.json" || true
compose logs --no-color --since 30m app queue postgres | sed -E 's/(APP_KEY|DB_PASSWORD|TASK010_BROWSER_PASSWORD|BROWSER_PASSWORD)=([^[:space:]]+)/\1=[REDACTED]/g; s/(Cookie|Set-Cookie|Authorization):[^[:space:]]+/\1:[REDACTED]/g' > "$EVIDENCE_DIR/container-sanitized.log" || true

php -r '$lines=file($argv[1],FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES); $header=str_getcsv(array_shift($lines),"\t"); $gates=[]; foreach($lines as $line){$gates[]=array_combine($header,str_getcsv($line,"\t"));} $failed=array_values(array_filter($gates,fn($g)=>$g["status"]!=="PASS")); echo json_encode(["schema"=>"cep.gate-summary.v1","suite"=>"release-verification","status"=>$failed?"FAIL":"PASS","gates"=>$gates],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),PHP_EOL;' "$status_file" > "$EVIDENCE_DIR/gate-summary.json"

exit "$failures"
