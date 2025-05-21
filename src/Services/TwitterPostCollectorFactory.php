<?php

namespace Drupal\socialfeed\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Http\Client\HttpClientInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Psr\Log\LoggerInterface;

/**
 * The factory collector class for Twitter.
 *
 * @package Drupal\socialfeed\Services
 */
class TwitterPostCollectorFactory {

  /** @var \Drupal\Core\Config\ConfigFactoryInterface */
  protected $configFactory;

  /** @var string */
  protected $defaultConsumerKey;

  /** @var string */
  protected $defaultConsumerSecret;

  /** @var string */
  protected $defaultAccessToken;

  /** @var string */
  protected $defaultAccessTokenSecret;

  /** @var string|null */
  protected $defaultBearerToken;

  /** @var \Drupal\Core\Http\Client\HttpClientInterface */
  protected $httpClient;

  /** @var \Drupal\Core\Cache\CacheBackendInterface */
  protected $cache;

  /** @var \Psr\Log\LoggerInterface */
  protected $logger;

  /**
   * TwitterPostCollectorFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Http\Client\HttpClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    HttpClientInterface $http_client,
    CacheBackendInterface $cache,
    LoggerInterface $logger
  ) {
    $this->configFactory = $configFactory;
    $settings = $this->configFactory->get('socialfeed.twitter.settings');
    $this->defaultConsumerKey = $settings->get('consumer_key');
    $this->defaultConsumerSecret = $settings->get('consumer_secret');
    $this->defaultAccessToken = $settings->get('access_token');
    $this->defaultAccessTokenSecret = $settings->get('access_token_secret');
    $this->defaultBearerToken = $settings->get('bearer_token');
    $this->httpClient = $http_client;
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   * Creates a pre-configured instance of TwitterPostCollector.
   *
   * @param string $consumerKey
   *   The consumer key.
   * @param string $consumerSecret
   *   The consumer secret.
   * @param string $accessToken
   *   The access token.
   * @param string $accessTokenSecret
   *   The access token secret.
   * @param string|null $bearerToken
   *   The bearer token (optional).
   *
   * @return \Drupal\socialfeed\Services\TwitterPostCollector
   *   A fully configured instance of TwitterPostCollector.
   */
  public function createInstance(
    string $consumerKey,
    string $consumerSecret,
    string $accessToken,
    string $accessTokenSecret,
    string $bearerToken = NULL
  ) {
    return new TwitterPostCollector(
      $consumerKey ?: $this->defaultConsumerKey,
      $consumerSecret ?: $this->defaultConsumerSecret,
      $accessToken ?: $this->defaultAccessToken,
      $accessTokenSecret ?: $this->defaultAccessTokenSecret,
      $bearerToken ?: $this->defaultBearerToken,
      $this->httpClient,
      $this->cache, // Pass the cache service
      $this->logger, // Pass the logger service
      $this->configFactory // Pass the config factory
      // Optional Abraham\TwitterOAuth\TwitterOAuth client is handled internally by TwitterPostCollector
    );
  }
}
