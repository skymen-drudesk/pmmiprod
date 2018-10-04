<?php

/**
 * @file
 * Contains \Drupal\pmmi_company_contact\Entity\PMMICompanyContactEntity.
 */

namespace Drupal\pmmi_company_contact\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Company contact entities.
 */
class PMMICompanyContactEntityViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['pmmi_company_contact']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Company contact'),
      'help' => $this->t('The Company contact ID.'),
    );

    return $data;
  }

}
