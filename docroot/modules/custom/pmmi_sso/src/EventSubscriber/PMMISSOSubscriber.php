<?php

namespace Drupal\pmmi_sso\EventSubscriber;

use Drupal\pmmi\CrawlerDetectInterface;
use Drupal\pmmi_sso\PMMISSORedirectData;
use Drupal\pmmi_sso\Service\PMMISSORedirector;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Condition\ConditionManager;
use Drupal\pmmi_sso\Service\PMMISSOHelper;

/**
 * Provides a PMMISSOSubscriber.
 */
class PMMISSOSubscriber extends HttpExceptionSubscriberBase {

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Route matcher object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatcher;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Condition manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * PMMI SSO helper.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSOHelper
   */
  protected $ssoHelper;

  /**
   * PMMI SSO Redirector.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSORedirector
   */
  protected $ssoRedirector;

  /**
   * The default web crawler detection object.
   *
   * @var \Drupal\pmmi\CrawlerDetectInterface
   */
  protected $crawlerDetect;

  /**
   * Frequency to check for gateway login.
   *
   * @var int
   */
  protected $gatewayCheckFrequency;

  /**
   * Frequency to check validation of a token.
   *
   * @var array
   */
  protected $tokenCheckFrequency;

  /**
   * The token action after validation.
   *
   * @var array
   */
  protected $tokenAction;

  /**
   * Paths to check for gateway login.
   *
   * @var array
   */
  protected $gatewayPaths = [];

  /**
   * Constructs a new PMMISSOSubscriber.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_matcher
   *   The route matcher.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition manager.
   * @param \Drupal\pmmi_sso\Service\PMMISSOHelper $sso_helper
   *   The PMMI SSO Helper service.
   * @param PMMISSORedirector $sso_redirector
   *   The PMMI SSO Redirector Service.
   * @param \Drupal\pmmi\CrawlerDetectInterface $crawler_detect
   *   The default web crawler detection object.
   */
  public function __construct(
    RequestStack $request_stack,
    RouteMatchInterface $route_matcher,
    AccountInterface $current_user,
    ConditionManager $condition_manager,
    PMMISSOHelper $sso_helper,
    PMMISSORedirector $sso_redirector,
    CrawlerDetectInterface $crawler_detect
  ) {
    $this->requestStack = $request_stack;
    $this->routeMatcher = $route_matcher;
    $this->currentUser = $current_user;
    $this->conditionManager = $condition_manager;
    $this->ssoHelper = $sso_helper;
    $this->ssoRedirector = $sso_redirector;
    $this->ssoRedirector = $sso_redirector;
    $this->crawlerDetect = $crawler_detect;
    $this->gatewayCheckFrequency = $sso_helper->getGatewayFrequency();
    $this->tokenCheckFrequency = $sso_helper->getTokenFrequency();
    $this->tokenAction = $sso_helper->getTokenAction();
    $this->gatewayPaths = $sso_helper->getGatewayPaths();
  }

  /**
   * The entry point for our subscriber.
   *
   * @param GetResponseEvent $event
   *   The response event from the kernel.
   */
  public function handle(GetResponseEvent $event) {
    // Don't do anything if this is a sub request and not a master request.
    if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
      return;
    }

    // Some routes we don't want to run on.
    if ($this->isIgnoreableRoute()) {
      return;
    }

    // The service controller may have indicated that this current request
    // should not be automatically sent to PMMI SSO for authentication checking.
    // This is to prevent infinite redirect loops.
    $session = $this->requestStack->getCurrentRequest()->getSession();
    if ($session && $session->has('sso_temp_disable_auto_auth')) {
      $session->remove('sso_temp_disable_auto_auth');
      $this->ssoHelper->log("Temp disable flag set, skipping PMMI SSO subscriber.");
      return;
    }

    $return_to = $this->requestStack->getCurrentRequest()->getUri();
    $redirect_data = new PMMISSORedirectData(['returnto' => $return_to]);

    // Additional check if the user is already logged in.
    if ($this->currentUser->isAuthenticated()) {
      if ($session->has('expiration') && $this->handleTokenPage()) {
        // If token expired, redirect to internal uri /ssoservice
        // to validate token.
        if ($session->get('expiration') > time()) {
          $redirect_data->preventRedirection();
        }
        else {
          $redirect_data->forceRedirection();
          $redirect_data->setServiceParameter('cti', $session->get('uid'));
          $redirect_data->setParameter('token_expired', TRUE);
          $redirect_data->setParameter('token_action', $this->tokenAction);
          $this->ssoHelper->log('Token expired: redirect user.');
        }
      }
      else {
        $redirect_data->preventRedirection();
      }
    }
    else {
      // Default assumption is that we don't want to redirect unless page
      // critera matches.
      $redirect_data->preventRedirection();

      // Check to see if we should initiate a gateway auth check.
      if ($this->handleGateway()) {
        $redirect_data->setParameter('gateway', TRUE);
        $this->ssoHelper->log('Gateway Login Requested');
        $redirect_data->forceRedirection();
      };
    }

    // If we're still going to redirect, lets do it.
    $response = $this->ssoRedirector->buildRedirectResponse($redirect_data);
    if ($response) {
      $event->setResponse($response);
    }
  }

  /**
   * Check if the current path is a gateway path.
   *
   * @return bool
   *   TRUE if current path is a gateway path, FALSE otherwise.
   */
  private function handleGateway() {
    // Don't do anything if this feature is disabled.
    if ($this->gatewayCheckFrequency === PMMISSOHelper::CHECK_NEVER) {
      return FALSE;
    }

    // Don't do anything if this is a request from cron, drush, crawler, etc.
    if ($this->crawlerDetect->isCrawler()) {
      return FALSE;
    }

    // Only implement gateway feature for GET requests, to prevent users from
    // being redirected to PMMI SSO server for things like form submissions.
    if (!$this->requestStack->getCurrentRequest()->isMethod('GET')) {
      return FALSE;
    }

    // If set to only implement gateway once per session, we use a session
    // variable to store the fact that we've already done the gateway check
    // so we don't keep doing it.
    if ($this->gatewayCheckFrequency === PMMISSOHelper::CHECK_ONCE) {
      // If the session var is already set, we know to back out.
      if ($this->requestStack->getCurrentRequest()
        ->getSession()
        ->has('sso_gateway_checked')
      ) {
        $this->ssoHelper->log("Gateway already checked, will not check again.");
        return FALSE;
      }
      $this->requestStack->getCurrentRequest()
        ->getSession()
        ->set('sso_gateway_checked', TRUE);
    }

    return $this->checkCondition();
  }

  /**
   * Check is the current path is restricted by a token and feature enabled.
   *
   * @return bool
   *   TRUE if current path is a restricted by token path, FALSE otherwise.
   */
  private function handleTokenPage() {
    // Don't do anything if this feature is disabled.
    if ($this->tokenCheckFrequency === PMMISSOHelper::TOKEN_DISABLED) {
      return FALSE;
    }

    // Only implement token feature for GET requests, to prevent users from
    // being redirected to PMMI SSO server for things like form submissions.
    if (!$this->requestStack->getCurrentRequest()->isMethod('GET')) {
      return FALSE;
    }

    // Don't do anything if this is a request from cron, drush, crawler, etc.
    if ($this->crawlerDetect->isCrawler()) {
      return FALSE;
    }

    // User can indicate specific paths to enable (or disable) token check mode.
    return $this->checkCondition();
  }

  /**
   * Check if the current path is restricted by a token.
   *
   * @return bool
   *   TRUE if current path is a restricted by token path, FALSE otherwise.
   */
  private function checkCondition() {
    // User can indicate specific paths to enable (or disable) token check mode.
    $condition = $this->conditionManager->createInstance('request_path');
    $condition->setConfiguration($this->gatewayPaths);
    return $this->conditionManager->execute($condition);
  }

  /**
   * Checks current request route against a list of routes we want to ignore.
   *
   * @return bool
   *   TRUE if we should ignore this request, FALSE otherwise.
   */
  private function isIgnoreableRoute() {
    $routes_to_ignore = [
      'pmmi_sso.service',
      'pmmi_sso.login',
      'system.cron',
    ];

    $current_route = $this->routeMatcher->getRouteName();
    if (in_array($current_route, $routes_to_ignore)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Handle 403 errors.
   *
   * Other request subscribers with a higher priority may intercept the request
   * and return a 403 before our request subscriber can handle it. In those
   * instances we handle the forced login redirect if applicable here instead,
   * using an exception subscriber.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on403(GetResponseForExceptionEvent $event) {
    if ($this->currentUser->isAnonymous()) {
      $return_to = $this->requestStack->getCurrentRequest()->getUri();
      $redirect_data = new PMMISSORedirectData(['returnto' => $return_to]);
      $redirect_data->preventRedirection();

      // If we're still going to redirect, lets do it.
      $response = $this->ssoRedirector->buildRedirectResponse($redirect_data);
      if ($response) {
        $event->setResponse($response);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Priority is just before the Dynamic Page Cache subscriber, but after
    // important services like route matcher and maintenance mode subscribers.
    $events[KernelEvents::REQUEST][] = ['handle', 29];
    $events[KernelEvents::EXCEPTION][] = ['onException', 0];
    return $events;
  }

}
