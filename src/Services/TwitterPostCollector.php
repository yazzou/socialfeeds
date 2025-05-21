<?php

namespace Drupal\socialfeed\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * The collector class for Twitter.
 *
 * @package Drupal\socialfeed\Services
 */
class TwitterPostCollector {

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
  protected string $userId;

  /**
   * TwitterPostCollector constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param string $bearerToken
   *   The bearer token.
   * @param string $userId
   *   The Twitter user ID.
   */
  public function __construct(ClientInterface $httpClient, string $bearerToken, string $userId) {
    $this->httpClient = $httpClient;
    $this->bearerToken = $bearerToken;
    $this->userId = $userId;
  }

  /**
   * Retrieves Tweets from the given accounts home page.
   *
   * @param int $count
   *   The number of posts to return.
   *
   * @return array
   *   An array of posts.
   */
  public function getPosts($count) {
    $endpoint_url = sprintf('https://api.twitter.com/2/users/%s/tweets', $this->userId);
    $query_params = [
      'max_results' => (int) $count,
      'tweet.fields' => 'created_at,public_metrics,text',
      'expansions' => 'author_id,attachments.media_keys',
      'user.fields' => 'name,username,profile_image_url',
      'media.fields' => 'preview_image_url,type,url',
    ];

    try {
      $response = $this->httpClient->request('GET', $endpoint_url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $this->bearerToken,
          'Accept'        => 'application/json',
        ],
        'query' => $query_params,
      ]);
      $body = $response->getBody()->getContents();
      $decoded_response = json_decode($body, TRUE);
      \Drupal::logger('socialfeed')->info('Successfully fetched @count tweets for user @user_id.', ['@count' => count($decoded_response['data'] ?? []), '@user_id' => $this->userId]);
      return $decoded_response;
    } catch (RequestException $e) {
      // Log the error or handle it as per application requirements.
      \Drupal::logger('socialfeed')->error('Twitter API request failed for user @user_id: @message. Response: @response', ['@user_id' => $this->userId, '@message' => $e->getMessage(), '@response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'N/A']);
      return [];
    }
  }

}
