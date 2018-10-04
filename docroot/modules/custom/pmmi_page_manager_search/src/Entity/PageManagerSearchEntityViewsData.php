<?php

/**
 * @file
 * Contains \Drupal\pmmi_company_contact\Entity\PMMICompanyContactEntity.
 */

namespace Drupal\pmmi_page_manager_search\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Company contact entities.
 */
class PageManagerSearchEntityViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['pmmi_page_manager_search']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Page Manager Search'),
      'help' => $this->t('Page Manager Search.'),
    ];

    return $data;
  }

}
