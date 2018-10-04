<?php

namespace Drupal\pmmi_sso\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining Access Token entities.
 *
 * @ingroup pmmi_sso
 */
interface PMMISSOTokenInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Revoke a token.
   */
  public function revoke();

  /**
   * Check if the token was revoked.
   *
   * @return bool
   *   TRUE if the token is revoked. FALSE otherwise.
   */
  public function isRevoked();

  /**
   * Set a token.
   *
   * @param string $token
   *   The token value.
   * @param int $expire
   *   The Unix timestamp when token expire.
   */
  public function setToken($token, $expire);

  /**
   * Return token value.
   *
   * @return string
   *   The token value.
   */
  public function getToken();

}
