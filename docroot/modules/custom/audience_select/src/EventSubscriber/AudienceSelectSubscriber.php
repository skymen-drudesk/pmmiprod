<?php

namespace Drupal\audience_select\EventSubscriber;

use Drupal\audience_select\Service\AudienceManager;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Audience request - response subscriber.
 *
 * Subscribe to KernelEvents::REQUEST and RESPONSE events and redirect to
 * gateway page if audience_select_audience cookie is not set.
 */
class AudienceSelectSubscriber implements EventSubscriberInterface {

  /**
   * The audience manager service.
   *
   * @var \Drupal\audience_select\Service\AudienceManager
   */
  protected $AudienceManager;

  /**
   * An alias manager to find the alias for the current system path.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs a new CurrentUserContext.
   *
   *   The plugin implementation definition.
   *
   * @param \Drupal\audience_select\Service\AudienceManager $audience_manager
   *   The Audience Manager.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   An alias manager to find the alias for the current system path.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher service.
   */
  public function __construct(AudienceManager $audience_manager, AliasManagerInterface $alias_manager, PathMatcherInterface $path_matcher) {
    $this->AudienceManager = $audience_manager;
    $this->aliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;

  }

  /**
   * Checks for audience_select_audience cookie and redirects to gateway page.
   *
   * Checks for audience_select_audience cookie and redirects to /gateway if not
   * set when the KernelEvents::REQUEST event is dispatched. If audience query
   * parameter exists, sets audience_select_audience cookie.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function checkForRedirection(GetResponseEvent $event) {
    // Get a clone of the request. During inbound processing the request
    // can be altered. Allowing this here can lead to unexpected behavior.
    // For example the path_processor.files inbound processor provided by
    // the system module alters both the path and the request; only the
    // changes to the request will be propagated, while the change to the
    // path will be lost.
    $request = clone $event->getRequest();
    $request_uri = $request->getPathInfo();
    // Filter Sub-request and POST.
    if (!$event->isMasterRequest() || $request->isMethod('POST')) {
      return;
    }

    // Get audience if query parameter exists.
    if ($request->query->has('audience')) {
      $audience = $request->query->get('audience');
    }

    // Get gateway page URL.
    $gateway_page_url = $this->AudienceManager->getGateway();
    $gateway_page = $request_uri == $gateway_page_url ? TRUE : FALSE;

    $audience_cache = new CacheableMetadata();

    // Check if User Agent is bot or Crawler.
    if ($this->AudienceManager->isCrawler() && $gateway_page) {
      $crawler_audience = $this->AudienceManager->getCrawlerAudience();
      $audience_data = AudienceManager::load($crawler_audience);
      $redirect_url = Url::fromUri($audience_data['audience_redirect_url'])
        ->toString();
      $response = new TrustedRedirectResponse($redirect_url);
      $audience_cache->setCacheMaxAge(Cache::PERMANENT);
      $audience_cache->setCacheContexts(['audience']);
      $audience_cache->setCacheTags(['audience:' . $crawler_audience]);
      $response->addCacheableDependency($audience_cache);
      $event->setResponse($response);
      return;
    }
    elseif ($this->AudienceManager->isCrawler() && !$gateway_page) {
      return;
    }

    $has_cookie = $request->cookies->has('audience_select_audience');
    $excluded = $this->excludedPages($request);

    // If audience_select_audience cookie is not set, redirect to gateway page.
    if (!$excluded && !$gateway_page && !$has_cookie && !isset($audience)) {
      if ($request_uri != '/' && !$request->query->has('dest')) {
        $gateway_page_url = $gateway_page_url . '?dest=' . $request_uri;
      }
      $response = new TrustedRedirectResponse($gateway_page_url);

      $audience_cache->setCacheMaxAge(0);
      $response->addCacheableDependency($audience_cache);
      $event->setResponse($response);
    }
    // If route is not gateway and have audience query parameter, set cookie.
    elseif (!$excluded && !$gateway_page && isset($audience)
    ) {
      $audience_data = AudienceManager::load($audience);
      $redirect_url = Url::fromUri($audience_data['audience_redirect_url'])
        ->toString();
      $response = new TrustedRedirectResponse($redirect_url);
      $audience_cache->setCacheMaxAge(0);
      $response->addCacheableDependency($audience_cache);
      // Set cookie without httpOnly, so that JavaScript can delete it.
      $cookie = new Cookie('audience_select_audience', $audience, time() + (86400 * 365), '/', NULL, FALSE, FALSE);
      $response->headers->setCookie($cookie);
      $event->setResponse($response);
    }

    // If audience_select_audience cookie is set and route is
    // /$gateway_page_url redirect to frontpage.
    elseif (!$excluded && $gateway_page && $has_cookie) {
      $response = new TrustedRedirectResponse('/');
      $exist_audience = $request->cookies->get('audience_select_audience');
      $audience_cache->setCacheContexts(['audience']);
      $audience_cache->setCacheTags(['audience:' . $exist_audience]);
      $response->addCacheableDependency($audience_cache);
      $event->setResponse($response);
    }

  }

  /**
   * Validate request path.
   *
   * @param Request $request
   *   The current request.
   *
   * @return bool
   *   Excluded page or not.
   */
  protected function excludedPages(Request $request) {
    $excluded_pages = (string) $this->AudienceManager->getConfig()
      ->get('excluded_pages');
    $path = $request->getPathInfo();
    $path = $path === '/' ? $path : rtrim($path, '/');
    $path_alias = Unicode::strtolower($this->aliasManager->getAliasByPath($path));
    if ($path != $path_alias) {
      $excluded = $this->pathMatcher->matchPath($path, $excluded_pages);
    }
    else {
      $excluded = $this->pathMatcher->matchPath($path_alias, $excluded_pages);
    }

    return $excluded;
  }

  /**
   * Sets the 'audience' cache tag on AudienceEvent responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $response = $event->getResponse();
    if (!$response instanceof CacheableResponseInterface) {
      return;
    }

    $request = $event->getRequest();
    // Filter excluded pages.
    $excluded = $this->excludedPages($request);
    $gateway_page_url = $this->AudienceManager->getGateway();
    $gateway_page = $request->getPathInfo() == $gateway_page_url ? TRUE : FALSE;
    if ($request->query->has('audience') || $request->isMethod('POST') || $excluded == TRUE || $gateway_page) {
      return;
    }

    $audience_cache = new CacheableMetadata();
    $audience_cache->addCacheContexts(['audience']);
    $audience_cache->setCacheTags(['audience:' . $this->AudienceManager->getCurrentAudience()]);
    $response->addCacheableDependency($audience_cache);

  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    // This needs to run before RouterListener::onKernelRequest(), which has
    // a priority of 32. Otherwise, that aborts the request if no matching
    // route is found.
    $events[KernelEvents::REQUEST][] = ['checkForRedirection', 33];
    // Priority 6, so that it runs before AnonymousUserResponseSubscriber, but
    // after event subscribers that add the associated cacheability metadata
    // (which have priority 10). This one is conditional, so must run after those.
    $events[KernelEvents::RESPONSE][] = ['onRespond', 6];
    return $events;
  }

}
