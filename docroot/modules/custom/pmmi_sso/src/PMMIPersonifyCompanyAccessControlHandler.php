<?php

namespace Drupal\pmmi_sso;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Personify company entity.
 *
 * @see \Drupal\pmmi_sso\Entity\PMMIPersonifyCompany.
 */
class PMMIPersonifyCompanyAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\pmmi_sso\Entity\PMMIPersonifyCompanyInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished personify company entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published personify company entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit personify company entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete personify company entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

}
