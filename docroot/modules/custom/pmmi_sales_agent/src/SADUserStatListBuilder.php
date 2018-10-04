<?php

namespace Drupal\pmmi_sales_agent;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Sales agent user stat entities.
 *
 * @ingroup pmmi_sales_agent
 */
class SADUserStatListBuilder extends EntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'id' => $this->t('ID'),
      'type' => $this->t('Type'),
      'created' => $this->t('Created'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\pmmi_sales_agent\Entity\SADUserStat */
    return [
      'id' => $entity->id(),
      'type' => $entity->getType(),
      'created' => date('Y-m-d H:i:s', $entity->getCreatedTime()),
    ] + parent::buildRow($entity);
  }
}
