<?php

/**
 * @file
 * Contains \Drupal\pmmi_company_contact\PMMICompanyContactEntityListBuilder.
 */

namespace Drupal\pmmi_company_contact;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Company contact entities.
 *
 * @ingroup pmmi_company_contact
 */
class PMMICompanyContactEntityListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Company contact ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\pmmi_company_contact\Entity\PMMICompanyContactEntity */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $this->getLabel($entity),
      new Url(
        'entity.pmmi_company_contact.edit_form', array(
          'pmmi_company_contact' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
