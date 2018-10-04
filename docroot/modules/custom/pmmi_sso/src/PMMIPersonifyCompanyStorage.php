<?php

namespace Drupal\pmmi_sso;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for Personify Company.
 *
 * This extends the base storage class, adding required special handling for
 * Personify Company entities.
 */
class PMMIPersonifyCompanyStorage extends SqlContentEntityStorage implements PMMIPersonifyCompanyStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getExistCompanyByPersonifyId(array $ids) {
    $query = $this->database->select($this->getBaseTable(), 'pc')
      ->fields('pc', ['id', 'personify_id'])
      ->condition('pc.personify_id', $ids, 'IN');
    return $query->execute()->fetchAllKeyed();
  }

  /**
   * {@inheritdoc}
   */
  public function getCompaniesForUpdate($interval) {
    $query = $this->database->select($this->getBaseTable(), 'pc')
      ->fields('pc', ['id', 'personify_id'])
      ->condition('pc.changed', REQUEST_TIME - $interval, '<');
    return $query->execute()->fetchAllKeyed();
  }

}
