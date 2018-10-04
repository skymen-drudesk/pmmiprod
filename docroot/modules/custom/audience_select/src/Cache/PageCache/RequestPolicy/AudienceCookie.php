<?php

namespace Drupal\audience_select\Cache\PageCache\RequestPolicy;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Symfony\Component\HttpFoundation\Request;

/**
 * Reject when audience cookie is not set.
 */
class AudienceCookie implements RequestPolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    if ($request->headers->has('user-agent')) {
      $crawler = new CrawlerDetect();
      $is_crawler = $crawler->isCrawler($request->headers->get('user-agent'));
      if ($is_crawler) {
        $crawler_audience = \Drupal::config('audience_select.settings')->get('default_bot_audience');
        $request->headers->set('crawler', $crawler_audience);
      }
    }
    if (!$request->cookies->has('audience_select_audience') || $request->query->has('audience')) {
      if (!isset($is_crawler) || !$is_crawler) {
        return static::DENY;
      }
    }
  }

}
