<?php

namespace Drupal\pmmi_training_provider;

use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;
use Drupal\pmmi_sales_agent\SADUserStatHtmlRouteProvider;

/**
 * Provides routes for Sales agent user stat entities.
 *
 * @see Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class TPDReportHtmlRouteProvider extends SADUserStatHtmlRouteProvider {

  /**
   * Gets the collection route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('collection') && $entity_type->hasListBuilderClass()) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('collection'));
      $route
        ->setDefaults([
          '_entity_list' => $entity_type_id,
          '_title' => "{$entity_type->getLabel()} list",
        ])
        ->setRequirement('_permission', 'access training provider report overview')
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

}
