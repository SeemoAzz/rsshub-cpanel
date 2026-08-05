<?php

declare(strict_types=1);

final class Rss
{
    public static function channel(array $meta, array $items): string
    {
        $title = self::escape($meta['title'] ?? 'RSS Feed');
        $link = self::escape($meta['link'] ?? '');
        $description = self::escape($meta['description'] ?? '');
        $image = isset($meta['image']) ? self::escape($meta['image']) : null;
        $language = self::escape($meta['language'] ?? 'fr');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= "  <channel>\n";
        $xml .= "    <title>{$title}</title>\n";
        $xml .= "    <link>{$link}</link>\n";
        $xml .= "    <description>{$description}</description>\n";
        $xml .= "    <language>{$language}</language>\n";
        $xml .= '    <generator>RSSHub-PHP/cPanel</generator>' . "\n";
        $xml .= '    <lastBuildDate>' . gmdate('D, d M Y H:i:s') . " GMT</lastBuildDate>\n";

        if ($image) {
            $xml .= "    <image>\n";
            $xml .= "      <url>{$image}</url>\n";
            $xml .= "      <title>{$title}</title>\n";
            $xml .= "      <link>{$link}</link>\n";
            $xml .= "    </image>\n";
        }

        foreach ($items as $item) {
            $xml .= self::item($item);
        }

        $xml .= "  </channel>\n";
        $xml .= "</rss>\n";

        return $xml;
    }

    private static function item(array $item): string
    {
        $title = self::escape($item['title'] ?? '');
        $link = self::escape($item['link'] ?? '');
        $guid = self::escape($item['guid'] ?? $link);
        $description = self::escape($item['description'] ?? '');
        $pubDate = isset($item['pubDate']) ? self::formatDate($item['pubDate']) : null;

        $xml = "    <item>\n";
        $xml .= "      <title>{$title}</title>\n";
        $xml .= "      <description>{$description}</description>\n";
        $xml .= "      <link>{$link}</link>\n";
        $xml .= "      <guid isPermaLink=\"true\">{$guid}</guid>\n";
        if ($pubDate) {
            $xml .= "      <pubDate>{$pubDate}</pubDate>\n";
        }
        $xml .= "    </item>\n";

        return $xml;
    }

    private static function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function formatDate(string $value): string
    {
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return gmdate('D, d M Y H:i:s') . ' GMT';
        }
        return gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
    }
}
