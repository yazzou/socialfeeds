<?php

namespace Drupal\socialfeed\Services;

use Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\Core\Http\Client\HttpClientInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The collector class for Twitter.
 *
 * Fetches tweets using Twitter API v2 (with Bearer Token) if configured,
 * otherwise falls back to API v1.1 (legacy credentials).
 * Implements caching to reduce API calls.
 */
class TwitterPostCollector {

  use StringTranslationTrait;

  /**
   * Twitter's consumer key (v1.1).
   *
   * @var string
   */
  protected $consumerKey;

  /**
   * Twitter's consumer secret (v1.1).
   *
   * @var string
   */
  protected $consumerSecret;

  /**
   * Twitter's access token (v1.1).
   *
   * @var string
   */
  protected $accessToken;

  /**
   * Twitter's access token secret (v1.1).
   *
   * @var string
   */
  protected $accessTokenSecret;

  /**
   * Twitter API v2 Bearer Token.
   *
   * @var string|null
   */
  protected $bearerToken;

  /**
   * The User ID for Twitter API v2 calls.
   *
   * @var string|null
   */
  protected $userId;

  /**
   * Cache duration in seconds.
   *
   * @var int
   */
  protected $cacheDurationSeconds;

  /**
   * Twitter's v1.1 OAuth client.
   *
   * @var \Abraham\TwitterOAuth\TwitterOAuth|null
   */
  protected $twitterV1Client;

  /**
   * Drupal's HTTP Client for API v2 calls.
   *
   * @var \Drupal\Core\Http\Client\HttpClientInterface
   */
  protected $httpClient;

  /**
   * Drupal's Cache service (default bin).
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * TwitterPostCollector constructor.
   *
   * @param string $consumerKey
   *   Twitter's consumer key.
   * @param string $consumerSecret
   *   Twitter's consumer secret.
   * @param string $accessToken
   *   Twitter's access token.
   * @param string $accessTokenSecret
   *   Twitter's access token secret.
   * @param string|null $bearerToken
   *   Twitter API v2 Bearer Token.
   * @param \Drupal\Core\Http\Client\HttpClientInterface $httpClient
   *   Drupal's HTTP Client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Drupal's Cache service (e.g., 'cache.default').
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory to load User ID and cache duration.
   * @param \Abraham\TwitterOAuth\TwitterOAuth|null $twitterV1Client
   *   (Optional) Twitter's v1.1 OAuth Client.
   */
  public function __construct(
    string $consumerKey,
    string $consumerSecret,
    string $accessToken,
    string $accessTokenSecret,
    ?string $bearerToken,
    HttpClientInterface $httpClient,
    CacheBackendInterface $cache,
    LoggerInterface $logger,
    ConfigFactoryInterface $configFactory,
    TwitterOAuth $twitterV1Client = NULL
  ) {
    $this->consumerKey = $consumerKey;
    $this->consumerSecret = $consumerSecret;
    $this->accessToken = $accessToken;
    $this->accessTokenSecret = $accessTokenSecret;
    $this->bearerToken = $bearerToken;
    $this->httpClient = $httpClient;
    $this->cache = $cache;
    $this->logger = $logger;
    $this->twitterV1Client = $twitterV1Client;

    $config = $configFactory->get('socialfeed.twitter.settings');
    // These will be added to settings form and schema in a later step.
    $this->userId = $config->get('user_id');
    $this->cacheDurationSeconds = (int) $config->get('cache_duration') * 60;

    if (!$this->bearerToken && !$this->twitterV1Client && $this->consumerKey && $this->consumerSecret && $this->accessToken && $this->accessTokenSecret) {
      $this->initializeV1Client();
    }
  }

  /**
   * Initializes the Twitter v1.1 API client if not already set.
   */
  protected function initializeV1Client() {
    if (NULL === $this->twitterV1Client) {
      try {
        $this->twitterV1Client = new TwitterOAuth(
          $this->consumerKey,
          $this->consumerSecret,
          $this->accessToken,
          $this->accessTokenSecret
        );
      } catch (\Exception $e) {
        $this->logger->error('Failed to initialize Twitter API v1.1 client: @message', ['@message' => $e->getMessage()]);
        $this->twitterV1Client = NULL; // Ensure it's null if failed.
      }
    }
  }

  /**
   * Retrieves Tweets.
   *
   * @param int $count
   *   The number of posts to return (max_results for API v2).
   *
   * @return array
   *   An array of tweets, or an empty array on failure/error.
   */
  public function getPosts(int $count) {
    $cacheId = 'socialfeed:twitter:' . ($this->bearerToken ? 'v2:' . $this->userId : 'v1') . ':' . $count;

    if ($cache = $this->cache->get($cacheId)) {
      return $cache->data;
    }

    $tweets = [];
    if ($this->bearerToken && $this->userId) {
      $tweets = $this->fetchPostsV2($count);
    }
    elseif ($this->consumerKey && $this->consumerSecret && $this->accessToken && $this->accessTokenSecret) {
      $this->initializeV1Client(); // Ensure v1 client is ready
      if ($this->twitterV1Client) {
        $tweets = $this->fetchPostsV1($count);
      } else {
         $this->logger->warning('Twitter API v1.1 client not available for fetching posts.');
      }
    }
    else {
      $this->logger->notice('Twitter API credentials are not fully configured.');
      return [];
    }

    if (!empty($tweets)) {
      $this->cache->set($cacheId, $tweets, time() + $this->cacheDurationSeconds);
    }

    return $tweets;
  }

  /**
   * Fetches Tweets using Twitter API v2.
   *
   * @param int $count
   *   The number of tweets to fetch (max_results).
   *
   * @return array
   *   An array of tweets or empty array on failure.
   */
  protected function fetchPostsV2(int $count) {
    $url = "https://api.twitter.com/2/users/{$this->userId}/tweets";
    // Parameters from the issue's curl command.
    $params = [
      'max_results' => $count > 0 && $count <=100 ? $count : 5, // API v2 min is 5, max is 100 for this endpoint.
      'tweet.fields' => 'created_at,public_metrics,text',
      'expansions' => 'author_id,attachments.media_keys',
      'user.fields' => 'name,username,profile_image_url',
      'media.fields' => 'preview_image_url,type,url',
    ];

    try {
      $response = $this->httpClient->request('GET', $url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $this->bearerToken,
          'Accept' => 'application/json',
        ],
        'query' => $params,
        // Prevent Guzzle from throwing exceptions on 4xx/5xx, we'll check status code.
        'http_errors' => FALSE,
      ]);

      $statusCode = $response->getStatusCode();
      $data = json_decode($response->getBody()->getContents(), TRUE);

      if ($statusCode == 200 && !empty($data['data'])) {
        // The structure of v2 is different. We might need to adapt this data
        // to match what the theme expects if it was based on v1.1 structure.
        // For now, returning the 'data' part.
        // This part might need more complex transformation based on actual theme requirements.
        return $this->mapV2ResponseToExpectedStructure($data);
      }
      else {
        $errorMessage = $data['title'] ?? 'Unknown error';
        $errorDetail = $data['detail'] ?? '';
        if ($statusCode === 429) {
             $this->logger->warning('Twitter API v2 rate limit exceeded. Error: @error, Detail: @detail', ['@error' => $errorMessage, '@detail' => $errorDetail]);
        } else {
            $this->logger->error('Twitter API v2 error: Status @status - @error. Detail: @detail. URL: @url', [
                '@status' => $statusCode,
                '@error' => $errorMessage,
                '@detail' => $errorDetail,
                '@url' => $url . '?' . http_build_query($params),
            ]);
        }
        return [];
      }
    }
    catch (\GuzzleHttp\Exception\RequestException $e) {
      $this->logger->error('Failed to fetch tweets using Twitter API v2: @message. URL: @url', [
          '@message' => $e->getMessage(),
          '@url' => $url . '?' . http_build_query($params),
        ]);
      return [];
    }
    catch (\Exception $e) {
        $this->logger->error('An unexpected error occurred while fetching tweets using Twitter API v2: @message. URL: @url', [
            '@message' => $e->getMessage(),
            '@url' => $url . '?' . http_build_query($params),
        ]);
        return [];
    }
  }

  /**
   * Maps API v2 response to a structure that might be more consistent
   * with what a v1.1-based theme might expect.
   * This is a placeholder and might need significant adjustment.
   *
   * @param array $v2Response
   *   The raw response from Twitter API v2.
   *
   * @return array
   *   The mapped tweets.
   */
  protected function mapV2ResponseToExpectedStructure(array $v2Response) {
    $tweets = $v2Response['data'] ?? [];
    $includes = $v2Response['includes'] ?? [];
    $users = [];
    $media = [];

    if (!empty($includes['users'])) {
      foreach ($includes['users'] as $user) {
        $users[$user['id']] = $user;
      }
    }
    if (!empty($includes['media'])) {
      foreach ($includes['media'] as $item) {
        $media[$item['media_key']] = $item;
      }
    }

    $mappedTweets = [];
    foreach ($tweets as $tweet) {
      $authorId = $tweet['author_id'] ?? NULL;
      $authorInfo = $users[$authorId] ?? ['name' => 'Unknown User', 'username' => 'unknown', 'profile_image_url' => ''];

      $entities = ['media' => []];
      if (!empty($tweet['attachments']['media_keys'])) {
        foreach ($tweet['attachments']['media_keys'] as $mediaKey) {
          if (isset($media[$mediaKey])) {
            $mediaItem = $media[$mediaKey];
            $entities['media'][] = [
              'media_url_https' => $mediaItem['url'] ?? ($mediaItem['preview_image_url'] ?? ''),
              'type' => $mediaItem['type'],
              // Add other fields if the theme expects them e.g. 'expanded_url'
            ];
          }
        }
      }
      
      // Basic mapping, assuming 'text' is 'full_text' from v1.1 extended mode
      // and 'created_at' format is compatible.
      $mappedTweets[] = [
        'id_str' => $tweet['id'],
        'full_text' => $tweet['text'],
        'text' => $tweet['text'], // For compatibility if theme uses 'text'
        'created_at' => $tweet['created_at'],
        'user' => [
          'id_str' => $authorId,
          'name' => $authorInfo['name'],
          'screen_name' => $authorInfo['username'],
          'profile_image_url_https' => $authorInfo['profile_image_url'],
        ],
        'public_metrics' => $tweet['public_metrics'] ?? [], // retweet_count, like_count etc. are here
        'entities' => $entities,
        // Add 'extended_entities' if your theme specifically uses it.
        // It would be similar to 'entities' but often with more detail or for specific types like video.
      ];
    }
    return $mappedTweets;
  }

  /**
   * Fetches Tweets using Twitter API v1.1.
   *
   * @param int $count
   *   The number of tweets to fetch.
   *
   * @return array
   *   An array of tweets or empty array on failure.
   */
  protected function fetchPostsV1(int $count) {
    if (!$this->twitterV1Client) {
      $this->logger->error('Twitter API v1.1 client is not initialized.');
      return [];
    }

    try {
      $this->twitterV1Client->setApiVersion('1.1'); // Ensure correct API version
      $response = $this->twitterV1Client->get('statuses/user_timeline', [
        'count' => $count,
        'tweet_mode' => 'extended', // Gets full text
      ]);

      if ($this->twitterV1Client->getLastHttpCode() == 200) {
        // API v1.1 returns tweets directly as an array of objects.
        // Convert to array for consistency if needed, though often objects are fine.
        return json_decode(json_encode($response), TRUE);
      }
      else {
        $errors = $this->twitterV1Client->getLastBody()->errors ?? [['message' => 'Unknown v1.1 API error']];
        $errorMessage = $errors[0]->message ?? 'Error message not available';
         if ($this->twitterV1Client->getLastHttpCode() === 429) {
             $this->logger->warning('Twitter API v1.1 rate limit exceeded. Error: @error', ['@error' => $errorMessage]);
        } else {
            $this->logger->error('Twitter API v1.1 error: Status @status - @error.', [
                '@status' => $this->twitterV1Client->getLastHttpCode(),
                '@error' => $errorMessage,
            ]);
        }
        return [];
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to fetch tweets using Twitter API v1.1: @message', ['@message' => $e->getMessage()]);
      return [];
    }
  }
}
