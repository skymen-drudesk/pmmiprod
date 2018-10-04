<?php

namespace Drupal\pmmi_sso;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Personify company entities.
 *
 * @ingroup pmmi_sso
 */
class PMMIPersonifyCompanyListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'id' => $this->t('ID'),
      'personify_id' => $this->t('Personify company ID'),
      'name' => $this->t('Name'),
      'code' => $this->t('Customer Class Code'),
      'status' => $this->t('Status'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\pmmi_sso\Entity\PMMIPersonifyCompany */
    $row = [
      'id' => $entity->id(),
      'personify_id' => $entity->getPersonifyId(),
      'name' => $entity->toLink($entity->label()),
      'code' => $entity->getCode(),
      'status' => $entity->isPublished() ? $this->t('Published') : $this->t('Not published'),
    ];
    return $row + parent::buildRow($entity);
  }

}
