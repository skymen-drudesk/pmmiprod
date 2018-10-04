<?php

/**
 * @file
 * Contains \Drupal\pmmi_company_contact\Entity\Form\PMMICompanyContactEntitySettingsForm.
 */

namespace Drupal\pmmi_company_contact\Entity\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PMMICompanyContactEntitySettingsForm.
 *
 * @package Drupal\pmmi_company_contact\Form
 *
 * @ingroup pmmi_company_contact
 */
class PMMICompanyContactEntitySettingsForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'PMMICompanyContactEntity_settings';
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
    // Empty implementation of the abstract submit class.
  }


  /**
   * Defines the settings form for Company contact entities.
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
    $form['PMMICompanyContactEntity_settings']['#markup'] = 'Settings form for Company contact entities. Manage field settings here.';
    return $form;
  }

}
