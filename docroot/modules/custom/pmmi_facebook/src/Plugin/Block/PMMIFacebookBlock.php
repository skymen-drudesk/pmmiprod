<?php

namespace Drupal\pmmi_facebook\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'PMMIFacebookBlock' block.
 *
 * @Block(
 *  id = "pmmi_facebook_block",
 *  admin_label = @Translation("PMMI Facebook block"),
 * )
 */
class PMMIFacebookBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'block_title' => $this->t('PMMI'),
      'message_count' => 1,
      'page_id' => '',
      'max_age' => 300,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
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
      '#description' => '',
      '#min' => 1,
      '#max' => 100,
      '#required' => TRUE,
      '#default_value' => $this->configuration['message_count'],
    ];
    $form['page_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page ID (eg. PMMIorg)'),
      '#required' => TRUE,
      '#maxlength' => 80,
      '#size' => 80,
      '#default_value' => $this->configuration['page_id'],
    ];

    $form['max_age'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache options - Max age in seconds'),
      '#description' => '',
      '#min' => 300,
      '#required' => TRUE,
      '#default_value' => $this->configuration['max_age'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $fields = ['block_title', 'message_count', 'page_id', 'max_age'];
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
    $build['#theme'] = 'pmmi_facebook_block';
    $block_config = $this->getConfiguration();
    $page_id = $block_config['page_id'];
    $limit = $block_config['message_count'];
    /** @var \Drupal\Core\Config\ImmutableConfig $secret_config */
    $secret_config = \Drupal::config('pmmi_facebook.settings');
    $access_token = $secret_config->get('app_secret_token');
    // Get the JSON.
    $json_object = @file_get_contents('https://graph.facebook.com/' . $page_id .
      '/posts?access_token=' . $access_token . '&limit=' . $limit);
    if (!empty($json_object)) {
      $fbdata = json_decode($json_object);
      foreach ($fbdata->data as $key => $status) {
        $date = new DrupalDateTime($status->created_time);
        $build['messages'][$key]['date']['#plain_text'] = $date->format('g:i A - j M Y');
        $build['messages'][$key]['text']['#markup'] = $status->message;
      }
    }
    else {
      $build['auth_messages']['#plain_text'] = $this->t('No data!!!');
    }

    return $build;
  }

}
