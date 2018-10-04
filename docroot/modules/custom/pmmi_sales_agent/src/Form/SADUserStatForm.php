<?php

namespace Drupal\pmmi_sales_agent\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Sales agent user stat edit forms.
 *
 * @ingroup pmmi_sales_agent
 */
class SADUserStatForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\pmmi_sales_agent\Entity\SADUserStat */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Sales agent user stat.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Sales agent user stat.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.sad_user_stat.canonical', ['sad_user_stat' => $entity->id()]);
  }

}
