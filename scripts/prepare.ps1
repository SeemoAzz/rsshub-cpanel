# Prépare RSSHub pour le déploiement (Windows)
$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)

if (-not (Get-Command node -ErrorAction SilentlyContinue)) {
    Write-Host "Node.js 22+ requis : https://nodejs.org/" -ForegroundColor Red
    exit 1
}

if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Host "Git requis : https://git-scm.com/" -ForegroundColor Red
    exit 1
}

Set-Location $root
npm install
node scripts/prepare.mjs

$envExample = Join-Path $root ".env.example"
$envFile = Join-Path $root ".env"
if (-not (Test-Path $envFile)) {
    Copy-Item $envExample $envFile
    Write-Host ""
    Write-Host "Fichier .env créé. Ajoutez votre TWITTER_AUTH_TOKEN avant le déploiement." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Déploiement : uploadez ce dossier sur cPanel (voir README.md)" -ForegroundColor Green
