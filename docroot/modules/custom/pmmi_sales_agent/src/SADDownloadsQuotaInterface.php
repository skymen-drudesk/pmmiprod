<?php

namespace Drupal\pmmi_sales_agent;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining downloads quota entities.
 */
interface SADDownloadsQuotaInterface extends ConfigEntityInterface {

  /**
   * Gets the downloads quota.
   *
   * @return string
   */
  public function getQuota();

  /**
   * Sets the downloads quota.
   *
   * @param integer $quota
   *   The downloads quota.
   *
   * @return string
   */
  public function setQuota($quota);
}
