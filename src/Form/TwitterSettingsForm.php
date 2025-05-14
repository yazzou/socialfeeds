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
    $form['help'] = [
      '#type' => 'item',
      '#title' => $this->t('You can generate the following keys & tokens at @developer
        <ol>
          <li>Sign up at the above URL, if not done yet.</li>
          <li>Once the above step is completed, create a new app.</li>
          <li>You should be able to see all the essential keys at @developer@twitter</li>
        </ol>
        ', [
          '@twitter' => '/en/portal/projects/<PROJECT-ID>/apps/<APP-ID>/keys',
          '@developer' => 'https://developer.twitter.com',
        ]),
    ];
    $form['consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Twitter API Key'),
      '#default_value' => $config->get('consumer_key'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Twitter API Secret Key'),
      '#default_value' => $config->get('consumer_secret'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Twitter Access Token'),
      '#default_value' => $config->get('access_token'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['access_token_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Twitter Access Token Secret'),
      '#default_value' => $config->get('access_token_secret'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['tweets_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Default Tweet Count'),
      '#default_value' => $config->get('tweets_count'),
      '#size' => 60,
      '#maxlength' => 100,
      '#min' => 1,
    ];
    // @todo Move these to the block form; Update theme implementation.
    $form['hashtag'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Hashtag'),
      '#default_value' => $config->get('hashtag'),
    ];
    $form['time_stamp'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Date/Time'),
      '#default_value' => $config->get('time_stamp'),
    ];
    $form['time_ago'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Twitter Style Date'),
      '#default_value' => $config->get('time_ago'),
      '#states' => [
        'visible' => [
          ':input[name="time_stamp"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['time_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date/Time Format'),
      '#default_value' => $config->get('time_format'),
      '#description' => $this->t('You can check for PHP Date Formats <a href="@datetime" target="@blank">here</a>', [
        '@datetime' => 'https://www.php.net/manual/en/datetime.format.php',
        '@blank' => '_blank',
      ]),
      '#size' => 60,
      '#maxlength' => 100,
      '#states' => [
        'visible' => [
          ':input[name="time_stamp"]' => ['checked' => TRUE],
          ':input[name="time_ago"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['trim_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Trim Length'),
      '#default_value' => $config->get('trim_length'),
      '#size' => 60,
      '#maxlength' => 280,
    ];
    $form['teaser_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Teaser Text'),
      '#default_value' => $config->get('teaser_text'),
      '#size' => 60,
      '#maxlength' => 60,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('socialfeed.twitter.settings');
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
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
