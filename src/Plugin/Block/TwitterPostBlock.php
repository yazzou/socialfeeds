<?php

namespace Drupal\socialfeed\Plugin\Block;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\socialfeed\Services\TwitterPostCollectorFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Twitter' block.
 *
 * @Block(
 *  id = "twitter_post_block",
 *  admin_label = @Translation("Twitter Block"),
 * )
 */
class TwitterPostBlock extends SocialBlockBase implements ContainerFactoryPluginInterface, BlockPluginInterface {

  /**
   * The Twitter service.
   *
   * @var \Drupal\socialfeed\Services\TwitterPostCollectorFactory
   */
  protected $twitter;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TwitterPostCollectorFactory $socialfeed_twitter, ConfigFactoryInterface $config, AccountInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->twitter = $socialfeed_twitter;
    $this->config = $config->get('socialfeed.twitter.settings');
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('socialfeed.twitter'),
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['overrides']['consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Consumer Key'),
      '#default_value' => $this->defaultSettingValue('consumer_key'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];

    $form['overrides']['consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Consumer Secret'),
      '#default_value' => $this->defaultSettingValue('consumer_secret'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];

    $form['overrides']['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Access Token'),
      '#default_value' => $this->defaultSettingValue('access_token'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];

    $form['overrides']['access_token_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Access Token Secret'),
      '#default_value' => $this->defaultSettingValue('access_token_secret'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];

    $form['overrides']['tweets_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Tweets Count'),
      '#default_value' => $this->defaultSettingValue('tweets_count'),
      '#size' => 60,
      '#maxlength' => 100,
      '#min' => 1,
    ];

    $this->blockFormElementStates($form);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $items = [];
    $config = $this->config;
    $block_settings = $this->getConfiguration();

    if ($block_settings['override']) {
      $twitter = $this->twitter->createInstance($block_settings['consumer_key'], $block_settings['consumer_secret'], $block_settings['access_token'], $block_settings['access_token_secret']);
    }
    else {
      $twitter = $this->twitter->createInstance($config->get('consumer_key'), $config->get('consumer_secret'), $config->get('access_token'), $config->get('access_token_secret'));
    }

    $tweets_count = $this->getSetting('tweets_count');
    $posts = $twitter->getPosts($tweets_count);
    if (!is_array($posts)) {
      return $build;
    }

    foreach ($posts as $post) {
      $items[] = [
        '#theme' => 'socialfeed_twitter_post',
        '#post' => $post,
        '#cache' => [
          // Cache for 1 hour.
          'max-age' => 60 * 60,
          'cache tags' => $this->config->getCacheTags(),
          'context' => $this->config->getCacheContexts(),
        ],
      ];
    }
    $build['posts'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    return $build;
  }

}
