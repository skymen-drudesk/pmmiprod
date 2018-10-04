<?php

/**
 * @file
 * Contains sad_user_stat.page.inc.
 *
 * Page callback for Sales agent user stat entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Sales agent user stat templates.
 *
 * Default template: sad_user_stat.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_sad_user_stat(array &$variables) {
  // Fetch SADUserStat Entity Object.
  $sad_user_stat = $variables['elements']['#sad_user_stat'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
