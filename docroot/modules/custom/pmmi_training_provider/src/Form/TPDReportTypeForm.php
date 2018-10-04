<?php

namespace Drupal\pmmi_training_provider\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\pmmi_sales_agent\Form\SADUserStatTypeForm;

/**
 * Class TPDReportTypeForm.
 *
 * @package Drupal\pmmi_training_provider\Form
 */
class TPDReportTypeForm extends SADUserStatTypeForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label']['#description'] = $this->t("Label for the training provider report type.");

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $tpd_report_type = $this->entity;
    $status = $tpd_report_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label training provider report type.', [
          '%label' => $tpd_report_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label training provider report type.', [
          '%label' => $tpd_report_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($tpd_report_type->toUrl('collection'));
  }

}
