<?php

namespace Drupal\socialfeed\Services;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * The factory collector class for Facebook.
 *
 * @package Drupal\socialfeed
 */
class FacebookPostCollectorFactory {

  /**
   * The default Facebook App ID.
   *
   * @var string
   */
  protected $defaultAppId;

  /**
   * The default Facebook App Secret.
   *
   * @var string
   */
  protected $defaultAppSecret;

  /**
   * The default Facebook User Token.
   *
   * @var string
   */
  protected $defaultUserToken;

  /**
   * The default Facebook Page Name.
   *
   * @var string
   */
  protected $pageName;

  /**
   * FacebookPostCollector constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $config = $configFactory->get('socialfeed.facebook.settings');
    $this->defaultAppId = $config->get('app_id');
    $this->defaultAppSecret = $config->get('secret_key');
    $this->defaultUserToken = $config->get('user_token');
    $this->pageName = $config->get('page_name');
  }

  /**
   * Creates a pre-configured instance.
   *
   * @param string $appId
   *   The App ID.
   * @param string $appSecret
   *   The App Secret.
   * @param string $userToken
   *   The User Token.
   * @param string $pageName
   *   The Page Name.
   *
   * @return \Drupal\socialfeed\Services\FacebookPostCollector
   *   A fully configured instance from FacebookPostCollector.
   *
   * @throws \Exception
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function createInstance(string $appId, string $appSecret, string $userToken, string $pageName) {
    return new FacebookPostCollector(
      $appId ?: $this->defaultAppId,
      $appSecret ?: $this->defaultAppSecret,
      $userToken ?: $this->defaultUserToken,
      $pageName ?: $this->pageName
    );
  }

}
