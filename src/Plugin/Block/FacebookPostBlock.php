<?php

namespace Drupal\socialfeed\Plugin\Block;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\socialfeed\Services\FacebookPostCollectorFactory;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Facebook' block.
 *
 * @Block(
 *  id = "facebook_post",
 *  admin_label = @Translation("Facebook Block"),
 * )
 */
class FacebookPostBlock extends SocialBlockBase implements ContainerFactoryPluginInterface, BlockPluginInterface {

  /**
   * The facebook service.
   *
   * @var \Drupal\socialfeed\Services\FacebookPostCollectorFactory
   */
  protected $facebook;

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
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FacebookPostCollectorFactory $facebook, ConfigFactoryInterface $config, AccountInterface $currentUser, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->facebook = $facebook;
    $this->config = $config->get('socialfeed.facebook.settings');
    $this->currentUser = $currentUser;
    $this->logger = $logger_factory->get('socialfeed');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('socialfeed.facebook'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $post_type_options = [
      'shared_story' => 'Shared story',
      'published_story' => 'Published story',
      'mobile_status_update' => 'Status',
      'added_photos' => 'Photo',
      'added_video' => 'Video',
    ];

    $form['overrides']['page_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Page Name'),
      '#default_value' => $this->defaultSettingValue('page_name'),
      '#description' => $this->t('e.g. If your Facebook page URL is this @facebook, then use <strong>YOUR_PAGE_NAME</strong> above.', ['@facebook' => 'https://www.facebook.com/YOUR_PAGE_NAME']),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];

    $form['overrides']['app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook App ID'),
      '#default_value' => $this->defaultSettingValue('app_id'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];

    $form['overrides']['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Secret Key'),
      '#default_value' => $this->defaultSettingValue('secret_key'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];

    $form['overrides']['user_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook User Token'),
      '#default_value' => $this->defaultSettingValue('user_token'),
      '#description' => $this->t('You can generate this at @facebook
        <ul>
          <li>Select the appropriate App under <em>Facebook App</em></li>
          <li>Then from <em>User or Page</em> select your listed page from <em>Page Access Tokens</em></li>
        </ul>
        ', ['@facebook' => 'https://developers.facebook.com/tools/explorer/']),
      '#size' => 60,
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['overrides']['no_feeds'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of Feeds'),
      '#default_value' => $this->defaultSettingValue('no_feeds'),
      '#size' => 60,
      '#maxlength' => 60,
      '#max' => 100,
      '#min' => 1,
    ];

    $form['overrides']['all_types'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show all post types'),
      '#default_value' => $this->defaultSettingValue('all_types'),
      '#states' => [
        'required' => [],
      ],
    ];

    $form['overrides']['post_type'] = [
      '#type' => 'select',
      '#title' => 'Select your post type(s) to show',
      '#default_value' => $this->defaultSettingValue('post_type'),
      '#options' => $post_type_options,
      '#empty_option' => $this->t('- Select -'),
      '#states' => [
        'visible' => [
          ':input[name="settings[overrides][all_types]"]' => ['checked' => FALSE],
        ],
        'required' => [
          ':input[name="settings[overrides][all_types]"]' => ['checked' => FALSE],
        ],
      ],
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
    $block_settings = $this->getConfiguration();
    try {
      if ($block_settings['override']) {
        $facebook = $this->facebook->createInstance($block_settings['app_id'], $block_settings['secret_key'], $block_settings['user_token'], $this->config->get('page_name'));
      }
      else {
        $facebook = $this->facebook->createInstance($this->config->get('app_id'), $this->config->get('secret_key'), $this->config->get('user_token'), $this->config->get('page_name'));
      }

      $post_types = $this->getSetting('all_types');
      if (!$post_types) {
        $post_types = $this->getSetting('post_type');
      }
      $posts = $facebook->getPosts(
        $post_types,
        $this->getSetting('no_feeds')
      );
      foreach ($posts as $post) {
        if ($post['status_type'] = !NULL) {
          $items[] = [
            '#theme' => [
              'socialfeed_facebook_post__' . $post['status_type'],
              'socialfeed_facebook_post',
            ],
            '#post' => $post,
            '#cache' => [
              // Cache for 1 hour.
              'max-age' => 60 * 60,
              'cache tags' => $this->config->getCacheTags(),
              'context' => $this->config->getCacheContexts(),
            ],
          ];
        }
      }
    }
    catch (\Exception $exception) {
      $this->logger->error($this->t('Exception: @exception', [
        '@exception' => $exception->getMessage(),
      ]));
    }
    catch (GuzzleException $e) {
      $this->logger->error($this->t('Exception: @exception', [
        '@exception' => $e->getMessage(),
      ]));
    }

    $build['posts'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];

    return $build;
  }

}
