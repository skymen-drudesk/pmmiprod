<?php

/**
 * @file
 * Builds placeholder replacement tokens for pmmi.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info_alter().
 */
function pmmi_token_info_alter(&$info) {
  if (isset($info['tokens']['node-field_video_type'])) {
    $info['tokens']['node-field_video_type']['pmmi_video_path'] = array(
      'name' => t('Video type path'),
      'description' => t('Video type path based on parent term'),
    );
  }

  // Add simple token to allow using current user ID as a global views
  // replacement pattern.
  // @todo: it should be in core, but let's use this solution for now.
  $info['tokens']['view']['cu-uid'] = [
    'name' => t('User ID'),
    'description' => t("The unique ID of the current user account."),
  ];
}

/**
 * Implements hook_tokens().
 */
function pmmi_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();

  if ($type == 'field_video_type' && !empty($data['node-field_video_type'])) {
    $node = $data['node-field_video_type']->getEntity();

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'pmmi_video_path':
          $name = '';
          if ($video_terms = $node->get('field_video_type')->referencedEntities()) {
            $term = reset($video_terms);
            $name = \Drupal::service('pathauto.alias_cleaner')->cleanString($term->getName(), $options);
            $parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($term->id());
            if (!empty($parents)) {
              $parent = reset($parents);
              if (strtolower($parent->getName()) == 'webinar') {
                $name .= '/webinars';
              }
            }
          }
          $replacements[$original] = $name;
          break;
      }
    }
  }

  if ($type == 'view' && !empty($data['view'])) {
    $replacements[$tokens['cu-uid']] = \Drupal::currentUser()->id();
  }

  return $replacements;
}
