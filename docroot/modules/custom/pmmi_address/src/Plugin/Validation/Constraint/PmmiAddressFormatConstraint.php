<?php

namespace Drupal\pmmi_address\Plugin\Validation\Constraint;

use Drupal\address\Plugin\Validation\Constraint\AddressFormatConstraint;

/**
 * Address format constraint.
 *
 * @Constraint(
 *   id = "PmmiAddressFormat",
 *   label = @Translation("Address Format", context = "Validation"),
 *   type = { "address" }
 * )
 */
class PmmiAddressFormatConstraint extends AddressFormatConstraint {}
