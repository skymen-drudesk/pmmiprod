<?php

namespace Drupal\pmmi;

/**
 * Interface for the Crawler Service.
 */
interface CrawlerDetectInterface {

  /**
   * Check user agent string against the regex.
   *
   * @param string|null $userAgent
   *   The user agent.
   *
   * @return bool
   *   Detection result.
   */
  public function isCrawler($userAgent = NULL);

}
