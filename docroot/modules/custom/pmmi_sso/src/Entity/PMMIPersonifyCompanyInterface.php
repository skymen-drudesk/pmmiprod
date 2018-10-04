<?php

namespace Drupal\pmmi_sso\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Personify company entities.
 *
 * @ingroup pmmi_sso
 */
interface PMMIPersonifyCompanyInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Personify company name (LabelName).
   *
   * @return string
   *   Name of the Personify company.
   */
  public function getName();

  /**
   * Sets the Personify company name (LabelName).
   *
   * @param string $name
   *   The Personify company name.
   *
   * @return \Drupal\pmmi_sso\Entity\PMMIPersonifyCompanyInterface
   *   The called Personify company entity.
   */
  public function setName($name);

  /**
   * Gets the Personify company ID (MasterCustomerId).
   *
   * @return string
   *   ID of the Personify company.
   */
  public function getPersonifyId();

  /**
   * Sets the Personify company ID (MasterCustomerId).
   *
   * @param string $personify_id
   *   The Personify company ID.
   *
   * @return \Drupal\pmmi_sso\Entity\PMMIPersonifyCompanyInterface
   *   The called Personify company entity.
   */
  public function setPersonifyId($personify_id);

  /**
   * Gets the Personify company Code (CustomerClassCode).
   *
   * @return string
   *   Name of the Personify company.
   */
  public function getCode();

  /**
   * Sets the Personify company Code (CustomerClassCode).
   *
   * @param string $code
   *   The Personify company name.
   *
   * @return \Drupal\pmmi_sso\Entity\PMMIPersonifyCompanyInterface
   *   The called Personify company entity.
   */
  public function setCode($code);

  /**
   * Gets the Personify company creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Personify company.
   */
  public function getCreatedTime();

  /**
   * Sets the Personify company creation timestamp.
   *
   * @param int $timestamp
   *   The Personify company creation timestamp.
   *
   * @return \Drupal\pmmi_sso\Entity\PMMIPersonifyCompanyInterface
   *   The called Personify company entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Personify company published status indicator.
   *
   * Unpublished Personify company are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Personify company is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Personify company.
   *
   * @param bool $published
   *   TRUE to set this Personify company to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\pmmi_sso\Entity\PMMIPersonifyCompanyInterface
   *   The called Personify company entity.
   */
  public function setPublished($published);

}
