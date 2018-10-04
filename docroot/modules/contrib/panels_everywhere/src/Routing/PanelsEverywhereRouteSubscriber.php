<?php

/**
 * @file
 * Contains \Drupal\panels_everywhere\Routing\PanelsEverywhereRouteSubscriber.
 */

namespace Drupal\panels_everywhere\Routing;

use Drupal\page_manager\Routing\PageManagerRoutes;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteCompiler;
use Drupal\page_manager\PageInterface;

/**
 * Associates a route with a Page Manager page, if it exists
 */
class PanelsEverywhereRouteSubscriber extends PageManagerRoutes {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityStorage->loadMultiple() as $entity_id => $entity) {

      // If the page is disabled or if it's not set to disable route override, skip processing it.
      if (
        !$entity->status() ||
        !$entity->getVariants() ||
        !$entity->getThirdPartySetting('panels_everywhere', 'disable_route_override')
      ) {
        continue;
      }

      if ($route = $this->getPageRoute($entity, $collection)) {
        $route->setDefault('page_id', $entity_id);
      }
    }
  }

  /**
   * Gets the overridden route.
   *
   * @param \Drupal\page_manager\PageInterface $entity
   *   The page entity.
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection.
   *
   * @return Symfony\Component\Routing\Route|null
   *   Either the route if this is overriding an existing path, or NULL.
   */
  protected function getPageRoute(PageInterface $entity, RouteCollection &$collection) {
    // Get the stored page path.
    $path = $entity->getPath();
    $overridden_route_name = NULL;

    // Loop through all existing routes to see if this is overriding a route.
    foreach ($collection->all() as $route_name => $route) {
      // Find all paths which match the path of the current display.
      $route_path = $route->getPath();
      $route_path_outline = RouteCompiler::getPatternOutline($route_path);

      if (
        // Match either the path or the outline, e.g., '/foo/{foo}' or '/foo/%'.
        ($path === $route_path || $path === $route_path_outline) &&
        // only remove Page Manager routes, not standalone ones
        $route->hasDefault('page_manager_page_variant')
      ) {
        $overridden_route_name = $route->getDefault('overridden_route_name');
        $collection->remove($route_name);
        break;
      }
    }

    if ($overridden_route_name) {
      return $collection->get($overridden_route_name);
    }
  }
}
