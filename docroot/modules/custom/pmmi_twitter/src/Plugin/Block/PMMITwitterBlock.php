<?php

namespace Drupal\pmmi_twitter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twitter;

/**
 * Provides a 'PMMITwitterBlock' block.
 *
 * @Block(
 *  id = "pmmi_twitter_block",
 *  admin_label = @Translation("PMMI Twitter block"),
 * )
 */
class PMMITwitterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'block_title' => $this->t('PMMI'),
      'message_count' => 2,
      'timeline' => 1,
      'max_age' => 1800,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $timeline_options = [
      Twitter::ME => $this->t('My timeline'),
      Twitter::ME_AND_FRIENDS => $this->t('My and friends timeline'),
    ];
    $form['block_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Block title to display'),
      '#required' => TRUE,
      '#maxlength' => 80,
      '#size' => 80,
      '#default_value' => $this->configuration['block_title'],
    ];
    $form['message_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of messages to display'),
      '#min' => 1,
      '#max' => 200,
      '#required' => TRUE,
      '#default_value' => $this->configuration['message_count'],
    ];
    $form['timeline'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of topics'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['timeline'],
      '#options' => $timeline_options,
    ];
    $form['max_age'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache options - Max age in seconds'),
      '#required' => TRUE,
      '#min' => 300,
      '#default_value' => $this->configuration['max_age'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $fields = ['block_title', 'message_count', 'timeline', 'max_age'];
    foreach ($fields as $field) {
      $this->configuration[$field] = $form_state->getValue($field);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return (int) $this->getConfiguration()['max_age'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $connection = FALSE;
    $build['#theme'] = 'pmmi_twitter_block';
    /** @var \Drupal\Core\Config\ImmutableConfig $secret_config */
    $secret_config = \Drupal::config('pmmi_twitter.settings');
    $consumerKey = $secret_config->get('consumer_key');
    $consumerSecret = $secret_config->get('consumer_secret');
    $accessToken = $secret_config->get('access_token');
    $accessTokenSecret = $secret_config->get('access_token_secret');
    $block_config = $this->getConfiguration();
    /** @var \Twitter $twitter */
    $twitter = new Twitter($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
    try {
      $connection = $twitter->authenticate();
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Can not establish connection with Twitter'), 'error');
    }
    // Retrieve the timeline.
    if ($connection) {
      $statuses = $twitter->load($block_config['timeline'], $block_config['message_count']);
      foreach ($statuses as $key => $status) {
        $message = Twitter::clickable($status);
        $date = new DrupalDateTime($status->created_at);
        $build['messages'][$key]['date']['#plain_text'] = $date->format('g:i A - j M Y');
        $build['messages'][$key]['text']['#markup'] = $message;
      }
    }
    else {
      $build['auth_messages']['#plain_text'] = $this->t('No data!!!');
    }
    return $build;
  }

}
