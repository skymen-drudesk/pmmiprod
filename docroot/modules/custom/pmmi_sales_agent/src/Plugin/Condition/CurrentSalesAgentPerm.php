<?php

namespace Drupal\pmmi_sales_agent\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a condition by sales agent permission.
 *
 * @Condition(
 *   id = "sales_agent_permission",
 *   label = @Translation("Sales agent permission"),
 * )
 */
class CurrentSalesAgentPerm extends ConditionPluginBase {
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['permission'] = [
      '#type' => 'radios',
      '#title' => $this->t('Current user has permission'),
      '#default_value' => $this->configuration['permission'],
      '#options' => $this->getPermissions(),
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
    $permissions = $this->getPermissions();
    $permission_name = $permissions[$this->configuration['permission']];

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

  /**
   * Get permissions of the pmmi sales agent module.
   *
   * @return array
   *   The permissions list.
   */
  protected function getPermissions() {
    return [
      'pmmi sales agent administration' => $this->t('Administer sales agent directory'),
      'pmmi sales agent favorites' => $this->t("Access to 'My favorites' page"),
      'pmmi sales agent search' => $this->t('Use sales agent directory search'),
    ];
  }
}
