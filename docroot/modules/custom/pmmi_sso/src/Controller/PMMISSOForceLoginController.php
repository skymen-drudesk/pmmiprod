<?php

namespace Drupal\pmmi_sso\Controller;

use Drupal\pmmi_sso\PMMISSORedirectData;
use Drupal\pmmi_sso\PMMISSORedirectResponse;
use Drupal\pmmi_sso\Service\PMMISSORedirector;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class PMMISSOForceLoginController.
 */
class PMMISSOForceLoginController implements ContainerInjectionInterface {
  /**
   * The PMMI SSO helper to get config settings from.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSORedirector
   */
  protected $ssoRedirector;

  /**
   * Used to get query string parameters from the request.
   *
   * @var RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   *
   * @param PMMISSORedirector $sso_redirector
   *   The PMMISSO Redirector service.
   * @param RequestStack $request_stack
   *   Symfony request stack.
   */
  public function __construct(PMMISSORedirector $sso_redirector, RequestStack $request_stack) {
    $this->ssoRedirector = $sso_redirector;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('pmmi_sso.redirector'), $container->get('request_stack'));
  }

  /**
   * Handles a page request for our forced login route.
   */
  public function forceLogin() {
    // @todo: What if PMMISSO is not configured? need to handle that case.

    // Check referer is an external site.
    $request = $this->requestStack->getCurrentRequest();
    $referer_host = $request->headers->get('referer');
    $referer = preg_replace("(^https?://)", "", $referer_host );
    // 'https://pmmi.com' or 'http://pmmi.com/'
    $base = $request->getHttpHost() . $request->getBaseUrl();
    // Example '/about?er=343'.
    $path = preg_replace('/^' . preg_quote($base, '/') . '/', '', $referer);
    $external = $path === $referer ? TRUE : FALSE;

    $sso_redirect_data = new PMMISSORedirectData();
    // Set return URI for redirect after Login to SSO.
    if ($external) {
      return new PMMISSORedirectResponse('/');
    }
    else {
      $sso_redirect_data->setServiceParameter('returnto', $path);
      $sso_redirect_data->setIsCacheable(FALSE);
      return $this->ssoRedirector->buildRedirectResponse($sso_redirect_data, TRUE);
    }
  }

}
