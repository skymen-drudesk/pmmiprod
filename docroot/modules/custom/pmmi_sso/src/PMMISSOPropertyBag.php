<?php

namespace Drupal\pmmi_sso;

/**
 * Class PMMISSOPropertyBag.
 */
class PMMISSOPropertyBag {

  /**
   * The Raw User ID of the PMMI SSO user.
   *
   * @var string
   */
  protected $rawUserId;

  /**
   * The MasterCustomerId of the PMMI SSO user.
   *
   * @var string
   */
  protected $userId;

  /**
   * The SubCustomerId of the PMMI SSO user.
   *
   * @var string
   */
  protected $subCustomerId;

  /**
   * The username of the PMMI SSO user.
   *
   * @var string
   */
  protected $username;

  /**
   * The user token.
   *
   * @var string
   */
  protected $token;

  /**
   * An array containing attributes returned from the server.
   *
   * @var array
   */
  protected $attributes;

  /**
   * Contructor.
   *
   * @param string $user_id
   *   The MasterCustomerId of the PMMI SSO user.
   */
  public function __construct($user_id = NULL) {
    $this->userId = $user_id;
  }

  /**
   * Username property setter.
   *
   * @param string $user
   *   The new username.
   */
  public function setUsername($user) {
    $this->username = $user;
  }

  /**
   * UserID property setter.
   *
   * @param string $user_id
   *   The new user_id.
   */
  public function setUserId($user_id) {
    $this->userId = $user_id;
  }

  /**
   * SubCustomerId property setter.
   *
   * @param string $subcustomer_id
   *   The new subcustomer_id.
   */
  public function setSubCustomerId($subcustomer_id) {
    $this->subCustomerId = $subcustomer_id;
  }

  /**
   * Proxy granting token property setter.
   *
   * @param string $token
   *   The token to set as pgt.
   */
  public function setToken($token) {
    $this->token = $token;
  }

  /**
   * Attributes property setter.
   *
   * @param array $sso_attributes
   *   An associative array containing attribute names as keys.
   */
  public function setAttributes(array $sso_attributes) {
    $this->attributes = $sso_attributes;
  }

  /**
   * Username property getter.
   *
   * @return string
   *   The username property.
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * UserID property getter.
   *
   * @return string $user_id
   *   The user_id property.
   */
  public function getUserId() {
    return $this->userId;
  }

  /**
   * SubCustomerId property getter.
   *
   * @return string $subcustomer_id
   *   The subcustomer_id property.
   */
  public function getSubCustomerId() {
    return $this->subCustomerId;
  }

  /**
   * UserID property getter.
   *
   * @return string $user_id
   *   The raw_user_id property.
   */
  public function getRawUserId() {
    return $this->userId . '|' . $this->subCustomerId;
  }

  /**
   * Token getter.
   *
   * @return string
   *   The token property.
   */
  public function getToken() {
    return $this->token;
  }

  /**
   * PMMI SSO attributes getter.
   *
   * @return array
   *   The attributes property.
   */
  public function getAttributes() {
    return $this->attributes;
  }

}
