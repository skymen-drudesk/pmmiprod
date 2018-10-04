<?php

namespace Drupal\pmmi_sales_agent\Form;

use Drupal\Core\Form\FormBase;

/**
 * Class SADClearTerritoryServed
 *
 * @package Drupal\pmmi_sales_agent\Form
 */
class SADClearTerritoryServed extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'sad_clear_territory_served';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear values'),
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
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $operation = [];

    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'company')
      ->execute();

    foreach ($nids as $key => $value) {
      $operation[] = [
        '\Drupal\pmmi_sales_agent\Controller\SADClearTerritoryServedBatch::bulkUpdate',
        [$value],
      ];
    }

    $batch = [
      'title' => t('Bulk Update...'),
      'operations' => $operation,
      'finished' => '\Drupal\pmmi_sales_agent\Controller\SADClearTerritoryServedBatch::bulkUpdateFinishedCallback',
    ];

    batch_set($batch);
  }
}
