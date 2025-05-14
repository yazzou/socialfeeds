<?php

namespace Drupal\socialfeed\Services;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * The factory collector class for Instagram.
 *
 * @package Drupal\socialfeed
 */
class InstagramPostCollectorFactory {

  /**
   * The default Instagram application api key.
   *
   * @var string
   */
  protected $defaultApiKey;

  /**
   * The default Instagram application api secret.
   *
   * @var string
   */
  protected $defaultApiSecret;

  /**
   * The default Instagram redirect URI.
   *
   * @var string
   */
  protected $defaultRedirectUri;

  /**
   * The default Instagram application access token.
   *
   * @var string
   */
  protected $defaultAccessToken;

  /**
   * InstagramPostCollectorFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $config = $configFactory->get('socialfeed.instagram.settings');
    $this->defaultApiKey = $config->get('client_id');
    $this->defaultApiSecret = $config->get('api_secret');
    $this->defaultRedirectUri = $config->get('redirect_uri');
    $this->defaultAccessToken = $config->get('access_token');
  }

  /**
   * Creates a pre-configured instance.
   *
   * @param string $apiKey
   *   The API Key.
   * @param string $apiSecret
   *   The API Secret.
   * @param string $redirectUri
   *   The Redirect URI.
   * @param string $accessToken
   *   The Access Token.
   *
   * @return \Drupal\socialfeed\Services\InstagramPostCollector
   *   A fully configured instance from InstagramPostCollector.
   *
   * @throws \Exception
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function createInstance(string $apiKey, string $apiSecret, string $redirectUri, string $accessToken) {
    return new InstagramPostCollector(
      $apiKey ?: $this->defaultApiKey,
      $apiSecret ?: $this->defaultApiSecret,
      $redirectUri ?: $this->defaultRedirectUri,
      $accessToken ?: $this->defaultAccessToken
    );
  }

}
