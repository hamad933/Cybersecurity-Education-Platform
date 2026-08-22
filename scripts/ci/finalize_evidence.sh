#!/usr/bin/env bash
set -euo pipefail

EVIDENCE_DIR="${1:?evidence directory is required}"
mkdir -p "$EVIDENCE_DIR"

forbidden_path_regex='(^|/)(\.env($|\.)|vendor|node_modules|source-vault|review-packets|browser-profiles|database-volumes|docker-data)(/|$)|\.(dump|backup|bak|sqlite|sqlite3|db|zip)$'
if find "$EVIDENCE_DIR" -type f -print | grep -Eiq "$forbidden_path_regex"; then
  echo "Forbidden evidence path detected." >&2
  exit 1
fi

forbidden_content_regex='(APP_KEY|DB_PASSWORD|TASK010_BROWSER_PASSWORD)[[:space:]]*=|(^|[[:space:]])Authorization:[[:space:]]|Set-Cookie:|BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY'
if grep -RIlE "$forbidden_content_regex" "$EVIDENCE_DIR" --exclude='SHA256SUMS.txt' --exclude='ARTIFACT_MANIFEST.json' | grep -q .; then
  echo "Potential secret-bearing evidence content detected." >&2
  exit 1
fi

manifest="$EVIDENCE_DIR/ARTIFACT_MANIFEST.json"
checksums="$EVIDENCE_DIR/SHA256SUMS.txt"
rm -f "$manifest" "$checksums"

mapfile -d '' files < <(find "$EVIDENCE_DIR" -type f ! -name 'SHA256SUMS.txt' ! -name 'ARTIFACT_MANIFEST.json' -print0 | sort -z)
{
  printf '{\n'
  printf '  "schema": "cep.github-evidence-manifest.v1",\n'
  printf '  "repository": "%s",\n' "${GITHUB_REPOSITORY:-unknown}"
  printf '  "commit_sha": "%s",\n' "${GITHUB_SHA:-unknown}"
  printf '  "workflow_run_id": "%s",\n' "${GITHUB_RUN_ID:-unknown}"
  printf '  "workflow_run_attempt": "%s",\n' "${GITHUB_RUN_ATTEMPT:-unknown}"
  printf '  "generated_at_utc": "%s",\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
  printf '  "files": [\n'
  for i in "${!files[@]}"; do
    file="${files[$i]}"
    rel="${file#"$EVIDENCE_DIR"/}"
    size="$(wc -c < "$file" | tr -d ' ')"
    digest="$(sha256sum "$file" | awk '{print $1}')"
    comma=','
    if [[ "$i" -eq $((${#files[@]} - 1)) ]]; then comma=''; fi
    printf '    {"path":"%s","bytes":%s,"sha256":"%s"}%s\n' "$rel" "$size" "$digest" "$comma"
  done
  printf '  ]\n'
  printf '}\n'
} > "$manifest"

find "$EVIDENCE_DIR" -type f ! -name 'SHA256SUMS.txt' -print0 \
  | sort -z \
  | xargs -0 sha256sum \
  | sed "s#  $EVIDENCE_DIR/#  #" > "$checksums"
