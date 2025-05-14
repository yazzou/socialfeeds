<?php

/**
 * @file
 * Theme preprocessors.
 */

use Carbon\Carbon;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Preprocess socialfeed_facebook_post.
 *
 * @see socialfeed_theme()
 */
function template_preprocess_socialfeed_facebook_post(&$variables) {
  $facebook_settings = \Drupal::config('socialfeed.facebook.settings');
  $use_facebook_hashtag = $facebook_settings->get('hashtag');
  $should_display_time = $facebook_settings->get('time_stamp');
  $teaser_text = $facebook_settings->get('teaser_text');
  $post = &$variables['post'];
  if (isset($post['message'])) {
    $post['message'] = substr($post['message'], 0, $facebook_settings->get('trim_length'));
  }

  if (!empty($post['permalink_url'])) {
    $post['permalink_url'] = Link::fromTextAndUrl(t('@teaser_text', ['@teaser_text' => !empty($teaser_text) ? $teaser_text : $post['permalink_url']]), Url::fromUri($post['permalink_url'], ['attributes' => ['target' => '_blank']]))->toString();
  }

  if ($use_facebook_hashtag) {
    $post['message'] = preg_replace_callback(
      '/#(\\w+)/',
      function ($hash) {
        return Link::fromTextAndUrl($hash[0], Url::fromUri('//facebook.com/hashtag/' . $hash[1], ['attributes' => ['target' => '_blank']]))->toString();
      },
      !empty($post['message']) ? $post['message'] : ""
    );
  }
  if ($should_display_time) {
    $variables['should_display_time'] = $should_display_time;
    $formatted_date = new DateTime();
    $formatted_date->setTimestamp(strtotime($post['created_time']));
    $post['created_time'] = $formatted_date->format($facebook_settings->get('time_format'));
  }

  if (isset($post['message'])) {
    $post['message'] = ['#markup' => $post['message']];
  }
}

/**
 * Preprocess socialfeed_twitter_post.
 *
 * @see socialfeed_theme()
 */
function template_preprocess_socialfeed_twitter_post(&$variables) {
  if (empty($variables['post']) || !is_object($variables['post'])) {
    unset($variables['post']);
    return;
  }

  $twitter_settings = \Drupal::config('socialfeed.twitter.settings');
  $display_time = $twitter_settings->get('time_stamp');
  $display_date_twitter_style = $twitter_settings->get('time_ago');
  $twitter_hash_tag = $twitter_settings->get('hashtag');
  $time_format = $twitter_settings->get('time_format');
  $trim_length = $twitter_settings->get('trim_length');
  $teaser_text = $twitter_settings->get('teaser_text');

  $post = &$variables['post'];
  $post->username = $post->user->screen_name;
  $post->full_username = '//twitter.com/' . $post->username;
  $post->text = $post->full_text;
  preg_match_all('#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', $post->text, $extra_links);

  foreach ($extra_links[0] as $extra_link) {
    $post->text = str_replace($extra_link, Link::fromTextAndUrl($extra_link, Url::fromUri($extra_link, ['attributes' => ['target' => '_blank']]))->toString(), $post->text);
  }
  if (isset($post->text)) {
    $post->tweet = substr(rtrim($post->text), 0, $trim_length);
  }
  if (!empty($teaser_text)) {
    if (isset($post->entities->media)) {
      $post->tweet_url = Link::fromTextAndUrl(t('@teaser_text', ['@teaser_text' => $teaser_text]), Url::fromUri($post->entities->media[0]->url, ['attributes' => ['target' => '_blank']]))->toString();
    }
  }
  if ($display_time) {
    $formatted_twitter_date = new DateTime($post->created_at);
    if ($display_date_twitter_style) {
      $post->twitter_date = socialfeed_time_elapsed_string($formatted_twitter_date);
    }
    else {
      $post->twitter_date = $formatted_twitter_date->format($time_format);
    }
  }
  if ($twitter_hash_tag) {
    $post->tweet = preg_replace_callback(
      '/#(\\w+)|@(\\w+)/u',
      function ($hash) {
        if ($hash[0][0] == '#') {
          return Link::fromTextAndUrl($hash[0], Url::fromUri('//twitter.com/hashtag/' . $hash[1], ['attributes' => ['target' => '_blank']]))->toString();
        }
        if ($hash[0][0] == '@') {
          return Link::fromTextAndUrl($hash[0], Url::fromUri('//twitter.com/' . $hash[2], ['attributes' => ['target' => '_blank']]))->toString();
        }
      },
      $post->tweet
    );
  }
  $post->tweet = ['#markup' => $post->tweet];
}

/**
 * Displays date in Twitter format.
 */
function socialfeed_time_elapsed_string($datetime) {
  return Carbon::instance($datetime)->diffForHumans();
}
