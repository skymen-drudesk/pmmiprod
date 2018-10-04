<?php

namespace Drupal\pmmi_forms\Routing;

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
    if ($route = $collection->get('entity.webform_options.collection')) {
      $route->setRequirement('_permission', 'create webform');
    }

    // Disable admin_route for submissions html and table views.
    $submission_views = [
      'entity.webform_submission.canonical',
      'entity.webform_submission.table',
    ];
    foreach ($submission_views as $view) {
      if ($submission_route = $collection->get($view)) {
        $submission_route->setOptions(['_admin_route' => FALSE]);
      }
    }
  }

}
