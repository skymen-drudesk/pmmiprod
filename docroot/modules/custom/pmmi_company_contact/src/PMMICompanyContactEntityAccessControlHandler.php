<?php

/**
 * @file
 * Contains \Drupal\pmmi_company_contact\PMMICompanyContactEntityAccessControlHandler.
 */

namespace Drupal\pmmi_company_contact;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Company contact entity.
 *
 * @see \Drupal\pmmi_company_contact\Entity\PMMICompanyContactEntity.
 */
class PMMICompanyContactEntityAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view company contact entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit company contact entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete company contact entities');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add company contact entities');
  }

}
