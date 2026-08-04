#!/usr/bin/env zsh

set -euo pipefail

# Este script deve ser executado apenas em instâncias AWS EC2.
# Ele valida o metadata de EC2 antes de iniciar o Docker Compose.

if ! curl -fsS --max-time 2 http://169.254.169.254/latest/meta-data/instance-id >/dev/null 2>&1; then
  echo "ERRO: este script só pode ser executado em uma instância EC2."
  exit 1
fi

if ! command -v docker >/dev/null 2>&1; then
  echo "ERRO: docker não encontrado. Instale o Docker antes de executar este script."
  exit 1
fi

BASE_DIR=$(cd "$(dirname "${0}")" && pwd)
cd "${BASE_DIR}"

echo "Executando Docker Compose no EC2..."

docker compose up -d --build

echo "Docker Compose iniciado. A aplicação deve estar disponível em http://127.0.0.1:8001"
