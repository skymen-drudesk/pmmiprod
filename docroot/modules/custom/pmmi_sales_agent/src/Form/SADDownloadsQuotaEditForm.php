<?php

namespace Drupal\pmmi_sales_agent\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Edit form for sales agent downloads quota.
 */
class SADDownloadsQuotaEditForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['id'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#title' => $this->t('User'),
      '#required' => TRUE,
      '#disabled' => !$this->entity->isNew(),
      '#default_value' => $this->entity->getUser(),
      '#selection_settings' => ['include_anonymous' => FALSE],
    ];
    $form['quota'] = [
      '#type' => 'number',
      '#title' => $this->t('Records per year'),
      '#required' => TRUE,
      '#min' => 1,
      '#default_value' => $this->entity->getQuota(),
    ];

    $build_info = $form_state->getBuildInfo();
    $entity = $build_info['callback_object']->getEntity();

    // Is entity new?
    $form['new'] = [
      '#type' => 'value',
      '#value' => $entity->id() ? FALSE : TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = \Drupal::entityTypeManager()
      ->getStorage('sad_downloads_quota')
      ->load($form_state->getValue('id'));

    if ($entity && $form_state->getValue('new')) {
      $form_state->setErrorByName('id', t('The downloads quota already added to user @username.', [
        '@username' => $entity->getUser()->getUserName(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    $build_info = $form_state->getBuildInfo();
    $entity = $build_info['callback_object']->getEntity();

    drupal_set_message($this->t('Downloads quota has been changed for @username.', [
      '@username' => $entity->getUser()->getUserName(),
    ]));
  }
}
