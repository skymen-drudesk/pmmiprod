<?php

namespace Drupal\pmmi_sso\Entity\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting Access Token entities.
 *
 * @ingroup pmmi_sso
 */
class PMMISSOTokenDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete entity %name?',
      [
        '%name' => $this->entity->label(),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->toUrl('canonical');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    drupal_set_message(
      $this->t('Token deleted @label.',
        [
          '@label' => $this->entity->label(),
        ]
      )
    );

    $form_state->setRedirect('entity.pmmi_sso_token.collection');
  }

}
