<?php

namespace Drupal\socialfeed\Services;

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * The collector class for Twitter.
 *
 * @package Drupal\socialfeed\Services
 */
class TwitterPostCollector {

  /**
   * Twitter's consumer key.
   *
   * @var string
   */
  protected $consumerKey;

  /**
   * Twitter's consumer secret.
   *
   * @var string
   */
  protected $consumerSecret;

  /**
   * Twitter's access token.
   *
   * @var string
   */
  protected $accessToken;

  /**
   * Twitter's access token secret.
   *
   * @var string
   */
  protected $accessTokenSecret;

  /**
   * Twitter's OAuth client.
   *
   * @var \Abraham\TwitterOAuth\TwitterOAuth
   */
  protected $twitter;

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
   * @param \Abraham\TwitterOAuth\TwitterOAuth|null $twitter
   *   Twitter's OAuth Client.
   */
  public function __construct(string $consumerKey, string $consumerSecret, string $accessToken, string $accessTokenSecret, TwitterOAuth $twitter = NULL) {
    $this->consumerKey = $consumerKey;
    $this->consumerSecret = $consumerSecret;
    $this->accessToken = $accessToken;
    $this->accessTokenSecret = $accessTokenSecret;
    $this->twitter = $twitter;
    $this->setTwitterClient();
  }

  /**
   * Sets the Twitter client.
   */
  public function setTwitterClient() {
    if (NULL === $this->twitter) {
      $this->twitter = new TwitterOAuth(
        $this->consumerKey,
        $this->consumerSecret,
        $this->accessToken,
        $this->accessTokenSecret
      );
    }
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
    return $this->twitter->get('statuses/user_timeline', [
      'count' => $count,
      'tweet_mode' => 'extended',
    ]);
  }

}
