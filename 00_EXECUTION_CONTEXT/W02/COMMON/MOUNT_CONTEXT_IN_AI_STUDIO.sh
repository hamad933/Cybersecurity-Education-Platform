#!/usr/bin/env bash
set -euo pipefail
BRANCH="context/cep-w02-aistudio-all-surfaces-r01"
CONTEXT_DIR="${CEP_W02_CONTEXT_DIR:-/app/.cep-w02-context}"
if [[ ! -d .git && ! -f .git ]]; then
  echo "Run this from the CEP repository worktree." >&2
  exit 2
fi
git fetch origin "$BRANCH"
if [[ -e "$CONTEXT_DIR" ]]; then
  echo "CONTEXT_DIR_ALREADY_EXISTS: $CONTEXT_DIR" >&2
  echo "No deletion was attempted. Choose another CEP_W02_CONTEXT_DIR or inspect the existing worktree." >&2
  exit 3
fi
git worktree add --detach "$CONTEXT_DIR" "origin/$BRANCH"
echo "CONTEXT_READY=$CONTEXT_DIR"
echo "LIBRARY=$CONTEXT_DIR/00_EXECUTION_CONTEXT/W02/LIBRARY_EDITOR"
echo "LEARN=$CONTEXT_DIR/00_EXECUTION_CONTEXT/W02/LEARN"
echo "VISUALIZE=$CONTEXT_DIR/00_EXECUTION_CONTEXT/W02/VISUALIZE"
echo "RQ=$CONTEXT_DIR/00_EXECUTION_CONTEXT/W02/RESEARCH_QUALITY"
