<?php

namespace Drupal\pmmi\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a condition by user has permission.
 *
 * @Condition(
 *   id = "user_has_permission",
 *   label = @Translation("User has permission"),
 * )
 */
class UserHasPermission extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['permission'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Current user has permission'),
      '#default_value' => $this->configuration['permission'],
      '#required' => TRUE,
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['permission' => NULL] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['permission'] = $form_state->getValue('permission');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $permission_name = $this->configuration['permission'];

    if ($this->configuration['negate']) {
      return $this->t('Current user does not have "@permission" permission.', ['@permission' => $permission_name]);
    }
    else {
      return $this->t('Current user has "@permission" permission.', ['@permission' => $permission_name]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $hasPermission = \Drupal::currentUser()
      ->hasPermission($this->configuration['permission']);

    return $this->configuration['negate'] ? !$hasPermission : $hasPermission;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'permission';
    return $contexts;
  }

}
