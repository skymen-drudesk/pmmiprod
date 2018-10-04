<?php

namespace Drupal\pmmi_training_provider\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;

/**
 * Form controller for training provider report edit forms.
 *
 * @ingroup pmmi_training_provider
 */
class TPDReportForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label training provider report.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label training provider report.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.tpd_report.canonical', ['tpd_report' => $entity->id()]);
  }

}
