<?php

/**
 * @file
 * Contains pmmi_company_contact.page.inc..
 *
 * Page callback for Company contact entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Company contact templates.
 *
 * Default template: pmmi_company_contact.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_pmmi_company_contact(array &$variables) {
  // Fetch PMMICompanyContactEntity Entity Object.
  $pmmi_company_contact = $variables['elements']['#pmmi_company_contact'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
