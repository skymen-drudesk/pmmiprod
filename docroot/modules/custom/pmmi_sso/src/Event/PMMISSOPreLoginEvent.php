<?php

namespace Drupal\pmmi_sso\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\pmmi_sso\PMMISSOPropertyBag;

/**
 * Class PMMISSOPreLoginEvent.
 *
 * PMMISSO dispatches this event during the authentication process after a local
 * Drupal user account has been loaded for the user attempting login, but
 * before the user is actually authenticated to Drupal.
 *
 * Subscribe to this event to:
 *  - Prevent the user from logging in by setting $allowLogin to FALSE.
 *  - Change properties on the Drupal user account (like adding or removing
 *    roles). The PMMI SSO module saves the user entity after dispatching the
 *    event, so subscribers do not need to save it themselves.
 *
 * Any PMMISSO attributes will be available via the $ssoPropertyBag data object.
 */
class PMMISSOPreLoginEvent extends Event {

  /**
   * Store the PMMI SSO property bag.
   *
   * @var \Drupal\pmmi_sso\PMMISSOPropertyBag
   */
  protected $ssoPropertyBag;

  /**
   * The drupal user entity about to be logged in.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * Controls whether or not the user will be allowed to login.
   *
   * @var bool
   */
  protected $allowLogin = TRUE;

  /**
   * Controls whether or not we need update the relationship to the company.
   *
   * Controls whether or not we need update the relationship beetween user and
   * company.
   *
   * @var bool
   */
  protected $updateCompanyFlag = FALSE;

  /**
   * Related companies with current user.
   *
   * @var array
   */
  protected $companiesId = [];

  /**
   * Constructor.
   *
   * @param \Drupal\user\UserInterface $account
   *   The drupal user entity about to be logged in.
   * @param \Drupal\pmmi_sso\PMMISSOPropertyBag $sso_property_bag
   *   The PMMISSOPropertyBag of the current login cycle.
   */
  public function __construct(UserInterface $account, PMMISSOPropertyBag $sso_property_bag) {
    $this->account = $account;
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
   * Return the user account entity.
   *
   * @return \Drupal\user\UserInterface
   *   The user account entity.
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * Set the $allowLogin property.
   *
   * @param bool $allow_login
   *   TRUE to allow login, FALSE otherwise.
   */
  public function setAllowLogin($allow_login) {
    if ($allow_login) {
      $this->allowLogin = TRUE;
    }
    else {
      $this->allowLogin = FALSE;
    }
  }

  /**
   * Return if this user is allowed to login.
   *
   * @return bool
   *   TRUE if the user is allowed to login, FALSE otherwise.
   */
  public function getAllowLogin() {
    return $this->allowLogin;
  }

  /**
   * Set the $updateCompanyFlag property.
   *
   * @param bool $updateCompanyFlag
   *   TRUE need update, FALSE otherwise.
   */
  public function setUpdateCompanyFlag($updateCompanyFlag) {
    $this->updateCompanyFlag = $updateCompanyFlag ? TRUE : FALSE;
  }

  /**
   * Return if this user is need to update relationship.
   *
   * @return bool
   *   TRUE if the user is  need to update, FALSE otherwise.
   */
  public function getUpdateCompanyFlag() {
    return $this->updateCompanyFlag;
  }

  /**
   * Sets the IDs of the related companies.
   *
   * @param array $companies_id
   *   Array of company IDs.
   */
  public function setCompanies(array $companies_id) {
    $this->companiesId = $companies_id;
  }

  /**
   * Return an array of related companies IDs.
   *
   * @return array
   *   Array of company IDs.
   */
  public function getCompanies() {
    return $this->companiesId;
  }

}
