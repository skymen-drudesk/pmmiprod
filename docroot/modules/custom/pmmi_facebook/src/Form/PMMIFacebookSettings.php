<?php

namespace Drupal\pmmi_facebook\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PMMIFacebookSettings.
 *
 * @package Drupal\pmmi_facebook\Form
 */
class PMMIFacebookSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pmmi_facebook.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_facebook_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pmmi_facebook.settings');
    $form['facebook'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Facebook APP'),
    ];
    $form['facebook']['app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('APP Id'),
      '#maxlength' => 255,
      '#size' => 80,
      '#default_value' => $config->get('app_id'),
    ];
    $form['facebook']['app_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('APP secret'),
      '#maxlength' => 255,
      '#size' => 80,
      '#default_value' => $config->get('app_secret'),
    ];
    $form['facebook']['app_secret_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('APP secret token'),
      '#maxlength' => 255,
      '#size' => 80,
      '#default_value' => $config->get('app_secret_token'),
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
    $this->configFactory()->getEditable('pmmi_facebook.settings')
      ->set('app_id', $form_state->getValue('app_id'))
      ->set('app_secret', $form_state->getValue('app_secret'))
      ->set('app_secret_token', $form_state->getValue('app_secret_token'))
      ->save();
  }

}
