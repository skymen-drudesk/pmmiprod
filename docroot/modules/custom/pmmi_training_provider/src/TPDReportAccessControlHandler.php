<?php

namespace Drupal\pmmi_training_provider;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\pmmi_sales_agent\SADUserStatAccessControlHandler;

/**
 * Access controller for the Sales agent user stat entity.
 *
 * @see \Drupal\pmmi_training_provider\Entity\TPDReport.
 */
class TPDReportAccessControlHandler extends SADUserStatAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view training provider report entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit training provider report entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete training provider report entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add training provider report entities');
  }

}
