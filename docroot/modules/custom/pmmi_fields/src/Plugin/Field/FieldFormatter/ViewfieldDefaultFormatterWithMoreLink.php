<?php

/**
 * @file
 * Contains \Drupal\viewfield\Plugin\Field\FieldFormatter\ViewfieldDefaultFormatter.
 */

namespace Drupal\pmmi_fields\Plugin\Field\FieldFormatter;

use Drupal\viewfield\Plugin\Field\FieldFormatter\ViewfieldDefaultFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'viewfield_with_more_link' formatter.
 *
 * @FieldFormatter(
 *   id = "viewfield_with_more_link",
 *   label = @Translation("Viewfield formatter with more link"),
 *   field_types = {
 *     "viewfield"
 *   }
 * )
 */
class ViewfieldDefaultFormatterWithMoreLink extends ViewfieldDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($elements as &$element) {
      $view_display = $element['#view_display'];
      if (!empty($element['#exposed_settings'])) {
        // More link settings.
        if (!empty($element['#exposed_settings']['more'])) {
          if (!empty($element['#exposed_settings']['more'][$view_display])) {
            $element['#view']->display_handler->options = array_merge($element['#view']->display_handler->options, $element['#exposed_settings']['more'][$view_display]);
          }
          foreach ($element['#exposed_settings']['more'] as $display_name => $more_settings) {
            $more_settings['link_display'] = 'custom_url';
            if ($display_name == $view_display) {
              $element['#view']->display_handler->options = array_merge($element['#view']->display_handler->options, $more_settings);
            }
            else {
              $handler = $element['#view']->displayHandlers->get($display_name);
              if (!empty($handler)) {
                $handler->display['display_options'] = array_merge($handler->display['display_options'], $more_settings);
              }
            }
          }
        }
        // Title settings.
        if (!empty($element['#exposed_settings']['view_override_title']) && isset($element['#exposed_settings']['view_title'])) {
          $handler = $element['#view']->displayHandlers->get($view_display);
          if ($handler->isDefaulted('title')) {
            $element['#view']->display_handler->options['title'] = $element['#exposed_settings']['view_title'];
          }
          else {
            $handler->options['title'] = $element['#exposed_settings']['view_title'];
          }
        }
      }
    }

    return $elements;
  }

}
