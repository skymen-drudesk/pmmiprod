<?php

namespace Drupal\pmmi_sso\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\pmmi_sso\PMMISSOPropertyBag;

/**
 * Class PMMISSOPreUserLoadEvent.
 *
 * The PMMISSO module dispatches this event during the authentication process
 * just before an attempt is made to find a local Drupal user account that's
 * associated with the user attempting to login.
 *
 * Subscribers to this event can:
 *  - Alter the PMMI SSO username that is used when looking up an existing
 *    Drupal user account.
 */
class PMMISSOPreUserLoadEvent extends Event {

  /**
   * Store the PMMI SSO property bag.
   *
   * @var \Drupal\pmmi_sso\PMMISSOPropertyBag
   *   The PMMISSOPropertyBag for context.
   */
  protected $ssoPropertyBag;

  /**
   * Constructor.
   *
   * @param \Drupal\pmmi_sso\PMMISSOPropertyBag $sso_property_bag
   *   The PMMISSOPropertyBag of the current login cycle.
   */
  public function __construct(PMMISSOPropertyBag $sso_property_bag) {
    $this->ssoPropertyBag = $sso_property_bag;
  }

  /**
   * PMMISSOPropertyBag getter.
   *
   * @return \Drupal\pmmi_sso\PMMISSOPropertyBag
   *   The ssoPropertyBag property.
   */
  public function getSsoPropertyBag() {
    return $this->ssoPropertyBag;
  }

  /**
   * Assign a different username to the Drupal account that is to be login.
   *
   * @param string $username
   *   The username.
   */
  public function setDrupalUsername($username) {
    $this->ssoPropertyBag->setUsername($username);
  }

}
