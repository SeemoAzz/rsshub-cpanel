#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

command -v node >/dev/null || { echo "Node.js 22+ requis"; exit 1; }
command -v git >/dev/null || { echo "Git requis"; exit 1; }

npm install
node scripts/prepare.mjs

if [[ ! -f .env ]]; then
  cp .env.example .env
  echo ""
  echo "Fichier .env créé. Ajoutez TWITTER_AUTH_TOKEN avant le déploiement."
fi

echo ""
echo "Déploiement : uploadez ce dossier sur cPanel (voir README.md)"
