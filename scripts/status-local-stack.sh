#!/usr/bin/env bash
set -euo pipefail

BACKEND_PORT="${BACKEND_PORT:-8000}"
AI_PORT="${AI_PORT:-8001}"
FRONTEND_PORT="${FRONTEND_PORT:-3000}"

echo "Port status"
echo "-----------"
lsof -i tcp:${FRONTEND_PORT} -n -P | sed -n '1,2p' || true
lsof -i tcp:${BACKEND_PORT} -n -P | sed -n '1,2p' || true
lsof -i tcp:${AI_PORT} -n -P | sed -n '1,2p' || true

echo ""
echo "Health checks"
echo "-------------"
echo "Frontend: $(curl -sS -I "http://127.0.0.1:${FRONTEND_PORT}" | head -n 1 || echo down)"
echo "Backend:  $(curl -sS "http://127.0.0.1:${BACKEND_PORT}/api/health" || echo down)"
echo "AI:       $(curl -sS "http://127.0.0.1:${AI_PORT}/health" || echo down)"
