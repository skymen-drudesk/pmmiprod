<?php

namespace Drupal\pmmi_sso;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines an interface for Personify Company entity storage classes.
 */
interface PMMIPersonifyCompanyStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Drupal ID of Personify company already exist in db.
   *
   * @param array $ids
   *   The array of Personify companies IDs.
   *
   * @return array
   *   The array of exist company IDs (personify_id => drupal_id).
   */
  public function getExistCompanyByPersonifyId(array $ids);

  /**
   * Gets an array of Drupal IDs for the Personify companies, which need update.
   *
   * @param int $interval
   *   The array of Personify companies IDs.
   *
   * @return array
   *   The array of exist company IDs (personify_id => drupal_id).
   */
  public function getCompaniesForUpdate($interval);

}
