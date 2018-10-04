<?php

namespace Drupal\pmmi\WebformElementViews;

use Drupal\webform\Plugin\WebformElementInterface;
use Drupal\webform_views\WebformElementViews\WebformElementViewsAbstract;

/**
 * Webform views handler for boolean webform elements.
 */
class WebformBooleanViews extends WebformElementViewsAbstract {

  /**
   * {@inheritdoc}
   */
  public function getElementViewsData(WebformElementInterface $element_plugin, array $element) {
    $views_data = parent::getElementViewsData($element_plugin, $element);

    $views_data['filter'] = [
      'id' => 'webform_submission_boolean_filter',
      'real field' => 'value',
    ];

    return $views_data;
  }

}
