<?php

namespace Drupal\socialfeed\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use EspressoDev\InstagramBasicDisplay\InstagramBasicDisplay;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for socialfeed routes.
 *
 * @package Drupal\socialfeed\Controller
 */
class InstagramAuthController extends ControllerBase {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * InstagramAuthController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Request $request) {
    $this->configFactory = $config_factory;
    $this->currentRequest = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Get an Instagram access token.
   */
  public function accessToken() {
    $code = $this->currentRequest->query->get('code');
    $message = 'Something went wrong. The access token could not be created.';
    $token = '';

    if ($code) {
      $config = $this->configFactory->getEditable('socialfeed.instagram.settings');

      $instagram = new InstagramBasicDisplay([
        'appId' => $config->get('client_id'),
        'appSecret' => $config->get('app_secret'),
        'redirectUri' => Url::fromRoute('socialfeed.instagram_auth', [], ['absolute' => TRUE])->toString(),
      ]);

      // Get the short-lived access token (valid for 1 hour)
      $token = $instagram->getOAuthToken($code, TRUE);

      // Exchange this token for a long lived token (valid for 60 days)
      if ($token) {
        $token = $instagram->getLongLivedToken($token, TRUE);
        $config->set('access_token', $token);
        $config->set('access_token_date', time());
        $config->save();

        $message = 'Your Access Token has been generated and saved.';
      }
    }

    return [
      '#markup' => $this->t('@message @token', [
        '@message' => $message,
        '@token' => $token,
      ]),
    ];
  }

}
