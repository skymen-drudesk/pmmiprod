<?php

namespace Drupal\pmmi\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Override default batch controller to allow display additional page
    // components (e.g. header).
    $batch_route = $collection->get('system.batch_page.html');
    if ($batch_route) {
      $batch_route->setDefaults([
        '_controller' => '\Drupal\pmmi\Controller\BatchController::batchPage',
        '_title_callback' => '\Drupal\pmmi\Controller\BatchController::batchPageTitle',
      ])->setOptions(['_admin_route' => FALSE]);
    }
  }
}
