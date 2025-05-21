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
   * The Twitter service factory.
   *
   * @var \Drupal\socialfeed\Services\TwitterPostCollectorFactory
   */
  protected $twitterFactory;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TwitterPostCollectorFactory $twitter_factory, ConfigFactoryInterface $config, AccountInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->twitterFactory = $twitter_factory;
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
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'override_global_settings' => FALSE,
      'bearer_token' => '',
      'consumer_key' => '',
      'consumer_secret' => '',
      'access_token' => '',
      'access_token_secret' => '',
      'tweets_count' => $this->config->get('tweets_count') ?: 5,
      // user_id and cache_duration are not block-specific in this version.
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $block_config = $this->getConfiguration();

    // Renaming 'override' to 'override_global_settings' for clarity from parent::blockForm
    if (isset($form['override'])) {
        $form['override_global_settings'] = $form['override'];
        $form['override_global_settings']['#title'] = $this->t('Override global Twitter settings');
        unset($form['override']);
    } elseif(isset($form['override_global_settings'])) {
        // It's already correctly named by a potential parent call, ensure title is specific.
        $form['override_global_settings']['#title'] = $this->t('Override global Twitter settings');
    }


    // API v2 Override
    $form['overrides']['bearer_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Override Twitter Bearer Token (API v2)'),
      '#default_value' => $block_config['bearer_token'] ?? $this->config->get('bearer_token'),
      '#description' => $this->t('Provide a Bearer Token to override the global setting for this block only. If provided, legacy keys are ignored for this block.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[override_global_settings]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="settings[override_global_settings]"]' => ['checked' => TRUE],
          ':input[name="settings[overrides][consumer_key]"]' => ['filled' => FALSE],
        ],
        'optional' => [
          ':input[name="settings[override_global_settings]"]' => ['checked' => TRUE],
          ':input[name="settings[overrides][consumer_key]"]' => ['filled' => TRUE],
        ],
      ],
    ];
    
    // Note: user_id is NOT overridable at block level for now. It uses global config.

    // API v1.1 Legacy Overrides
    $form['overrides']['consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Override Twitter API Key (Legacy v1.1)'),
      '#default_value' => $block_config['consumer_key'] ?? $this->config->get('consumer_key'),
      '#states' => [
        'visible' => [
          ':input[name="settings[override_global_settings]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="settings[override_global_settings]"]' => ['checked' => TRUE],
          ':input[name="settings[overrides][bearer_token]"]' => ['filled' => FALSE],
        ],
        'optional' => [
          ':input[name="settings[override_global_settings]"]' => ['checked' => TRUE],
          ':input[name="settings[overrides][bearer_token]"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $form['overrides']['consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Override Twitter API Secret Key (Legacy v1.1)'),
      '#default_value' => $block_config['consumer_secret'] ?? $this->config->get('consumer_secret'),
      '#states' => [
        'visible' => [
          ':input[name="settings[override_global_settings]"]' => ['checked' => TRUE],
        ],
         'required' => [
          ':input[name="settings[override_global_settings]"]' => ['checked' => TRUE],
          ':input[name="settings[overrides][bearer_token]"]' => ['filled' => FALSE],
        ],
        'optional' => [
          ':input[name="settings[override_global_settings]"]' => ['checked' => TRUE],
          ':input[name="settings[overrides][bearer_token]"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $form['overrides']['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Override Twitter Access Token (Legacy v1.1)'),
      '#default_value' => $block_config['access_token'] ?? $this->config->get('access_token'),
      '#states' => [
        'visible' => [
          ':input[name="settings[override_global_settings]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="settings[override_global_settings]"]' => ['checked' => TRUE],
          ':input[name="settings[overrides][bearer_token]"]' => ['filled' => FALSE],
        ],
        'optional' => [
          ':input[name="settings[override_global_settings]"]' => ['checked' => TRUE],
          ':input[name="settings[overrides][bearer_token]"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $form['overrides']['access_token_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Override Twitter Access Token Secret (Legacy v1.1)'),
      '#default_value' => $block_config['access_token_secret'] ?? $this->config->get('access_token_secret'),
      '#states' => [
        'visible' => [
          ':input[name="settings[override_global_settings]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="settings[override_global_settings]"]' => ['checked' => TRUE],
          ':input[name="settings[overrides][bearer_token]"]' => ['filled' => FALSE],
        ],
        'optional' => [
          ':input[name="settings[override_global_settings]"]' => ['checked' => TRUE],
          ':input[name="settings[overrides][bearer_token]"]' => ['filled' => TRUE],
        ],
      ],
    ];

    // Common Overrides
    $form['overrides']['tweets_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Override Tweets Count'),
      '#default_value' => $block_config['tweets_count'] ?? $this->config->get('tweets_count'),
      '#min' => 1,
      '#max' => 100, // Max for user_tweets timeline is 100 for v2, min 5.
      '#description' => $this->t('Number of tweets to display. For API v2, this must be between 5 and 100.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[override_global_settings]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    
    // Ensure the #states paths are correct for elements within 'overrides' details.
    // The parent SocialBlockBase might have its own handling for 'override' checkbox,
    // this code assumes 'override_global_settings' is the effective key for the checkbox state.
    // If SocialBlockBase::blockFormElementStates exists and is used, it might need review.
    // For now, explicit #states are added here.

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $items = [];
    $block_config = $this->getConfiguration();
    $global_config = $this->config;

    $collector_instance_params = [
      'consumer_key' => $global_config->get('consumer_key'),
      'consumer_secret' => $global_config->get('consumer_secret'),
      'access_token' => $global_config->get('access_token'),
      'access_token_secret' => $global_config->get('access_token_secret'),
      'bearer_token' => $global_config->get('bearer_token'),
    ];

    if (!empty($block_config['override_global_settings'])) {
      $collector_instance_params['consumer_key'] = $block_config['consumer_key'] ?? $collector_instance_params['consumer_key'];
      $collector_instance_params['consumer_secret'] = $block_config['consumer_secret'] ?? $collector_instance_params['consumer_secret'];
      $collector_instance_params['access_token'] = $block_config['access_token'] ?? $collector_instance_params['access_token'];
      $collector_instance_params['access_token_secret'] = $block_config['access_token_secret'] ?? $collector_instance_params['access_token_secret'];
      $collector_instance_params['bearer_token'] = $block_config['bearer_token'] ?? $collector_instance_params['bearer_token'];
    }

    $twitter_collector = $this->twitterFactory->createInstance(
      $collector_instance_params['consumer_key'],
      $collector_instance_params['consumer_secret'],
      $collector_instance_params['access_token'],
      $collector_instance_params['access_token_secret'],
      $collector_instance_params['bearer_token']
    );

    $tweets_count = (!empty($block_config['override_global_settings']) && isset($block_config['tweets_count'])) ? $block_config['tweets_count'] : $global_config->get('tweets_count');
    // Ensure count is valid for API v2 if bearer token is active
    if (!empty($collector_instance_params['bearer_token'])) {
        if ($tweets_count < 5) $tweets_count = 5;
        if ($tweets_count > 100) $tweets_count = 100;
    }


    $posts = $twitter_collector->getPosts($tweets_count);

    if (empty($posts) || !is_array($posts)) {
      // Return empty build if no posts, or log message.
      // Consider adding a user-friendly message if needed.
      return $build;
    }

    foreach ($posts as $post) {
      $items[] = [
        '#theme' => 'socialfeed_twitter_post',
        '#post' => $post,
        // Cache metadata for each item will be handled by Drupal's render cache
        // for the block as a whole, based on its #cache properties.
        // The data itself is cached within TwitterPostCollector.
      ];
    }
    $build['posts'] = [
      '#theme' => 'item_list',
      '#items' => $items,
      '#attributes' => ['class' => ['socialfeed-twitter-posts']],
    ];
    
    // Set cacheability for the block. It depends on the global config and block config.
    // The actual tweet data is cached in the service, so this block's render cache
    // can be shorter or primarily depend on config changes.
    $build['#cache'] = [
      'tags' => $this->config->getCacheTags(),
      // Contexts based on what makes this block vary, e.g., user permissions if any, URL.
      // For now, let's assume config changes are the main driver.
      'contexts' => $this->config->getCacheContexts(),
       // Max age can be set to a reasonable value, e.g., global cache duration / by some factor,
       // or rely on cache tags for invalidation. The data cache in service is primary.
      'max-age' => $global_config->get('cache_duration') * 60, // Align with data cache duration
    ];
    // Add block configuration as a cache tag
    if (isset($block_config['id'])) { // Assuming block config has an ID
        $build['#cache']['tags'][] = 'config:block.block.' . $block_config['id'];
    }


    return $build;
  }

}
