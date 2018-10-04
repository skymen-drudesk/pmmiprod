<?php

namespace Drupal\pmmi_sales_agent\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Sales agent user stat entities.
 */
class SADUserStatViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
