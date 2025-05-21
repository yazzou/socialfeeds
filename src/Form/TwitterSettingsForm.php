<?php

namespace Drupal\socialfeed\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Twitter settings for this site.
 *
 * @package Drupal\socialfeed\Form
 */
class TwitterSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twitter_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('socialfeed.twitter.settings');

    // Help text section
    $form['help'] = [
      '#type' => 'item',
      '#markup' => $this->t('<b>Authentication Options:</b> You can authenticate using either a Twitter API v2 Bearer Token or the legacy API v1.1 Consumer and Access Keys. Provide one set of credentials. If the Bearer Token is provided, the legacy keys will be ignored.
        <br/><br/>
        <b>To obtain a Bearer Token (API v2) and User ID:</b>
        <ol>
          <li>Go to the <a href="@developer_portal" target="_blank">Twitter Developer Portal</a>.</li>
          <li>Navigate to your Project, then to your App settings.</li>
          <li>Under the "Keys and tokens" section for your App, you should find your Bearer Token.</li>
          <li>Your Twitter User ID can be found in your Twitter profile URL or using various online tools. It is a numerical ID.</li>
          <li>For more detailed instructions, refer to the <a href="@bearer_token_docs" target="_blank">official Twitter documentation on Bearer Tokens</a> and API v2.</li>
        </ol>
        <b>To obtain legacy Consumer Keys and Access Tokens (API v1.1):</b>
        <ol>
          <li>Sign up at the <a href="@developer_portal" target="_blank">Twitter Developer Portal</a>, if not done yet.</li>
          <li>Create a new app or use an existing one.</li>
          <li>You should be able to see all the essential keys (API Key, API Secret Key, Access Token, Access Token Secret) in your app’s "Keys and tokens" section.</li>
        </ol>
        ', [
          '@developer_portal' => 'https://developer.twitter.com',
          '@bearer_token_docs' => 'https://developer.twitter.com/en/docs/authentication/oauth-2-0/bearer-tokens',
        ]),
    ];

    // API v2 specific fields
    $form['api_v2_settings_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Twitter API v2 Settings'),
      '#open' => TRUE, // Default to open
    ];

    $form['api_v2_settings_group']['bearer_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Twitter Bearer Token (API v2)'),
      '#default_value' => $config->get('bearer_token', ''),
      '#size' => 60,
      '#maxlength' => 255,
      '#description' => $this->t('Enter your Twitter API v2 Bearer Token. If provided, this will be used for authentication. Legacy keys below will be ignored.'),
      '#states' => [
        'required' => [
          [':input[name="consumer_key"]' => ['filled' => FALSE]],
        ],
        'optional' => [
          [':input[name="consumer_key"]' => ['filled' => TRUE]],
        ],
      ],
    ];

    $form['api_v2_settings_group']['user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter User ID (for API v2)'),
      '#default_value' => $config->get('user_id', ''),
      '#size' => 30,
      '#maxlength' => 50,
      '#description' => $this->t('Enter the numerical User ID of the Twitter account you want to fetch tweets from. Required if using Bearer Token.'),
      '#states' => [
        'required' => [
          ':input[name="bearer_token"]' => ['filled' => TRUE],
        ],
        'visible' => [
          ':input[name="bearer_token"]' => ['filled' => TRUE],
        ],
      ],
    ];

    // API v1.1 specific fields
    $form['api_v1_settings_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Twitter API v1.1 Legacy Settings'),
      '#open' => TRUE, // Default to open, or use #states to open if bearer_token is empty
    ];

    $form['api_v1_settings_group']['consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Twitter API Key (Legacy v1.1)'),
      '#default_value' => $config->get('consumer_key'),
      '#size' => 60,
      '#maxlength' => 100,
      '#states' => [
        'required' => [
          ':input[name="bearer_token"]' => ['filled' => FALSE],
        ],
        'optional' => [
          ':input[name="bearer_token"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $form['api_v1_settings_group']['consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Twitter API Secret Key (Legacy v1.1)'),
      '#default_value' => $config->get('consumer_secret'),
      '#size' => 60,
      '#maxlength' => 100,
      '#states' => [
        'required' => [
          ':input[name="bearer_token"]' => ['filled' => FALSE],
        ],
        'optional' => [
          ':input[name="bearer_token"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $form['api_v1_settings_group']['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Twitter Access Token (Legacy v1.1)'),
      '#default_value' => $config->get('access_token'),
      '#size' => 60,
      '#maxlength' => 100,
      '#states' => [
        'required' => [
          ':input[name="bearer_token"]' => ['filled' => FALSE],
        ],
        'optional' => [
          ':input[name="bearer_token"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $form['api_v1_settings_group']['access_token_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Twitter Access Token Secret (Legacy v1.1)'),
      '#default_value' => $config->get('access_token_secret'),
      '#size' => 60,
      '#maxlength' => 100,
      '#states' => [
        'required' => [
          ':input[name="bearer_token"]' => ['filled' => FALSE],
        ],
        'optional' => [
          ':input[name="bearer_token"]' => ['filled' => TRUE],
        ],
      ],
    ];

    // Common settings
    $form['common_settings_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Common Display Settings'),
      '#open' => TRUE,
    ];

    $form['common_settings_group']['tweets_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Default Tweet Count'),
      '#default_value' => $config->get('tweets_count', 5),
      '#min' => 1,
      '#max' => 100, // Max for user_tweets timeline is 100 for v2, min 5.
      '#description' => $this->t('Number of tweets to display. For API v2, this must be between 5 and 100.'),
    ];
    
    $form['common_settings_group']['cache_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache Duration (minutes)'),
      '#default_value' => $config->get('cache_duration', 15),
      '#min' => 1,
      '#description' => $this->t('How long to cache the fetched tweets to avoid hitting API rate limits. Default is 15 minutes.'),
    ];

    // Display options from original form
    $form['common_settings_group']['hashtag'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Hashtag'),
      '#default_value' => $config->get('hashtag'),
    ];
    $form['common_settings_group']['time_stamp'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Date/Time'),
      '#default_value' => $config->get('time_stamp'),
    ];
    $form['common_settings_group']['time_ago'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Twitter Style Date'),
      '#default_value' => $config->get('time_ago'),
      '#states' => [
        'visible' => [
          ':input[name="time_stamp"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['common_settings_group']['time_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date/Time Format'),
      '#default_value' => $config->get('time_format'),
      '#description' => $this->t('PHP Date Formats. Example: <a href="@datetime" target="@blank">d-M-Y H:i</a>', [
        '@datetime' => 'https://www.php.net/manual/en/datetime.format.php',
        '@blank' => '_blank',
      ]),
      '#states' => [
        'visible' => [
          ':input[name="time_stamp"]' => ['checked' => TRUE],
          ':input[name="time_ago"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['common_settings_group']['trim_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Trim Length'),
      '#default_value' => $config->get('trim_length'),
      '#min' => 0,
      '#description' => $this->t('Maximum length of tweet text. Set to 0 for no trimming.'),
    ];
    $form['common_settings_group']['teaser_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Teaser Text (if trimmed)'),
      '#default_value' => $config->get('teaser_text'),
      '#description' => $this->t('Text like "Read More" to append if tweet is trimmed.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve all form values.
    $values = $form_state->getValues();
    $config = $this->config('socialfeed.twitter.settings');

    // Values are structured due to fieldsets, so map them correctly.
    $config->set('bearer_token', $values['bearer_token']);
    $config->set('user_id', $values['user_id']);
    $config->set('consumer_key', $values['consumer_key']);
    $config->set('consumer_secret', $values['consumer_secret']);
    $config->set('access_token', $values['access_token']);
    $config->set('access_token_secret', $values['access_token_secret']);
    
    $config->set('tweets_count', $values['tweets_count']);
    $config->set('cache_duration', $values['cache_duration']);
    $config->set('hashtag', $values['hashtag']);
    $config->set('time_stamp', $values['time_stamp']);
    $config->set('time_ago', $values['time_ago']);
    $config->set('time_format', $values['time_format']);
    $config->set('trim_length', $values['trim_length']);
    $config->set('teaser_text', $values['teaser_text']);
    
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'socialfeed.twitter.settings',
    ];
  }
}
