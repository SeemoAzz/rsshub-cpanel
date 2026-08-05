<?php

declare(strict_types=1);

final class Http
{
    public static function get(string $url, array $headers = [], int $timeout = 30): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => self::formatHeaders($headers),
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Requête HTTP échouée: ' . $error);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $rawHeaders = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        $parsedHeaders = self::parseHeaders($rawHeaders);

        return [
            'status' => $status,
            'headers' => $parsedHeaders,
            'body' => $body,
        ];
    }

    private static function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $name => $value) {
            $formatted[] = $name . ': ' . $value;
        }
        return $formatted;
    }

    private static function parseHeaders(string $rawHeaders): array
    {
        $headers = [];
        foreach (explode("\r\n", $rawHeaders) as $line) {
            if ($line === '' || stripos($line, 'HTTP/') === 0) {
                continue;
            }
            $colon = strpos($line, ':');
            if ($colon === false) {
                continue;
            }
            $name = strtolower(trim(substr($line, 0, $colon)));
            $headers[$name][] = trim(substr($line, $colon + 1));
        }
        return $headers;
    }

    public static function parseSetCookies(array $headerGroups): array
    {
        $cookies = [];
        foreach ($headerGroups as $values) {
            foreach ($values as $value) {
                $pair = explode(';', $value, 2)[0];
                $eq = strpos($pair, '=');
                if ($eq === false) {
                    continue;
                }
                $name = trim(substr($pair, 0, $eq));
                $cookies[$name] = trim(substr($pair, $eq + 1));
            }
        }
        return $cookies;
    }
}
