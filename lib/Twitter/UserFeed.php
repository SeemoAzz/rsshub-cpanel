<?php

declare(strict_types=1);

require_once __DIR__ . '/Client.php';
require_once __DIR__ . '/../Rss.php';

final class TwitterUserFeed
{
    private TwitterClient $client;

    public function __construct(TwitterClient $client)
    {
        $this->client = $client;
    }

    public function handle(string $username, array $options = []): string
    {
        $count = max(1, min((int) ($options['count'] ?? 20), 100));
        $includeReplies = !empty($options['include_replies']);
        $includeRts = array_key_exists('include_rts', $options) ? (bool) $options['include_rts'] : true;

        $user = $this->client->getUserByScreenName($username);
        $tweets = $this->client->getUserTweets($user['id'], $count, $includeReplies);

        if (!$includeRts) {
            $tweets = array_values(array_filter(
                $tweets,
                static fn(array $tweet): bool => !str_starts_with($tweet['text'], 'RT @')
            ));
        }

        $image = $user['profile_image_url'] ?? '';
        if ($image !== '') {
            $image = (string) preg_replace('/_normal(\.(jpe?g|png|gif|webp))$/i', '$1', $image);
        }

        $items = [];
        foreach ($tweets as $tweet) {
            $description = $tweet['text'];
            foreach ($tweet['media'] as $mediaUrl) {
                $description .= "\n" . $mediaUrl;
            }

            $items[] = [
                'title' => $this->tweetTitle($tweet['text']),
                'description' => $description,
                'link' => $tweet['url'],
                'guid' => $tweet['url'],
                'pubDate' => $tweet['created_at'],
            ];
        }

        return Rss::channel([
            'title' => 'Twitter @' . $user['name'],
            'link' => 'https://x.com/' . $user['screen_name'],
            'description' => $user['description'] !== '' ? $user['description'] : ('Flux Twitter @' . $user['screen_name']),
            'image' => $image !== '' ? $image : null,
        ], $items);
    }

    public static function parseRouteParams(?string $routeParams): array
    {
        $options = [
            'count' => 20,
            'include_replies' => false,
            'include_rts' => true,
        ];

        if ($routeParams === null || $routeParams === '') {
            return $options;
        }

        foreach (explode('/', trim($routeParams, '/')) as $part) {
            if ($part === 'with_replies') {
                $options['include_replies'] = true;
            } elseif ($part === 'exclude_rts') {
                $options['include_rts'] = false;
            } elseif (preg_match('/^(\d+)$/', $part, $matches)) {
                $options['count'] = (int) $matches[1];
            }
        }

        return $options;
    }

    private function tweetTitle(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';
        if ($text === '') {
            return 'Tweet';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($text) > 120 ? mb_substr($text, 0, 117) . '...' : $text;
        }

        return strlen($text) > 120 ? substr($text, 0, 117) . '...' : $text;
    }
}
