<?php

namespace Drupal\pmmi_training_provider;

use Drupal\pmmi_sales_agent\SADUserStatTypeListBuilder;

/**
 * Provides a listing of Training provider report type entities.
 */
class TPDReportTypeListBuilder extends SADUserStatTypeListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Training provider report');
    return $header + parent::buildHeader();
  }

}
