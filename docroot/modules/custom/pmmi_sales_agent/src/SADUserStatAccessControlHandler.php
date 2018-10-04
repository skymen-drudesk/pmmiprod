<?php

namespace Drupal\pmmi_sales_agent;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Sales agent user stat entity.
 *
 * @see \Drupal\pmmi_sales_agent\Entity\SADUserStat.
 */
class SADUserStatAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\pmmi_sales_agent\Entity\SADUserStatInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view sales agent user stat entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit sales agent user stat entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete sales agent user stat entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add sales agent user stat entities');
  }

}
