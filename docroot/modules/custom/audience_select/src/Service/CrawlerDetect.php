<?php

namespace Drupal\audience_select\Service;

use Drupal\pmmi\CrawlerDetectInterface;
use Jaybizzle\CrawlerDetect\CrawlerDetect as BaseCrawlerDetect;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class extending the BaseCrawlerDetect to adapt it for Symfony.
 */
class CrawlerDetect extends BaseCrawlerDetect implements CrawlerDetectInterface {

  /**
   * The related request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Initialise the BaseCrawlerDetect from the $requestStack.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    if ($request = $request_stack->getMasterRequest()) {
      // The app is accessed by a HTTP request.
      $headers = $request->server->all();
      $userAgent = $request->headers->get('User-Agent');
    }
    else {
      // The app is accessed by the CLI.
      $headers = $userAgent = NULL;
    }
    parent::__construct($headers, $userAgent);
  }

}
