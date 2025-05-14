<?php

namespace Drupal\socialfeed\Services;

use EspressoDev\InstagramBasicDisplay\InstagramBasicDisplay;

/**
 * The collector class for Instagram.
 *
 * @package Drupal\socialfeed
 */
class InstagramPostCollector {

  /**
   * Instagram's application api key.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * Instagram application api secret.
   *
   * @var string
   */
  protected $apiSecret;

  /**
   * Instagram application redirect Uri.
   *
   * @var string
   */
  protected $redirectUri;

  /**
   * Instagram's application access token.
   *
   * @var string
   */
  protected $accessToken;

  /**
   * Instagram client.
   *
   * @var \EspressoDev\InstagramBasicDisplay\InstagramBasicDisplay
   */
  protected $instagram;

  /**
   * InstagramPostCollector constructor.
   *
   * @param string $apiKey
   *   Instagram API key.
   * @param string $apiSecret
   *   Instagram API secret.
   * @param string $redirectUri
   *   Instagram Redirect URI.
   * @param string $accessToken
   *   Instagram Access token.
   * @param \EspressoDev\InstagramBasicDisplay\InstagramBasicDisplay|null $instagram
   *   Instagram client.
   *
   * @throws \Exception
   */
  public function __construct(string $apiKey, string $apiSecret, string $redirectUri, string $accessToken, InstagramBasicDisplay $instagram = NULL) {
    $this->apiKey = $apiKey;
    $this->apiSecret = $apiSecret;
    $this->redirectUri = $redirectUri;
    $this->accessToken = $accessToken;
    $this->instagram = $instagram;
    $this->setInstagramClient();
  }

  /**
   * Sets the Instagram client.
   *
   * @throws \Exception
   */
  public function setInstagramClient() {
    if (NULL === $this->instagram) {
      $this->instagram = new InstagramBasicDisplay([
        'appId' => $this->apiKey,
        'appSecret' => $this->apiSecret,
        'redirectUri' => $this->redirectUri,
      ]);
      $this->instagram->setAccessToken($this->accessToken);
    }
  }

  /**
   * Retrieves user's posts.
   *
   * @param int $numPosts
   *   Number of posts to get.
   * @param string $user_id
   *   The user id from whom to get media. Defaults to the user that the access
   *   token was created for.
   *
   * @return array
   *   An array of Instagram posts.
   */
  public function getPosts($numPosts, $user_id = 'me') {
    $posts = [];
    $response = $this->instagram->getUserMedia($user_id, $numPosts);
    if (isset($response->data)) {
      $posts = array_map(function ($post) {
        return [
          'raw' => $post,
          'media_url' => $post->media_url,
          'type' => $post->media_type,
          'children' => ($post->children ?? NULL),
        ];
      }, $response->data);
    }
    return $posts;
  }

}
