<?php

/**
 * @file
 * Contains pmmi_facebook.page.inc.
 *
 * Page callback for PMMI Facebook module.
 */

use Drupal\Core\Render\Element;


/**
 * Prepares variables for Process element.
 *
 * Default template: pmmi-facebook-block.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_pmmi_facebook_block(array &$variables){
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
