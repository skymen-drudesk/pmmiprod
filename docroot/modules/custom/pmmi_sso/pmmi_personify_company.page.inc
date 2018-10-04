<?php

/**
 * @file
 * Contains pmmi_personify_company.page.inc.
 *
 * Page callback for Personify company entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Personify company templates.
 *
 * Default template: pmmi_personify_company.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_pmmi_personify_company(array &$variables) {
  // Fetch PMMIPersonifyCompany Entity Object.
  $pmmi_personify_company = $variables['elements']['#pmmi_personify_company'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
