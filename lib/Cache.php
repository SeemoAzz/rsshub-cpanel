<?php

declare(strict_types=1);

final class Cache
{
    private string $dir;

    public function __construct(string $dir)
    {
        $this->dir = rtrim($dir, DIRECTORY_SEPARATOR);
        if (!is_dir($this->dir) && !mkdir($this->dir, 0755, true) && !is_dir($this->dir)) {
            throw new RuntimeException('Impossible de créer le dossier cache.');
        }
    }

    public function get(string $key): ?string
    {
        $path = $this->path($key);
        if (!is_readable($path)) {
            return null;
        }

        $payload = json_decode((string) file_get_contents($path), true);
        if (!is_array($payload) || !isset($payload['expires'], $payload['value'])) {
            return null;
        }

        if ($payload['expires'] !== 0 && $payload['expires'] < time()) {
            @unlink($path);
            return null;
        }

        return is_string($payload['value']) ? $payload['value'] : null;
    }

    public function set(string $key, string $value, int $ttlSeconds): void
    {
        $payload = [
            'expires' => $ttlSeconds > 0 ? time() + $ttlSeconds : 0,
            'value' => $value,
        ];

        file_put_contents(
            $this->path($key),
            json_encode($payload, JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    private function path(string $key): string
    {
        return $this->dir . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
    }
}
