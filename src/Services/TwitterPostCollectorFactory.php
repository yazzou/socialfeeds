<?php

namespace Drupal\socialfeed\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * The factory collector class for Twitter.
 *
 * @package Drupal\socialfeed\Services
 */
class TwitterPostCollectorFactory {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The bearer token.
   *
   * @var string
   */
  protected $bearerToken;

  /**
   * The Twitter user ID.
   *
   * @var string
   */
  protected $userId;

  /**
   * TwitterPostCollectorFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ClientInterface $httpClient) {
    $this->configFactory = $configFactory;
    $this->httpClient = $httpClient;
    $config = $this->configFactory->get('socialfeed.twitter.settings');
    $this->bearerToken = $config->get('bearer_token');
    $this->userId = $config->get('user_id');
  }

  /**
   * Creates a pre-configured instance.
   *
   * @return \Drupal\socialfeed\Services\TwitterPostCollector
   *   A fully configured instance from TwitterPostCollector.
   */
  public function createInstance() {
    return new TwitterPostCollector(
      $this->httpClient,
      $this->bearerToken,
      $this->userId
    );
  }

}
