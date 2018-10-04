<?php

namespace Drupal\pmmi_sso\Entity\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PMMIPersonifyCompanySettingsForm.
 *
 * @package Drupal\pmmi_sso\Entity\Form
 *
 * @ingroup pmmi_sso
 */
class PMMIPersonifyCompanySettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'pmmi_personify_company_settings';
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
    $settings = $this->configFactory()->getEditable('pmmi_sso.company.settings');
    $settings->set('time_duration', $form_state->getValue('time_duration'));
    $settings->save();
  }

  /**
   * Defines the settings form for Personify company entities.
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
    $form['time_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Time duration'),
      '#min' => 100,
      '#step' => 100,
      '#description' => $this->t(
        "Time in seconds from the user's last login. Used as an event to 
        update the relationships between the user and companies."
      ),
      '#default_value' => $this->config('pmmi_sso.company.settings')->get('time_duration'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    return $form;
  }

}
