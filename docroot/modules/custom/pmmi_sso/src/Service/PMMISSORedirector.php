<?php

namespace Drupal\pmmi_sso\Service;

use Drupal\pmmi_sso\PMMISSORedirectData;
use Drupal\pmmi_sso\PMMISSORedirectResponse;
use Drupal\pmmi_sso\Event\PMMISSOPreRedirectEvent;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class PMMISSORedirector.
 */
class PMMISSORedirector {

  /**
   * The PMMISSOHelper.
   *
   * @var PMMISSOHelper
   */
  protected $ssoHelper;

  /**
   * The EventDispatcher.
   *
   * @var EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * PMMISSORedirector constructor.
   *
   * @param \Drupal\pmmi_sso\Service\PMMISSOHelper $sso_helper
   *   The PMMISSOHelper service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The EventDispatcher service.
   */
  public function __construct(PMMISSOHelper $sso_helper, EventDispatcherInterface $event_dispatcher) {
    $this->ssoHelper = $sso_helper;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Determine login URL response.
   *
   * @param PMMISSORedirectData $data
   *   Data used to generate redirector.
   * @param bool $force
   *   True implies that you always want to generate a redirector as occurs with
   *   the ForceRedirectController. False implies redirector is controlled by
   *   the allow_redirect property in the PMMISSORedirectData object.
   *
   * @return TrustedRedirectResponse|PMMISSORedirectResponse|null
   *   The RedirectResponse or NULL if a redirect shouldn't be done.
   */
  public function buildRedirectResponse(PMMISSORedirectData $data, $force = FALSE) {
    $response = NULL;

    // Dispatch an event that allows modules to alter or prevent the redirect.
    $pre_redirect_event = new PMMISSOPreRedirectEvent($data);
    $this->eventDispatcher->dispatch(PMMISSOHelper::EVENT_PRE_REDIRECT, $pre_redirect_event);

    // $force implies we are on the /ssologin url, so we always want to redirect
    // and data is always not cacheable.
    if ($force) {
      // Generate login url.
      $login_url = $this->ssoHelper->generateLoginUrl($data->getServiceParameter('returnto'));
      return new PMMISSORedirectResponse($login_url);
    }

    if ($data->willRedirect()) {

      if ($data->getParameter('token_expired') == TRUE) {
        $service_url = $this->ssoHelper->generateSsoServiceUrl($data->getAllServiceParameters());
        return new PMMISSORedirectResponse($service_url);
      }

      $login_url = $this->ssoHelper->generateLoginUrl($data->getServiceParameter('returnto'));
      return new PMMISSORedirectResponse($login_url);

    }
    return $response;
  }

}
