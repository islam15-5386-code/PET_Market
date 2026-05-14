#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RUNTIME_DIR="${ROOT_DIR}/.runtime"

stop_by_pid_file() {
  local name="$1"
  local file="$2"
  if [[ -f "${file}" ]]; then
    local pid
    pid="$(cat "${file}")"
    if kill -0 "${pid}" >/dev/null 2>&1; then
      echo "Stopping ${name} (PID ${pid})"
      kill "${pid}" || true
    fi
    rm -f "${file}"
  fi
}

stop_by_pid_file "backend" "${RUNTIME_DIR}/backend.pid"
stop_by_pid_file "ai-service" "${RUNTIME_DIR}/ai.pid"
stop_by_pid_file "frontend" "${RUNTIME_DIR}/frontend.pid"

echo "Stopped local stack (if running)."
