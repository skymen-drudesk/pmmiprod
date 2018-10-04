<?php

/**
 * @file
 * Contains \Drupal\pmmi_company_contact\PMMICompanyContactEntityInterface.
 */

namespace Drupal\pmmi_company_contact;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Company contact entities.
 *
 * @ingroup pmmi_company_contact
 */
interface PMMICompanyContactEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  // Add get/set methods for your configuration properties here.

}
