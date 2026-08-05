<?php

declare(strict_types=1);

require_once __DIR__ . '/Cache.php';
require_once __DIR__ . '/Twitter/UserFeed.php';

$configFile = dirname(__DIR__) . '/config.php';
if (!is_readable($configFile)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Configuration manquante. Copiez config.php.example vers config.php.\n";
    exit;
}

$config = require $configFile;

$authToken = trim((string) ($config['TWITTER_AUTH_TOKEN'] ?? ''));
if ($authToken === '' || $authToken === 'votre_auth_token_ici') {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Configurez TWITTER_AUTH_TOKEN dans config.php.\n";
    exit;
}

$cacheDir = $config['CACHE_DIR'] ?? (dirname(__DIR__) . '/cache');
$cacheTtl = (int) ($config['CACHE_TTL'] ?? 300);

return [
    'auth_token' => $authToken,
    'ct0' => isset($config['TWITTER_CT0']) ? trim((string) $config['TWITTER_CT0']) : null,
    'cache' => new Cache($cacheDir),
    'cache_ttl' => max(60, $cacheTtl),
];
