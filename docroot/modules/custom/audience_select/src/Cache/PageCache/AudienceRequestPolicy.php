<?php

namespace Drupal\audience_select\Cache\PageCache;

use Drupal\audience_select\Cache\PageCache\RequestPolicy\AudienceCookie;
use Drupal\Core\PageCache\ChainRequestPolicy;

/**
 * The default page cache request policy.
 *
 * Delivery of cached pages is denied if audience cookie is not set.
 */
class AudienceRequestPolicy extends ChainRequestPolicy {

  /**
   * Constructs the default page cache request policy.
   */
  public function __construct() {
    $this->addPolicy(new AudienceCookie());
  }

}
