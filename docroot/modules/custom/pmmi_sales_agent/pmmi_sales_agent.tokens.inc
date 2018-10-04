<?php

/**
 * @file
 * Builds placeholder replacement tokens for pmmi_sales_agent module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info_alter().
 */
function pmmi_sales_agent_token_info_alter(&$info) {
  $info['tokens']['node']['one-time-update-link'] = [
    'name' => t('One-time update link'),
    'description' => t('Generates a unique URL for a user to update a listing.'),
  ];
}

/**
 * Implements hook_tokens().
 */
function pmmi_sales_agent_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'entity' && $data['entity_type'] == 'node' && !empty($data['entity'])) {
    $node = $data['entity'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'one-time-update-link':
          $replacements[$original] = pmmi_sales_agent_login_url($node);
          break;
      }
    }
  }

  return $replacements;
}
