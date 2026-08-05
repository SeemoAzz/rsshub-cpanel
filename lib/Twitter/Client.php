<?php

declare(strict_types=1);

require_once __DIR__ . '/../Cache.php';
require_once __DIR__ . '/../Http.php';

final class TwitterClient
{
    private const BEARER = 'Bearer AAAAAAAAAAAAAAAAAAAAANRILgAAAAAAnNwIzUejRCOuH5E6I8xnZz4puTs%3D1Zv7ttfk8LF81IUq16cHjhLTvJu4FA33AGWWjCpTnA';
    private const BASE_URL = 'https://x.com/i/api';

    private const FALLBACK_QUERY_IDS = [
        'UserTweets' => 'E3opETHurmVJflFsUBVuUQ',
        'UserByScreenName' => 'Yka-W8dz7RaEuQNkroPkYw',
        'UserTweetsAndReplies' => 'bt4TKuFz4T7Ckk-VvQVSow',
    ];

    private const FEATURES_USER = [
        'hidden_profile_subscriptions_enabled' => true,
        'rweb_tipjar_consumption_enabled' => true,
        'responsive_web_graphql_exclude_directive_enabled' => true,
        'verified_phone_label_enabled' => false,
        'subscriptions_verification_info_is_identity_verified_enabled' => true,
        'subscriptions_verification_info_verified_since_enabled' => true,
        'highlights_tweets_tab_ui_enabled' => true,
        'responsive_web_twitter_article_notes_tab_enabled' => true,
        'subscriptions_feature_can_gift_premium' => true,
        'creator_subscriptions_tweet_preview_api_enabled' => true,
        'responsive_web_graphql_skip_user_profile_image_extensions_enabled' => false,
        'responsive_web_graphql_timeline_navigation_enabled' => true,
    ];

    private const FEATURES_FEED = [
        'rweb_tipjar_consumption_enabled' => true,
        'responsive_web_graphql_exclude_directive_enabled' => true,
        'verified_phone_label_enabled' => false,
        'creator_subscriptions_tweet_preview_api_enabled' => true,
        'responsive_web_graphql_timeline_navigation_enabled' => true,
        'responsive_web_graphql_skip_user_profile_image_extensions_enabled' => false,
        'communities_web_enable_tweet_community_results_fetch' => true,
        'c9s_tweet_anatomy_moderator_badge_enabled' => true,
        'articles_preview_enabled' => true,
        'responsive_web_edit_tweet_api_enabled' => true,
        'graphql_is_translatable_rweb_tweet_is_translatable_enabled' => true,
        'view_counts_everywhere_api_enabled' => true,
        'longform_notetweets_consumption_enabled' => true,
        'responsive_web_twitter_article_tweet_consumption_enabled' => true,
        'tweet_awards_web_tipping_enabled' => false,
        'creator_subscriptions_quote_tweet_preview_enabled' => false,
        'freedom_of_speech_not_reach_fetch_enabled' => true,
        'standardized_nudges_misinfo' => true,
        'tweet_with_visibility_results_prefer_gql_limited_actions_policy_enabled' => true,
        'rweb_video_timestamps_enabled' => true,
        'longform_notetweets_rich_text_read_enabled' => true,
        'longform_notetweets_inline_media_enabled' => true,
        'responsive_web_enhance_cards_enabled' => false,
    ];

    private Cache $cache;
    private string $authToken;
    private ?string $ct0;

    public function __construct(Cache $cache, string $authToken, ?string $ct0 = null)
    {
        $this->cache = $cache;
        $this->authToken = trim($authToken);
        $this->ct0 = $ct0 !== null && $ct0 !== '' ? trim($ct0) : null;
    }

    public function getUserByScreenName(string $screenName): array
    {
        $variables = [
            'screen_name' => ltrim($screenName, '@'),
            'withSafetyModeUserFields' => true,
        ];

        $data = $this->graphql('UserByScreenName', $variables, self::FEATURES_USER);
        $result = $data['data']['user']['result'] ?? null;

        if (!$result || ($result['__typename'] ?? '') === 'UserUnavailable') {
            throw new RuntimeException('Utilisateur X introuvable: ' . $screenName);
        }

        $legacy = $result['legacy'] ?? [];

        return [
            'id' => $result['rest_id'] ?? '',
            'name' => $legacy['name'] ?? $screenName,
            'screen_name' => $legacy['screen_name'] ?? $screenName,
            'description' => $legacy['description'] ?? '',
            'profile_image_url' => $legacy['profile_image_url_https'] ?? ($legacy['profile_image_url'] ?? ''),
        ];
    }

    public function getUserTweets(string $userId, int $count = 20, bool $includeReplies = false): array
    {
        $operation = $includeReplies ? 'UserTweetsAndReplies' : 'UserTweets';
        $variables = [
            'userId' => $userId,
            'count' => max(1, min($count, 100)),
            'includePromotedContent' => false,
            'withQuickPromoteEligibilityTweetFields' => true,
            'withVoice' => true,
            'withV2Timeline' => true,
        ];

        $data = $this->graphql($operation, $variables, self::FEATURES_FEED, $userId);
        return $this->extractTweets($data, $userId);
    }

    private function graphql(string $operation, array $variables, array $features, ?string $userId = null): array
    {
        $queryId = $this->resolveQueryId($operation);
        $params = http_build_query([
            'variables' => json_encode($variables, JSON_UNESCAPED_UNICODE),
            'features' => json_encode($features, JSON_UNESCAPED_UNICODE),
        ]);

        $url = self::BASE_URL . '/graphql/' . $queryId . '/' . $operation . '?' . $params;
        $session = $this->sessionCookies();

        $response = Http::get($url, [
            'Accept' => '*/*',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Authorization' => self::BEARER,
            'Content-Type' => 'application/json',
            'Referer' => 'https://x.com/',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'x-csrf-token' => $session['ct0'],
            'x-twitter-active-user' => 'yes',
            'x-twitter-auth-type' => 'OAuth2Session',
            'x-twitter-client-language' => 'en',
            'Cookie' => 'auth_token=' . $this->authToken . '; ct0=' . $session['ct0'],
        ]);

        if ($response['status'] === 429) {
            throw new RuntimeException('Limite de requêtes X atteinte. Réessayez plus tard.');
        }

        if ($response['status'] === 401 || $response['status'] === 403) {
            throw new RuntimeException('Token X invalide ou expiré. Mettez à jour TWITTER_AUTH_TOKEN et TWITTER_CT0.');
        }

        if ($response['status'] >= 400) {
            throw new RuntimeException('Erreur API X (' . $response['status'] . ').');
        }

        $json = json_decode($response['body'], true);
        if (!is_array($json)) {
            throw new RuntimeException('Réponse X invalide.');
        }

        if (isset($json['errors'][0]['message'])) {
            throw new RuntimeException('Erreur X: ' . $json['errors'][0]['message']);
        }

        return $json;
    }

    private function sessionCookies(): array
    {
        $cacheKey = 'twitter:session:' . hash('sha256', $this->authToken);
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            $parsed = json_decode($cached, true);
            if (is_array($parsed) && !empty($parsed['ct0'])) {
                return $parsed;
            }
        }

        if ($this->ct0) {
            $session = ['ct0' => $this->ct0];
            $this->cache->set($cacheKey, json_encode($session), 3600);
            return $session;
        }

        $response = Http::get('https://x.com/', [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'Cookie' => 'auth_token=' . $this->authToken,
        ]);

        $cookies = Http::parseSetCookies($response['headers']['set-cookie'] ?? []);
        if (empty($cookies['ct0'])) {
            throw new RuntimeException('Impossible d\'obtenir le cookie ct0. Ajoutez TWITTER_CT0 dans config.php.');
        }

        $session = ['ct0' => $cookies['ct0']];
        $this->cache->set($cacheKey, json_encode($session), 3600);
        return $session;
    }

    private function resolveQueryId(string $operation): string
    {
        $cacheKey = 'twitter:query-ids';
        $cached = $this->cache->get($cacheKey);
        $ids = self::FALLBACK_QUERY_IDS;

        if ($cached) {
            $parsed = json_decode($cached, true);
            if (is_array($parsed)) {
                $ids = array_merge($ids, $parsed);
            }
        } elseif ($fresh = $this->fetchQueryIds()) {
            $ids = array_merge($ids, $fresh);
            $this->cache->set($cacheKey, json_encode($fresh), 86400);
        }

        if (empty($ids[$operation])) {
            throw new RuntimeException('Query ID X manquant pour ' . $operation);
        }

        return $ids[$operation];
    }

    private function fetchQueryIds(): array
    {
        try {
            $response = Http::get('https://x.com/', [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            ]);

            if (!preg_match('/\/client-web\/main\.([a-z0-9]+)\./', $response['body'], $matches)) {
                return [];
            }

            $mainUrl = 'https://abs.twimg.com/responsive-web/client-web/main.' . $matches[1] . '.js';
            $mainResponse = Http::get($mainUrl, [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            ]);

            $ids = [];
            if (preg_match_all('/queryId:"([^"]+)".+?operationName:"([^"]+)"/s', $mainResponse['body'], $all, PREG_SET_ORDER)) {
                foreach ($all as $match) {
                    if (isset(self::FALLBACK_QUERY_IDS[$match[2]])) {
                        $ids[$match[2]] = $match[1];
                    }
                }
            }

            return $ids;
        } catch (Throwable) {
            return [];
        }
    }

    private function extractTweets(array $data, string $userId): array
    {
        $userResult = $data['data']['user']['result'] ?? null;
        if (!$userResult) {
            return [];
        }

        $timeline = $userResult['timeline_v2']['timeline']
            ?? $userResult['timeline']['timeline']
            ?? null;

        $instructions = $timeline['instructions'] ?? [];
        $entries = [];

        foreach ($instructions as $instruction) {
            if (($instruction['type'] ?? '') === 'TimelineAddEntries') {
                $entries = array_merge($entries, $instruction['entries'] ?? []);
            }
            if (($instruction['type'] ?? '') === 'TimelineAddToModule') {
                foreach ($instruction['moduleItems'] ?? [] as $moduleItem) {
                    $entries[] = $moduleItem;
                }
            }
        }

        $tweets = [];
        foreach ($entries as $entry) {
            $entryId = $entry['entryId'] ?? '';
            if ($entryId === '' || str_starts_with($entryId, 'cursor-') || str_starts_with($entryId, 'profile-conversation-')) {
                continue;
            }

            $content = $entry['content'] ?? $entry['item'] ?? [];
            $itemContent = $content['itemContent'] ?? $content['content'] ?? $content;
            $result = $itemContent['tweet_results']['result'] ?? $itemContent['tweetResult']['result'] ?? null;

            if (!$result) {
                continue;
            }

            if (isset($result['tweet'])) {
                $result = $result['tweet'];
            }

            $legacy = $result['legacy'] ?? null;
            if (!$legacy) {
                continue;
            }

            if (!empty($legacy['retweeted_status_result'])) {
                continue;
            }

            if (($legacy['user_id_str'] ?? '') !== $userId) {
                continue;
            }

            $screenName = $legacy['user']['screen_name'] ?? 'i/web/status';
            $tweetId = $result['rest_id'] ?? $legacy['id_str'] ?? '';
            $fullText = $legacy['full_text'] ?? '';

            if (isset($result['note_tweet']['note_tweet_results']['result']['text'])) {
                $fullText = $result['note_tweet']['note_tweet_results']['result']['text'];
            }

            $tweets[] = [
                'id' => $tweetId,
                'text' => $fullText,
                'created_at' => $legacy['created_at'] ?? '',
                'url' => 'https://x.com/' . $screenName . '/status/' . $tweetId,
                'media' => $this->extractMedia($legacy),
            ];
        }

        return $tweets;
    }

    private function extractMedia(array $legacy): array
    {
        $media = [];
        foreach ($legacy['entities']['media'] ?? [] as $item) {
            if (!empty($item['media_url_https'])) {
                $media[] = $item['media_url_https'];
            }
        }
        return $media;
    }
}
