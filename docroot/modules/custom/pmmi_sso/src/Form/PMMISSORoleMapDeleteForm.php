<?php

namespace Drupal\pmmi_sso\Form;

use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\pmmi_sso\Service\PMMISSOHelper;

/**
 * Defines a confirmation form for deleting a Audience.
 */
class PMMISSORoleMapDeleteForm extends ConfirmFormBase {
  use ConfigFormBaseTrait;

  /**
   * The Drupal role ID.
   *
   * @var string
   */
  protected $roleId;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pmmi_sso.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete role mapping for the %role role?', ['%role' => $this->roleId]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('pmmi_sso.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_sso_role_map_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $role_id = NULL) {
    $this->roleId = $role_id;

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('pmmi_sso.settings');
    $config->clear('user_accounts.role_mapping.' . $this->roleId);
    $config->save();
    $args = [
      '%role_id' => $this->roleId,
    ];

    $this->logger(PMMISSOHelper::PROVIDER)
      ->notice('The %role_id map has been deleted.', $args);

    drupal_set_message($this->t('The %role_id map has been deleted.', $args));

    $form_state->setRedirect('pmmi_sso.settings');
  }

}
