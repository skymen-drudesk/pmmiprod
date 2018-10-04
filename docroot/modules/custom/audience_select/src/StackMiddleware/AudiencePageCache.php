<?php

namespace Drupal\audience_select\StackMiddleware;

use Drupal\page_cache\StackMiddleware\PageCache;
use Symfony\Component\HttpFoundation\Request;

/**
 * Executes the page caching before the main kernel takes over the request.
 */
class AudiencePageCache extends PageCache {

  /**
   * Gets the page cache ID for this request with Audience Cookie.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   *
   * @return string
   *   The cache ID for this request.
   */
  protected function getCacheId(Request $request) {
    $audience = $request->cookies->has('audience_select_audience') ? $request->cookies->get('audience_select_audience') : '';
    $audience = $request->headers->has('crawler') ? $request->headers->get('crawler') : $audience;
    $cid_parts = [
      $request->getUri(),
      $audience,
      $request->getRequestFormat(),
    ];
    return implode(':', $cid_parts);
  }

}
