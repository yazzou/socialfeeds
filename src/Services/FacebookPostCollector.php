<?php

namespace Drupal\socialfeed\Services;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * The collector class for Facebook.
 *
 * @package Drupal\socialfeed
 */
class FacebookPostCollector {

  /**
   * The Facebook graph domain.
   */
  const GRAPH_DOMAIN = 'graph.facebook.com';

  /**
   * The Graph API version used.
   */
  const GRAPH_API_VERSION = 'v15.0';

  /**
   * The field names to retrieve from Facebook.
   *
   * @var array
   */
  protected array $fields = [
    'created_time',
    'message',
    'permalink_url',
    'picture',
    'status_type',
  ];

  /**
   * The Facebook's App ID.
   *
   * @var string
   */
  protected string $appId;

  /**
   * The Facebook's App Secret.
   *
   * @var string
   */
  protected string $appSecret;

  /**
   * The Facebook User Token.
   *
   * @var string
   */
  protected string $userToken;

  /**
   * The Facebook Page Name.
   *
   * @var string
   */
  private string $pageName;

  /**
   * Constructs a new FacebookPostCollector object.
   *
   * @param string $appId
   *   The Facebook's App ID.
   * @param string $appSecret
   *   The Facebook's App Secret.
   * @param string|null $userToken
   *   The Facebook User Token.
   * @param string|null $pageName
   *   The Facebook Page Name.
   */
  public function __construct(string $appId, string $appSecret, string $userToken = NULL, string $pageName = NULL) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
    $this->userToken = $userToken;
    $this->pageName = $pageName;
  }

  /**
   * Fetches Facebook posts from a given feed.
   *
   * @param string $post_types
   *   The post types to filter for.
   * @param int $num_posts
   *   The number of posts to return.
   *
   * @return array
   *   An array of Facebook posts.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getPosts(string $post_types, int $num_posts = 10): array {
    $posts = [];
    $post_count = 0;
    $url = $this->getFacebookFeedUrl($num_posts);
    do {
      $client = \Drupal::httpClient();
      $response = $client->request('GET', $url);
      // Ensure not caught in an infinite loop if there's no next page.
      $url = NULL;
      if ($response->getStatusCode() == Response::HTTP_OK) {
        $data = json_decode($response->getBody(), TRUE);
        $posts = array_merge($this->extractFacebookFeedData($post_types, $data['data']), $posts);
        $post_count = count($posts);
        if ($post_count < $num_posts && isset($data['paging']['next'])) {
          $url = $data['paging']['next'];
        }
      }
    } while ($post_count < $num_posts && NULL != $url);
    return array_slice($posts, 0, $num_posts);
  }

  /**
   * Extracts information from the Facebook feed.
   *
   * @param string $post_types
   *   The type of posts to extract.
   * @param array $data
   *   An array of data to extract information from.
   *
   * @return array
   *   An array of posts.
   */
  protected function extractFacebookFeedData(string $post_types, array $data): array {
    $posts = array_map(function ($post) {
      return $post;
    }, $data);

    // Filtering needed.
    if ($post_types !== '1') {
      return array_filter($posts, function ($post) use ($post_types) {
        if (!empty($post['status_type'])) {
          return $post['status_type'] === $post_types;
        }
      });
    }
    return $posts;
  }

  /**
   * Generates the Facebook access token.
   *
   * @return string
   *   The access token.
   */
  protected function defaultAccessToken(): string {
    $config = \Drupal::service('config.factory')->getEditable('socialfeed.facebook.settings');
    $permanent_token = $config->get('page_permanent_token');
    if (empty($permanent_token)) {
      $args = [
        'user_token' => $this->userToken,
        'app_id' => $this->appId,
        'app_secret' => $this->appSecret,
        'page_name' => $this->pageName,
      ];
      $url = Url::fromUri('https://' . self::GRAPH_DOMAIN . '/' . self::GRAPH_API_VERSION)->toString();
      $client = \Drupal::httpClient();
      // Token.
      $request = $client->request('GET', $url . "/oauth/access_token?grant_type=fb_exchange_token&client_id={$args['app_id']}&client_secret={$args['app_secret']}&fb_exchange_token={$args['user_token']}");
      $request = json_decode($request->getBody()->getContents());
      $long_token = $request->access_token;
      // User ID.
      $request = $client->request('GET', $url . "/me?access_token=$long_token");
      $request = json_decode($request->getBody()->getContents());
      $account_id = $request->id;
      // Page ID.
      $request = $client->request('GET', $url . "/{$args['page_name']}?fields=id&access_token=$long_token");
      $request = json_decode($request->getBody()->getContents());
      $page_id = $request->id;
      $config->set('page_id', $page_id)->save();
      // Permanent Token.
      $request = $client->request('GET', $url . "/$account_id/accounts?access_token=$long_token");
      $request = json_decode($request->getBody()->getContents());
      foreach ($request->data as $response_data) {
        if ($response_data->id == $page_id) {
          $config->set('page_permanent_token', $response_data->access_token)->save();
          return $response_data->access_token;
        }
      }
    }
    return $permanent_token;
  }

  /**
   * Creates the Facebook feed URL.
   *
   * @param int $num_posts
   *   The number of posts to return.
   *
   * @return string
   *   The feed URL with the appended fields to retrieve.
   */
  protected function getFacebookFeedUrl(int $num_posts): string {
    // This is to ensure that Long-Lived access token is generated at the first
    // call.
    $this->defaultAccessToken();
    $url = Url::fromUri('https://' . self::GRAPH_DOMAIN)->toString();
    $config = \Drupal::service('config.factory')->get('socialfeed.facebook.settings');
    return $url . '/' . $config->get('page_id') . '/feed?access_token=' . $config->get('page_permanent_token') . '&fields=' . implode(',', $this->fields) . '&limit=' . $num_posts;
  }

}
