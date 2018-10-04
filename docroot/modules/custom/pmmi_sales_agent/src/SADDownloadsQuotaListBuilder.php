<?php

namespace Drupal\pmmi_sales_agent;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Downloads quota entities.
 */
class SADDownloadsQuotaListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sad_downloads_quota_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'username' => $this->t('Username'),
      'quota' => $this->t('Downloads quota'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\pmmi_sales_agent\SADDownloadsQuotaInterface $entity */
    return [
      'usernmae' => $entity->getUser()->getUserName(),
      'quota' => $entity->getQuota(),
    ] + parent::buildRow($entity);
  }
}
