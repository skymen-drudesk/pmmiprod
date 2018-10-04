<?php

namespace Drupal\pmmi_sso\Event;

use Drupal\pmmi_sso\PMMISSORedirectData;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class PMMISSOPreRedirectEvent.
 *
 * Dispatches this event just before a user is redirected to the PMMI SSO server
 * for authentication.
 *
 * Subscribers of this event can:
 *  - Add query string parameters to the PMMI SSO server URL. This is useful if
 *    your PMMI SSO server requires extra data to be sent during authentication.
 *  - Add query string parameters to the "service URL" (the URL users are
 *    returned to after authenticating with the PMMI SSO server). This is useful
 *    if you want to pass data back to your Drupal site after the authentication
 *    process is completed.
 *  - Prevent the authentication redirect entirely. This is only applicable if
 *    the user was being redirected for a Forced Login or Gateway Login.
 *    Users that visit /ssologin (or /sso) will always be redirected to the
 *    PMMISSO server no matter what.
 *  - Indicate if the redirect to the PMMI SSO server is a cacheable redirect
 *    and if so, add cache tags and cache contexts to the redirect response.
 */
class PMMISSOPreRedirectEvent extends Event {

  /**
   * Data used to decide on final redirection.
   *
   * @var PMMISSORedirectData
   */
  protected $ssoRedirectData;

  /**
   * PMMISSOPreRedirectEvent constructor.
   *
   * @param \Drupal\pmmi_sso\PMMISSORedirectData $sso_redirect_data
   *   The redirect data object.
   */
  public function __construct(PMMISSORedirectData $sso_redirect_data) {
    $this->ssoRedirectData = $sso_redirect_data;
  }

  /**
   * Getter for $ssoRedirectData.
   *
   * @return \Drupal\pmmi_sso\PMMISSORedirectData
   *   The redirect data object.
   */
  public function getSsoRedirectData() {
    return $this->ssoRedirectData;
  }

}
