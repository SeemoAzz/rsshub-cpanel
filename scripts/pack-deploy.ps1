# Crée deploy.zip pour upload cPanel (Windows)
$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$zipPath = Join-Path $root "deploy.zip"

if (-not (Test-Path (Join-Path $root "RSSHub\dist\index.mjs"))) {
    Write-Host "RSSHub non buildé. Lancez d'abord : .\scripts\prepare.ps1" -ForegroundColor Red
    exit 1
}

if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

$items = @(
    "app.js",
    "package.json",
    "package-lock.json",
    ".env.example",
    "RSSHub\dist",
    "RSSHub\node_modules",
    "RSSHub\assets",
    "RSSHub\public",
    "RSSHub\package.json"
)

$existing = $items | Where-Object { Test-Path (Join-Path $root $_) }

Compress-Archive -Path ($existing | ForEach-Object { Join-Path $root $_ }) -DestinationPath $zipPath -Force

Write-Host "Archive créée : $zipPath" -ForegroundColor Green
Write-Host "Uploadez et extrayez sur cPanel, puis créez .env avec TWITTER_AUTH_TOKEN" -ForegroundColor Cyan
