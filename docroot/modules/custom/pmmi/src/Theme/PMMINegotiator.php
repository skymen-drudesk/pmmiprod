<?php

namespace Drupal\pmmi\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\RouteMatchInterface;

class PMMINegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $status = FALSE;

    // Use 'PMMI' theme for the company create/edit pages.
    if ($route_match->getRouteName() == 'node.add' && $route_match->getParameter('node_type')->id() == 'company') {
      $status = TRUE;
    }
    elseif ($route_match->getRouteName() == 'entity.node.edit_form') {
      $node = $route_match->getParameter('node');
      if ($node->getType() == 'company') {
        $status = TRUE;
      }
    }

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    // Here you return the actual theme name.
    return 'pmmi_bootstrap';
  }
}
