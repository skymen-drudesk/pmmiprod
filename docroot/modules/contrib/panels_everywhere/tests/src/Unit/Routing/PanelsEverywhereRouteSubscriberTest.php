<?php

namespace Drupal\Tests\panels_everywhere\Unit;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Display\PageVariantInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\page_manager\PageInterface;
use Drupal\panels_everywhere\Routing\PanelsEverywhereRouteSubscriber;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\panels_everywhere\Routing\PanelsEverywhereRouteSubscriber
 * @group panels_everywhere
 */
class PanelsEverywhereRouteSubscriberTest extends UnitTestCase {

  /**
   * Tests that PanelsEverywhereRouteSubscriber does nothing if there are no
   * page entities.
   */
  public function testSubscriberDoesNothingForNoPageEntities() {
    // Given.
    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $routeCollection = $this->prophesize(RouteCollection::class);

    $event = $this->prophesize(RouteBuildEvent::class);
    $event->getRouteCollection()->willReturn($routeCollection->reveal());

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event->reveal());

    // Then.
    $routeCollection->remove(Argument::type('string'))->shouldNotHaveBeenCalled();
    $routeCollection->remove(Argument::type('array'))->shouldNotHaveBeenCalled();
    $routeCollection->get(Argument::type('string'))->shouldNotHaveBeenCalled();
  }

  /**
   * Test that PanelsEverywhereRouteSubscriber does nothing if there are no
   * enabled page entities.
   */
  public function testSubscriberDoesNothingForNoEnabledPageEntity() {
    // Given.
    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->status()->willReturn(FALSE);

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $routeCollection = $this->prophesize(RouteCollection::class);

    $event = $this->prophesize(RouteBuildEvent::class);
    $event->getRouteCollection()->willReturn($routeCollection->reveal());

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event->reveal());

    // Then.
    $routeCollection->remove(Argument::type('string'))->shouldNotHaveBeenCalled();
    $routeCollection->remove(Argument::type('array'))->shouldNotHaveBeenCalled();
    $routeCollection->get(Argument::type('string'))->shouldNotHaveBeenCalled();
  }

  /**
   * Test that PanelsEverywhereRouteSubscriber does nothing if there are no
   * variants on page entity.
   */
  public function testSubscriberDoesNothingForNoVariantsOnPageEntity() {
    // Given.
    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->status()->willReturn(TRUE);
    $pageEntity->getVariants()->willReturn([]);

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $routeCollection = $this->prophesize(RouteCollection::class);

    $event = $this->prophesize(RouteBuildEvent::class);
    $event->getRouteCollection()->willReturn($routeCollection->reveal());

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event->reveal());

    // Then.
    $routeCollection->remove(Argument::type('string'))->shouldNotHaveBeenCalled();
    $routeCollection->remove(Argument::type('array'))->shouldNotHaveBeenCalled();
    $routeCollection->get(Argument::type('string'))->shouldNotHaveBeenCalled();
  }

  /**
   * Test that PanelsEverywhereRouteSubscriber does nothing if there is no
   * disabled route override flag on page entity.
   */
  public function testSubscriberDoesNothingForNoDisableFlagOnPageEntity() {
    // Given.
    $pageVariant = $this->prophesize(PageVariantInterface::class);

    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->status()->willReturn(TRUE);
    $pageEntity->getVariants()->willReturn([ $pageVariant->reveal() ]);
    $pageEntity->getThirdPartySetting('panels_everywhere', 'disable_route_override')->willReturn(NULL);

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $routeCollection = $this->prophesize(RouteCollection::class);

    $event = $this->prophesize(RouteBuildEvent::class);
    $event->getRouteCollection()->willReturn($routeCollection->reveal());

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event->reveal());

    // Then.
    $routeCollection->remove(Argument::type('string'))->shouldNotHaveBeenCalled();
    $routeCollection->remove(Argument::type('array'))->shouldNotHaveBeenCalled();
    $routeCollection->get(Argument::type('string'))->shouldNotHaveBeenCalled();
  }

  /**
   * Test that PanelsEverywhereRouteSubscriber does nothing if the route
   * override flag is set to FALSE on page entity.
   */
  public function testSubscriberDoesNothingForInactiveDisableFlagOnPageEntity() {
    // Given.
    $pageVariant = $this->prophesize(PageVariantInterface::class);

    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->status()->willReturn(TRUE);
    $pageEntity->getVariants()->willReturn([ $pageVariant->reveal() ]);
    $pageEntity->getThirdPartySetting('panels_everywhere', 'disable_route_override')->willReturn(FALSE);

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $routeCollection = $this->prophesize(RouteCollection::class);

    $event = $this->prophesize(RouteBuildEvent::class);
    $event->getRouteCollection()->willReturn($routeCollection->reveal());

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event->reveal());

    // Then.
    $routeCollection->remove(Argument::type('string'))->shouldNotHaveBeenCalled();
    $routeCollection->remove(Argument::type('array'))->shouldNotHaveBeenCalled();
    $routeCollection->get(Argument::type('string'))->shouldNotHaveBeenCalled();
  }

  /**
   * Test that PanelsEverywhereRouteSubscriber does nothing if the collection
   * contains no entries and route override flag is set to TRUE on page
   * entity.
   */
  public function testSubscriberDoesNothingForActiveDisableFlagAndEmptyRouteCollection() {
    // Given.
    $pageVariant = $this->prophesize(PageVariantInterface::class);

    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->status()->willReturn(TRUE);
    $pageEntity->getVariants()->willReturn([ $pageVariant->reveal() ]);
    $pageEntity->getThirdPartySetting('panels_everywhere', 'disable_route_override')->willReturn(TRUE);
    $pageEntity->getPath()->willReturn('/path');

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $routeCollection = $this->prophesize(RouteCollection::class);
    $routeCollection->all()->willReturn([]);

    $event = $this->prophesize(RouteBuildEvent::class);
    $event->getRouteCollection()->willReturn($routeCollection->reveal());

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event->reveal());

    // Then.
    $routeCollection->remove(Argument::type('string'))->shouldNotHaveBeenCalled();
    $routeCollection->remove(Argument::type('array'))->shouldNotHaveBeenCalled();
    $routeCollection->get(Argument::type('string'))->shouldNotHaveBeenCalled();
  }

  /**
   * Test that PanelsEverywhereRouteSubscriber does nothing if the collection
   * contains no matching path or outline and route override flag is set to TRUE on page
   * entity.
   */
  public function testSubscriberDoesNothingForActiveDisableFlagAndNoMatchingPathAndOutlineInCollection() {
    // Given.
    $pageVariant = $this->prophesize(PageVariantInterface::class);

    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->status()->willReturn(TRUE);
    $pageEntity->getVariants()->willReturn([ $pageVariant->reveal() ]);
    $pageEntity->getThirdPartySetting('panels_everywhere', 'disable_route_override')->willReturn(TRUE);
    $pageEntity->getPath()->willReturn('/path');

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $route = $this->prophesize(Route::class);
    $route->getPath()->willReturn('/anotherPath');

    $routeCollection = $this->prophesize(RouteCollection::class);
    $routeCollection->all()->willReturn([ 'some.route_name' => $route->reveal() ]);

    $event = $this->prophesize(RouteBuildEvent::class);
    $event->getRouteCollection()->willReturn($routeCollection->reveal());

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event->reveal());

    // Then.
    $routeCollection->remove(Argument::type('string'))->shouldNotHaveBeenCalled();
    $routeCollection->remove(Argument::type('array'))->shouldNotHaveBeenCalled();
    $routeCollection->get(Argument::type('string'))->shouldNotHaveBeenCalled();
  }

  /**
   * Test that PanelsEverywhereRouteSubscriber removes override rout if the
   * collection contains matching variant route and the route override flag is
   * set to TRUE on page entity.
   */
  public function testSubscriberDoesNothingForActiveDisableFlagAndNoMatchingVariantRouteInCollection() {
    // Given.
    $pageVariant = $this->prophesize(PageVariantInterface::class);

    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->status()->willReturn(TRUE);
    $pageEntity->getVariants()->willReturn([ $pageVariant->reveal() ]);
    $pageEntity->getThirdPartySetting('panels_everywhere', 'disable_route_override')->willReturn(TRUE);
    $pageEntity->getPath()->willReturn('/path');

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $route = $this->prophesize(Route::class);
    $route->getPath()->willReturn('/path');
    $route->hasDefault('page_manager_page_variant')->willReturn(FALSE);

    $routeCollection = $this->prophesize(RouteCollection::class);
    $routeCollection->all()->willReturn([ 'some.route_name' => $route->reveal() ]);

    $event = $this->prophesize(RouteBuildEvent::class);
    $event->getRouteCollection()->willReturn($routeCollection->reveal());

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event->reveal());

    // Then.
    $routeCollection->remove(Argument::type('string'))->shouldNotHaveBeenCalled();
    $routeCollection->remove(Argument::type('array'))->shouldNotHaveBeenCalled();
    $routeCollection->get(Argument::type('string'))->shouldNotHaveBeenCalled();
  }

  /**
   * Test that PanelsEverywhereRouteSubscriber removes override if the
   * collection contains matching path and variant route and the route override
   * flag is set to TRUE on page entity.
   */
  public function testSubscriberRemovesOverrideForActiveDisableFlagAndMatchingVariantRouteAndPathInCollection() {
    // Given.
    $pageVariant = $this->prophesize(PageVariantInterface::class);

    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->status()->willReturn(TRUE);
    $pageEntity->getVariants()->willReturn([ $pageVariant->reveal() ]);
    $pageEntity->getThirdPartySetting('panels_everywhere', 'disable_route_override')->willReturn(TRUE);
    $pageEntity->getPath()->willReturn('/path');

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $overrideRoute = $this->prophesize(Route::class);
    $overrideRoute->getPath()->willReturn('/path');
    $overrideRoute->hasDefault('page_manager_page_variant')->willReturn(TRUE);
    $overrideRoute->getDefault('overridden_route_name')->willReturn('original.route_name');

    $originalRoute = $this->prophesize(Route::class);
    $originalRoute->getPath()->willReturn('/path');
    $originalRoute->hasDefault('page_manager_page_variant')->willReturn(FALSE);
    $originalRoute->setDefault('page_id', Argument::type('integer'))->willReturn($originalRoute->reveal());

    $routeCollection = $this->prophesize(RouteCollection::class);
    $routeCollection->all()->willReturn([
      'some.route_name' => $overrideRoute->reveal(),
      'original.route_name' => $overrideRoute->reveal()
    ]);
    $routeCollection->remove("some.route_name")->willReturn(NULL);
    $routeCollection->get('original.route_name')->willReturn($originalRoute->reveal());

    $event = $this->prophesize(RouteBuildEvent::class);
    $event->getRouteCollection()->willReturn($routeCollection->reveal());

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event->reveal());

    // Then.
    $routeCollection->remove('some.route_name')->shouldHaveBeenCalled();
  }
  /**
   * Test that PanelsEverywhereRouteSubscriber removes override if the
   * collection contains matching path outline and variant route and the route
   * override flag is set to TRUE on page entity.
   */
  public function testSubscriberRemovesOverrideForActiveDisableFlagAndMatchingVariantRouteAndPathOutlineInCollection() {
    // Given.
    $pageVariant = $this->prophesize(PageVariantInterface::class);

    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->status()->willReturn(TRUE);
    $pageEntity->getVariants()->willReturn([ $pageVariant->reveal() ]);
    $pageEntity->getThirdPartySetting('panels_everywhere', 'disable_route_override')->willReturn(TRUE);
    $pageEntity->getPath()->willReturn('/path/%');

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $overrideRoute = $this->prophesize(Route::class);
    $overrideRoute->getPath()->willReturn('/path/{wildcard}');
    $overrideRoute->hasDefault('page_manager_page_variant')->willReturn(TRUE);
    $overrideRoute->getDefault('overridden_route_name')->willReturn('original.route_name');

    $originalRoute = $this->prophesize(Route::class);
    $originalRoute->getPath()->willReturn('/path/{wildcard}');
    $originalRoute->hasDefault('page_manager_page_variant')->willReturn(FALSE);
    $originalRoute->setDefault('page_id', Argument::type('integer'))->willReturn($originalRoute->reveal());

    $routeCollection = $this->prophesize(RouteCollection::class);
    $routeCollection->all()->willReturn([
      'some.route_name' => $overrideRoute->reveal(),
      'original.route_name' => $overrideRoute->reveal()
    ]);
    $routeCollection->remove("some.route_name")->willReturn(NULL);
    $routeCollection->get('original.route_name')->willReturn($originalRoute->reveal());

    $event = $this->prophesize(RouteBuildEvent::class);
    $event->getRouteCollection()->willReturn($routeCollection->reveal());

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event->reveal());

    // Then.
    $routeCollection->remove('some.route_name')->shouldHaveBeenCalled();
  }

  /**
   * Test that PanelsEverywhereRouteSubscriber sets page-id on original if the
   * collection contains matching path and variant route and the route override
   * flag is set to TRUE on page entity.
   */
  public function testSubscriberSetsPageIdForActiveDisableFlagAndMatchingVariantRouteAndPathInCollection() {
    // Given.
    $pageVariant = $this->prophesize(PageVariantInterface::class);

    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->id()->willReturn(42);
    $pageEntity->status()->willReturn(TRUE);
    $pageEntity->getVariants()->willReturn([ $pageVariant->reveal() ]);
    $pageEntity->getThirdPartySetting('panels_everywhere', 'disable_route_override')->willReturn(TRUE);
    $pageEntity->getPath()->willReturn('/path');

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ 42 => $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $overrideRoute = $this->prophesize(Route::class);
    $overrideRoute->getPath()->willReturn('/path');
    $overrideRoute->hasDefault('page_manager_page_variant')->willReturn(TRUE);
    $overrideRoute->getDefault('overridden_route_name')->willReturn('original.route_name');

    $originalRoute = $this->prophesize(Route::class);
    $originalRoute->getPath()->willReturn('/path');
    $originalRoute->hasDefault('page_manager_page_variant')->willReturn(FALSE);
    $originalRoute->setDefault('page_id', Argument::type('integer'))->willReturn($originalRoute->reveal());

    $routeCollection = $this->prophesize(RouteCollection::class);
    $routeCollection->all()->willReturn([
      'some.route_name' => $overrideRoute->reveal(),
      'original.route_name' => $overrideRoute->reveal()
    ]);
    $routeCollection->remove("some.route_name")->willReturn(NULL);
    $routeCollection->get('original.route_name')->willReturn($originalRoute->reveal());

    $event = $this->prophesize(RouteBuildEvent::class);
    $event->getRouteCollection()->willReturn($routeCollection->reveal());

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event->reveal());

    // Then.
    $originalRoute->setDefault('page_id', 42)->shouldHaveBeenCalled();
  }
  /**
   * Test that PanelsEverywhereRouteSubscriber sets page-id on original if the
   * collection contains matching path outline and variant route and the route
   * override flag is set to TRUE on page entity.
   */
  public function testSubscriberSetsPageIdForActiveDisableFlagAndMatchingVariantRouteAndPathOutlineInCollection() {
    // Given.
    $pageVariant = $this->prophesize(PageVariantInterface::class);

    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->id()->willReturn(42);
    $pageEntity->status()->willReturn(TRUE);
    $pageEntity->getVariants()->willReturn([ $pageVariant->reveal() ]);
    $pageEntity->getThirdPartySetting('panels_everywhere', 'disable_route_override')->willReturn(TRUE);
    $pageEntity->getPath()->willReturn('/path/%');

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ 42 => $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $overrideRoute = $this->prophesize(Route::class);
    $overrideRoute->getPath()->willReturn('/path/{wildcard}');
    $overrideRoute->hasDefault('page_manager_page_variant')->willReturn(TRUE);
    $overrideRoute->getDefault('overridden_route_name')->willReturn('original.route_name');

    $originalRoute = $this->prophesize(Route::class);
    $originalRoute->getPath()->willReturn('/path/{wildcard}');
    $originalRoute->hasDefault('page_manager_page_variant')->willReturn(FALSE);
    $originalRoute->setDefault('page_id', Argument::type('integer'))->willReturn($originalRoute->reveal());

    $routeCollection = $this->prophesize(RouteCollection::class);
    $routeCollection->all()->willReturn([
      'some.route_name' => $overrideRoute->reveal(),
      'original.route_name' => $overrideRoute->reveal()
    ]);
    $routeCollection->remove("some.route_name")->willReturn(NULL);
    $routeCollection->get('original.route_name')->willReturn($originalRoute->reveal());

    $event = $this->prophesize(RouteBuildEvent::class);
    $event->getRouteCollection()->willReturn($routeCollection->reveal());

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event->reveal());

    // Then.
    $originalRoute->setDefault('page_id', 42)->shouldHaveBeenCalled();
  }

}
