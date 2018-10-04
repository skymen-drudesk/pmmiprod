<?php

namespace Drupal\pmmi_twitter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PMMITwitterSettings.
 *
 * @package Drupal\pmmi_twitter\Form
 */
class PMMITwitterSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pmmi_twitter.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pm_twitter_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pmmi_twitter.settings');
    $form['oauth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('OAuth Settings'),
      '#description' => $this->t(
        'To enable OAuth based access for twitter, you must <a href="@url">register your application</a> with Twitter and add the provided keys here.',
          ['@url' => 'https://dev.twitter.com/apps/new']
      ),
    ];
    $form['oauth']['consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OAuth Consumer key'),
      '#maxlength' => 255,
      '#size' => 80,
      '#default_value' => $config->get('consumer_key'),
    ];
    $form['oauth']['consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OAuth Consumer secret'),
      '#maxlength' => 255,
      '#size' => 80,
      '#default_value' => $config->get('consumer_secret'),
    ];
    $form['oauth']['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#maxlength' => 255,
      '#size' => 80,
      '#default_value' => $config->get('access_token'),
    ];
    $form['oauth']['access_token_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token Secret'),
      '#maxlength' => 255,
      '#size' => 80,
      '#default_value' => $config->get('access_token_secret'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->configFactory()->getEditable('pmmi_twitter.settings')
      ->set('consumer_key', $form_state->getValue('consumer_key'))
      ->set('consumer_secret', $form_state->getValue('consumer_secret'))
      ->set('access_token', $form_state->getValue('access_token'))
      ->set('access_token_secret', $form_state->getValue('access_token_secret'))
      ->save();
  }

}
