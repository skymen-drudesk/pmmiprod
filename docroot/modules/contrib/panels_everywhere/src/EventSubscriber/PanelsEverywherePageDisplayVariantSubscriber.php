<?php

namespace Drupal\panels_everywhere\EventSubscriber;

use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Display\ContextAwareVariantInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\PageDisplayVariantSelectionEvent;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Render\RenderEvents;
use Drupal\page_manager\Entity\PageVariant;
use Drupal\page_manager\Entity\PageVariantAccess;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Selects the appropriate page display variant from 'site_template'.
 */
class PanelsEverywherePageDisplayVariantSubscriber implements EventSubscriberInterface {

  use ConditionAccessResolverTrait;

  /**
   * The event-derived route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The event-derived route object.
   *
   * @var \Symfony\Component\Routing\Route
   */
  protected $route;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Constructs a new PageManagerRoutes.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityStorage = $entity_type_manager->getStorage('page');
  }

  /**
   * Selects the page display variant.
   *
   * @param \Drupal\Core\Render\PageDisplayVariantSelectionEvent $event
   *   The event to process.
   */
  public function onSelectPageDisplayVariant(PageDisplayVariantSelectionEvent $event) {
    $this->routeMatch = $event->getRouteMatch();
    $this->route = $this->routeMatch->getRouteObject();

    if (
      // if this is an admin path, do not process it
      $this->route->getOption('_admin_route')
    ) {
      return;
    } elseif ($variant = $this->getVariant()) {
      $event->setPluginId($variant->getPluginId());
      $event->setPluginConfiguration($variant->getConfiguration());
      $event->setContexts($variant->getContexts());
      $event->stopPropagation();
    }
  }

  /**
   * Copied from VariantRouteFilter.php
   *
   * Checks access of a page variant.
   *
   * @param string $variant
   *   The page varian.
   *
   * @return bool
   *   TRUE if the route is valid, FALSE otherwise.
   */
  protected function checkVariantAccess($variant) {
    try {
      $access = $variant && $variant->access('view');
    }
    // Since access checks can throw a context exception, consider that as
    // a disallowed variant.
    catch (ContextException $e) {
      $access = FALSE;
    }

    return $access;
  }

  /**
   * get the display variant for this route, if it exists.
   *
   * @return \Drupal\page_manager\Entity\Page|null
   *   A page object. NULL if no matching page is found.
   */
  protected function getVariant() {
    $page = NULL;

    // pass 1 - try getting the page using the overridable getPageEntity function
    $routeObject = $this->routeMatch->getRouteObject();
    if ($routeObject) {
      $pageID = $routeObject->getDefault('page_id');
      if ($pageID) {
        $page = $this->entityStorage->load($pageID);
      }
    }

    // pass 2 - use the global "Site Template" page
    if (!is_object($page)) {
      $page = $this->entityStorage->load('site_template');
    }

    if (is_object($page) && $page->get('status')) {
      // return the first variant which selects for this context
      foreach ($page->getVariants() as $variant) {
        if (!$this->checkVariantAccess($variant)) {
          continue;
        }

        return $variant->getVariantPlugin();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[RenderEvents::SELECT_PAGE_DISPLAY_VARIANT][] = ['onSelectPageDisplayVariant'];
    return $events;
  }

}
