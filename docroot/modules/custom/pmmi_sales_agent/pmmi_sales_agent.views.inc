<?php

/**
 * @file
 * Provide views data for pmmi_sales_agent module.
 */

use Drupal\search_api\Entity\Index;

/**
 * Implements hook_views_data_alter().
 */
function pmmi_sales_agent_views_data_alter(&$data) {
  $data['views']['downloads_favorites_button'] = [
    'title' => t('Downloads favorites button'),
    'help' => t('Displays the download favorites button.'),
    'area' => [
      'id' => 'downloads_favorites_button',
    ],
  ];

  // Describe specific field for the 'created' property, which allows us to get
  // transformed date (i.e. using FROM_UNIXTIME mysql function).
  $data['sad_user_stat']['created_from_unixtime'] = [
    'title' => t('Created (from unixtime)'),
    'help' => t('Created date (from unixtime) in the next format: %M %Y. Use for MySql only.'),
    'real field' => 'created',
    'field' => [
      'id' => 'created_from_unixtime',
    ],
  ];

  // Add "search_index_bulk_form" field handler to all search api index views.
  foreach (Index::loadMultiple() as $index) {
    try {
      // Fill in base data.
      $key = 'search_api_index_' . $index->id();
      $data[$key]['search_index_bulk_form'] = [
        'title' => t('Search index operations bulk form'),
        'help' => t('Add a form element that lets you run operations on multiple nodes.'),
        'field' => [
          'id' => 'search_index_bulk_form',
        ],
      ];
    }
    catch (\Exception $e) {
      $args = [
        '%index' => $index->label(),
      ];
      watchdog_exception('search_api', $e, '%type while computing Views data for index %index: @message in %function (line %line of %file).', $args);
    }
  }
}
