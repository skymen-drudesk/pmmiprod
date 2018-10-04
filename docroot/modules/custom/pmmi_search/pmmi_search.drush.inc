<?php

/**
 * @file
 * Additional drush commands for search_api.
 */

use Drupal\search_api\Entity\Index;

/**
 * Implements hook_drush_command().
 */
function pmmi_search_drush_command() {
  $items = [];

  $items['pmmi-search-api-fix'] = [
    'description' => 'Fix search_api broken indexes',
    'drush dependencies' => ['pmmi_search', 'search_api'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_SITE,
  ];

  return $items;
}

/**
 * Callback function drush_pmmi_search_api_fix().
 *
 */
function drush_pmmi_search_api_fix() {
  // Load all configured indexes.
  $indexes = Index::loadMultiple();

  /** @var \Drupal\search_api\Entity\Index $index */
  foreach ($indexes as $index) {
    $total = $index->getTrackerInstance()->getTotalItemsCount();
    $index_id = $index->id();
    $server_id = $index->getServerId();

    if ($total == 0) {
      // Disable and enable server.
      drush_search_api_server_disable($server_id);
      drush_search_api_server_enable($server_id);
      // Enable index.
      drush_search_api_enable($index_id);
      // Try to index content.
      drush_search_api_index($index_id);
    }
  }
}
