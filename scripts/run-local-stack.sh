#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RUNTIME_DIR="${ROOT_DIR}/.runtime"
mkdir -p "${RUNTIME_DIR}"

BACKEND_PORT="${BACKEND_PORT:-8000}"
AI_PORT="${AI_PORT:-8001}"
FRONTEND_PORT="${FRONTEND_PORT:-3000}"

BACKEND_LOG="${RUNTIME_DIR}/backend.log"
AI_LOG="${RUNTIME_DIR}/ai.log"
FRONTEND_LOG="${RUNTIME_DIR}/frontend.log"

BACKEND_PID_FILE="${RUNTIME_DIR}/backend.pid"
AI_PID_FILE="${RUNTIME_DIR}/ai.pid"
FRONTEND_PID_FILE="${RUNTIME_DIR}/frontend.pid"

kill_port() {
  local port="$1"
  local pids
  pids="$(lsof -ti tcp:${port} || true)"
  if [[ -n "${pids}" ]]; then
    echo "Releasing port ${port}: ${pids}"
    kill -9 ${pids} || true
  fi
}

start_backend() {
  echo "Starting Laravel backend on :${BACKEND_PORT}"
  cd "${ROOT_DIR}/backend"
  php artisan config:clear >/dev/null 2>&1 || true
  nohup php \
    -d display_errors=0 \
    -d display_startup_errors=0 \
    -d log_errors=1 \
    -d error_reporting=8191 \
    -S 127.0.0.1:"${BACKEND_PORT}" \
    -t public \
    public/index.php >"${BACKEND_LOG}" 2>&1 &
  echo $! >"${BACKEND_PID_FILE}"
}

start_ai() {
  echo "Starting FastAPI AI service on :${AI_PORT}"
  cd "${ROOT_DIR}/ai-service"

  if [[ -x ".venv311/bin/uvicorn" ]]; then
    UVICORN_BIN=".venv311/bin/uvicorn"
  elif [[ -x ".venv/bin/uvicorn" ]]; then
    UVICORN_BIN=".venv/bin/uvicorn"
  else
    echo "No uvicorn found in ai-service/.venv311 or .venv"
    echo "Run: cd ai-service && python3.11 -m venv .venv311 && .venv311/bin/pip install -r requirements.txt"
    exit 1
  fi

  nohup "${UVICORN_BIN}" main:app --host 127.0.0.1 --port "${AI_PORT}" >"${AI_LOG}" 2>&1 &
  echo $! >"${AI_PID_FILE}"
}

start_frontend() {
  echo "Starting Next.js frontend on :${FRONTEND_PORT}"
  cd "${ROOT_DIR}/frontend"

  if [[ ! -d "node_modules" ]]; then
    echo "frontend/node_modules missing. Run: cd frontend && npm install"
    exit 1
  fi

  NEXT_PUBLIC_API_URL="${NEXT_PUBLIC_API_URL:-http://127.0.0.1:${BACKEND_PORT}/api}" \
  NEXT_PUBLIC_AI_ENABLED="${NEXT_PUBLIC_AI_ENABLED:-true}" \
    nohup npm run dev -- --hostname 127.0.0.1 --port "${FRONTEND_PORT}" >"${FRONTEND_LOG}" 2>&1 &
  echo $! >"${FRONTEND_PID_FILE}"
}

health_check() {
  echo "Waiting for services..."
  sleep 3
  echo "Backend:  $(curl -sS "http://127.0.0.1:${BACKEND_PORT}/api/health" || echo 'down')"
  echo "AI:       $(curl -sS "http://127.0.0.1:${AI_PORT}/health" || echo 'down')"
  echo "Frontend: $(curl -sS -I "http://127.0.0.1:${FRONTEND_PORT}" | head -n 1 || echo 'down')"
}

kill_port "${BACKEND_PORT}"
kill_port "${AI_PORT}"
kill_port "${FRONTEND_PORT}"

start_backend
start_ai
start_frontend
health_check

echo ""
echo "Stack started."
echo "Frontend: http://127.0.0.1:${FRONTEND_PORT}"
echo "Backend:  http://127.0.0.1:${BACKEND_PORT}"
echo "AI:       http://127.0.0.1:${AI_PORT}/health"
echo "Logs:"
echo "  ${BACKEND_LOG}"
echo "  ${AI_LOG}"
echo "  ${FRONTEND_LOG}"
