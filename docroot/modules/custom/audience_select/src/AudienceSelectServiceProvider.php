<?php

namespace Drupal\audience_select;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides services.
 *
 * Overrides the http_middleware.page_cache service
 * to enable caching for anonymous.
 */
class AudienceSelectServiceProvider extends ServiceProviderBase {

  /**
   * Modifies existing service definitions.
   *
   * @param ContainerBuilder $container
   *   The ContainerBuilder whose service definitions can be altered.
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (!array_key_exists('pmmi_crawler_detect', $modules)) {
      $container->register('audience_select.crawler', 'Drupal\audience_select\Service\CrawlerDetect')
        ->addArgument(new Reference('request_stack'));

      $container->getDefinition('audience_select.audience_manager')
        ->setArguments([new Reference('audience_select.crawler')]);
    }

    $definition = $container->getDefinition('http_middleware.page_cache');
    $definition->setClass('Drupal\audience_select\StackMiddleware\AudiencePageCache');
  }

}
