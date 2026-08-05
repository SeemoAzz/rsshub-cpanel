<?php

declare(strict_types=1);

/** @var array{auth_token:string, ct0:?string, cache:Cache, cache_ttl:int} $app */
$app = require __DIR__ . '/lib/bootstrap.php';

$path = $_SERVER['PATH_INFO'] ?? '/';
$path = '/' . trim($path, '/');
if ($path === '//') {
    $path = '/';
}

try {
    if ($path === '/') {
        header('Content-Type: text/html; charset=utf-8');
        echo renderHome();
        exit;
    }

    if (preg_match('#^/twitter/user/([^/]+)(?:/(.+))?$#', $path, $matches)) {
        $username = urldecode($matches[1]);
        $routeParams = isset($matches[2]) ? urldecode($matches[2]) : null;
        $options = TwitterUserFeed::parseRouteParams($routeParams);

        $cacheKey = 'feed:twitter:user:' . strtolower($username) . ':' . md5(json_encode($options));
        $cached = $app['cache']->get($cacheKey);

        if ($cached !== null) {
            header('Content-Type: application/rss+xml; charset=utf-8');
            header('Cache-Control: public, max-age=' . $app['cache_ttl']);
            echo $cached;
            exit;
        }

        $client = new TwitterClient($app['cache'], $app['auth_token'], $app['ct0']);
        $feed = new TwitterUserFeed($client);
        $xml = $feed->handle($username, $options);

        $app['cache']->set($cacheKey, $xml, $app['cache_ttl']);

        header('Content-Type: application/rss+xml; charset=utf-8');
        header('Cache-Control: public, max-age=' . $app['cache_ttl']);
        echo $xml;
        exit;
    }

    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Route introuvable: {$path}\n";
} catch (Throwable $e) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Erreur: ' . $e->getMessage() . "\n";
}

function renderHome(): string
{
    return <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>RSSHub PHP — cPanel</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; }
    code { background: #f4f4f4; padding: 0.1rem 0.35rem; border-radius: 4px; }
  </style>
</head>
<body>
  <h1>RSSHub PHP</h1>
  <p>Flux RSS X/Twitter compatible RSSHub, hébergé en PHP sur cPanel (sans Node.js).</p>
  <h2>Routes disponibles</h2>
  <ul>
    <li><code>/twitter/user/Reuters</code></li>
    <li><code>/twitter/user/Reuters/20</code> — 20 tweets</li>
    <li><code>/twitter/user/Reuters/with_replies</code></li>
    <li><code>/twitter/user/Reuters/exclude_rts</code></li>
  </ul>
</body>
</html>
HTML;
}
