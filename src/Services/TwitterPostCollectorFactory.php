<?php

namespace Drupal\socialfeed\Services;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * The factory collector class for Twitter.
 *
 * @package Drupal\socialfeed\Services
 */
class TwitterPostCollectorFactory {

  /**
   * The default consumer key.
   *
   * @var string
   */
  protected $defaultConsumerKey;

  /**
   * The default consumer secret.
   *
   * @var string
   */
  protected $defaultConsumerSecret;

  /**
   * The default access token.
   *
   * @var string
   */
  protected $defaultAccessToken;

  /**
   * The default access token secret.
   *
   * @var string
   */
  protected $defaultAccessTokenSecret;

  /**
   * TwitterPostCollectorFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $config = $configFactory->get('socialfeed.twitter.settings');
    $this->defaultConsumerKey = $config->get('consumer_key');
    $this->defaultConsumerSecret = $config->get('consumer_secret');
    $this->defaultAccessToken = $config->get('access_token');
    $this->defaultAccessTokenSecret = $config->get('access_token_secret');
  }

  /**
   * Creates a pre-configured instance.
   *
   * @param string $consumerKey
   *   The consumer key.
   * @param string $consumerSecret
   *   The consumer secret.
   * @param string $accessToken
   *   The access token.
   * @param string $accessTokenSecret
   *   The access token secret.
   *
   * @return \Drupal\socialfeed\Services\TwitterPostCollector
   *   A fully configured instance from TwitterPostCollector.
   *
   * @throws \Exception
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function createInstance(string $consumerKey, string $consumerSecret, string $accessToken, string $accessTokenSecret) {
    return new TwitterPostCollector(
      $consumerKey ?: $this->defaultConsumerKey,
      $consumerSecret ?: $this->defaultConsumerSecret,
      $accessToken ?: $this->defaultAccessToken,
      $accessTokenSecret ?: $this->defaultAccessTokenSecret
    );
  }

}
