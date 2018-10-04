<?php

namespace Drupal\pmmi_training_provider\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\pmmi_sales_agent\Form\PMMISADMigrateForm;

/**
 * Provide a form to upload .xslx file in order to import new training providers.
 */
class PMMITPDMigrateForm extends PMMISADMigrateForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_tpd_migrate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['help']['#description'] = $this->t('Your import file should include 
      only training providers which you want to import. If items have failed,
      you can find the reason on the "Messages" tab. After that, you can fix it
      and re-import (only failed items will be imported at a second time).'
    );
    $form['worksheet'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Worksheet name'),
      '#default_value' => 'Sheet1',
      '#description' => $this->t('Change this if xls sheet has another name.'),
      '#weight' => 1,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->migration_id = 'training_provider';
    parent::submitForm($form, $form_state);
  }

}
