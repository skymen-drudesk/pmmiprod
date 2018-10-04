<?php

namespace Drupal\pmmi_sales_agent\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Sales agent user stat entities.
 *
 * @ingroup pmmi_sales_agent
 */
interface SADUserStatInterface extends ContentEntityInterface, EntityOwnerInterface {
  /**
   * Gets the Sales agent user stat type.
   *
   * @return string
   *   The Sales agent user stat type.
   */
  public function getType();

  /**
   * Gets the Sales agent user stat creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Sales agent user stat.
   */
  public function getCreatedTime();

  /**
   * Sets the Sales agent user stat creation timestamp.
   *
   * @param int $timestamp
   *   The Sales agent user stat creation timestamp.
   *
   * @return \Drupal\pmmi_sales_agent\Entity\SADUserStatInterface
   *   The called Sales agent user stat entity.
   */
  public function setCreatedTime($timestamp);
}
