<?php

namespace Drupal\pmmi_sso\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\pmmi_sso\PMMISSOPropertyBag;

/**
 * Class PMMISSOPreRegisterEvent.
 *
 * The PMMI SSO module dispatches this event during the authentication process
 * just before a user is automatically registered to Drupal if:
 *  - Automatic user registration is enabled in the PMMI SSO module settings.
 *  - No existing Drupal account can be found that's associated with the
 *    PMMI SSO username of the user attempting authentication.
 *
 * Subscribers to this event can:
 *  - Prevent a Drupal account from being created for this user (thereby also
 *    preventing the user from logging in).
 *  - Change the username that will be assigned to the Drupal account. By
 *    default it is the same as the PMMI SSO username.
 *  - Set properties on the user account that will be created, like user roles
 *    or a custom first name field (for example by populating it with data from
 *    the PMMI SSO attributes available in $ssoPropertyBag).
 *
 * Any PMMI SSO attributes will be available via the $ssoPropertyBag data
 * object.
 */
class PMMISSOPreRegisterEvent extends Event {

  /**
   * The user information returned from the PMMI SSO server.
   *
   * @var \Drupal\pmmi_sso\PMMISSOPropertyBag
   */
  protected $ssoPropertyBag;

  /**
   * Determines if this user will be allowed to auto-register or not.
   *
   * @var bool
   */
  protected $allowAutomaticRegistration = TRUE;

  /**
   * The SSO LabelName.
   *
   * @var string
   */
  protected $ssoLabelName;

  /**
   * The username that will be assigned to the Drupal user account.
   *
   * By default this will be populated with the PMMI DataService LabelName.
   *
   * @var string
   */
  protected $drupalUsername;

  /**
   * The Drupal roles that will be assigned to the SSO user account.
   *
   * @var array
   */
  protected $drupalRoles = [];

  /**
   * An array of property values to assign to the user account on registration.
   *
   * @var array
   */
  protected $propertyValues = [];


  /**
   * An array of AuthData values to assign to the user account on registration.
   *
   * @var array
   */
  protected $authData = [];

  /**
   * Contructor.
   *
   * @param \Drupal\pmmi_sso\PMMISSOPropertyBag $sso_property_bag
   *   The PMMISSOPropertyBag for context.
   */
  public function __construct(PMMISSOPropertyBag $sso_property_bag) {
    $this->ssoPropertyBag = $sso_property_bag;
    $this->drupalUsername = $sso_property_bag->getUsername();
  }

  /**
   * Return the PMMISSOPropertyBag of the event.
   *
   * @return \Drupal\pmmi_sso\PMMISSOPropertyBag
   *   The $ssoPropertyBag property.
   */
  public function getSsoPropertyBag() {
    return $this->ssoPropertyBag;
  }

  /**
   * Return the Token of the user event.
   *
   * @return string
   *   The $token property.
   */
  public function getToken() {
    return $this->ssoPropertyBag->getToken();
  }

  /**
   * Return the UserId of the user event.
   *
   * @return string
   *   The $id property.
   */
  public function getUserId() {
    return $this->ssoPropertyBag->getUserId();
  }

  /**
   * Return the RAWUserId of the user event.
   *
   * @return string
   *   The $rawUserId property.
   */
  public function getRawUserId() {
    return $this->ssoPropertyBag->getRawUserId();
  }

  /**
   * Retrieve the PMMI DataService LabelName.
   *
   * @return string
   *   The LabelName.
   */
  public function getSsoLabelName() {
    return $this->ssoLabelName;
  }

  /**
   * Assign a PMMI DataService LabelName.
   *
   * @param string $label_name
   *   The LabelName.
   */
  public function setSsoLabelName($label_name) {
    $this->ssoLabelName = $label_name;
  }

  /**
   * Retrieve the username that will be assigned to the Drupal account.
   *
   * @return string
   *   The username.
   */
  public function getDrupalUsername() {
    return $this->drupalUsername;
  }

  /**
   * Assign a different username to the Drupal account that is to be registered.
   *
   * @param string $username
   *   The username.
   */
  public function setDrupalUsername($username) {
    $this->drupalUsername = $username;
  }

  /**
   * Retrieve the SSO role assigned to the Drupal account.
   *
   * @return array
   *   The roles.
   */
  public function getDrupalRoles() {
    return $this->drupalRoles;
  }

  /**
   * Assign a roles to the Drupal account that is to be registered.
   *
   * @param array $roles
   *   The roles.
   */
  public function setDrupalRoles(array $roles) {
    $this->drupalRoles = array_merge($this->drupalRoles, $roles);
  }

  /**
   * Sets the allow auto registration property.
   *
   * @param bool $allow_automatic_registration
   *   TRUE to allow auto registration, FALSE to deny it.
   */
  public function setAllowAutomaticRegistration($allow_automatic_registration) {
    if ($allow_automatic_registration) {
      $this->allowAutomaticRegistration = TRUE;
    }
    else {
      $this->allowAutomaticRegistration = FALSE;
    }
  }

  /**
   * Return if this user is allowed to be auto-registered or not.
   *
   * @return bool
   *   TRUE if the user is allowed to be registered, FALSE otherwise.
   */
  public function getAllowAutomaticRegistration() {
    return $this->allowAutomaticRegistration;
  }

  /**
   * Getter for propertyValues.
   *
   * @return array
   *   The user property values.
   */
  public function getPropertyValues() {
    return $this->propertyValues;
  }

  /**
   * Set a single property value for the user entity on registration.
   *
   * @param string $property
   *   The user entity property to set.
   * @param mixed $value
   *   The value of the property.
   */
  public function setPropertyValue($property, $value) {
    $this->propertyValues[$property] = $value;
  }

  /**
   * Getter data for the AuthMapData.
   *
   * @return array
   *   The AuthMap data.
   */
  public function getAuthData() {
    return $this->authData;
  }

  /**
   * Set a single data value for the AuthMapData on registration.
   *
   * @param string $key
   *   The user entity property to set.
   * @param mixed $value
   *   The value of the property.
   */
  public function setAuthData($key, $value) {
    $this->authData[$key] = $value;
  }

  /**
   * Set an array of property values for the user entity on registration.
   *
   * @param array $property_values
   *   The property values to set with each key corresponding to the property.
   */
  public function setPropertyValues(array $property_values) {
    $this->propertyValues = array_merge($this->propertyValues, $property_values);
  }

}
