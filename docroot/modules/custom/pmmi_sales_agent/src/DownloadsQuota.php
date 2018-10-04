<?php

namespace  Drupal\pmmi_sales_agent;

use Drupal\Core\Database\Database;

/**
 * Downloads quota service.
 */
class DownloadsQuota {

  /**
   * Get user-level quota.
   *
   * @param integer $uid
   *   The user ID.
   *
   * @return integer
   *   The user-level downloads quota.
   */
  public function getUserLevelQuota($uid) {
    $reporting_settings = \Drupal::service('config.factory')
      ->getEditable('pmmi_sales_agent.reporting_settings');

    $entity_manager = \Drupal::entityTypeManager();
    $entity = $entity_manager->getStorage('sad_downloads_quota')->load($uid);

    return $entity ? $entity->getQuota() : $reporting_settings->get('records_per_year');
  }

  /**
   * Get count of downloads by last year.
   *
   * @param integer $uid
   *   The user ID.
   *
   * @return integer
   *   The user's downloads by last year.
   */
  public function getDownloadsByYear($uid) {
    $connection = Database::getConnection();

    $query = $connection->select('sad_user_stat', 'sus')
      ->condition('sus.type', 'records_download')
      ->condition('sus.created', strtotime('-1 year'), '>')
      ->condition('user_id', $uid);

    $query->join('sad_user_stat__field_records_number', 'rn', 'rn.entity_id=sus.id');
    $query->condition('rn.bundle', 'records_download')
      ->addExpression('SUM(rn.field_records_number_value)', 'downloads');

    $sum = $query->execute()->fetchField();
    return $sum ?: 0;
  }

  /**
   * Get available number of downloads.
   *
   * @param integer $uid
   *   The user ID.
   *
   * @return integer
   *   The number of available downloads.
   */
  public function availableDownloadsNumber($uid) {
    $num = $this->getUserLevelQuota($uid) - $this->getDownloadsByYear($uid);
    return $num > 0 ? $num : 0;
  }
}
