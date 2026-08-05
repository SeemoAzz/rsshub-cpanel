#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

command -v node >/dev/null || { echo "Node.js 22+ requis"; exit 1; }
command -v git >/dev/null || { echo "Git requis"; exit 1; }

# --ignore-scripts : évite le conflit avec l'environnement virtuel cPanel (nodevenv)
npm install --ignore-scripts --prefix "$ROOT"
node "$ROOT/scripts/prepare.mjs"

if [[ ! -f .env ]]; then
  cp .env.example .env
  echo ""
  echo "Fichier .env créé. Ajoutez TWITTER_AUTH_TOKEN avant le déploiement."
fi

echo ""
echo "Déploiement : uploadez ce dossier sur cPanel (voir README.md)"
