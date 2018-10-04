<?php

namespace Drupal\pmmi_sso\Entity\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PMMISSOTokenSettingsForm.
 *
 * @package Drupal\pmmi_sso
 */
class PMMISSOTokenSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'pmmi_sso_token_settings';
  }

  /**
   * Defines the settings form for Access Token entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['expiration'] = [
      '#type' => 'number',
      '#title' => $this->t('Token Session Expiration time'),
      '#min' => 10,
      '#step' => 10,
      '#description' => $this->t(
        "The default value, in seconds, to be used as the expiration time when 
        creating or renewing a user's token. If a user's token time is expired 
        for a session, run the validation process for that user."
      ),
      '#default_value' => $this->config('pmmi_sso.settings')->get('expiration'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->configFactory()->getEditable('pmmi_sso.settings');
    $settings->set('expiration', $form_state->getValue('expiration'));
    $settings->save();
  }

}
