<?php

namespace Drupal\pmmi_sso\Plugin\QueueWorker;

/**
 * Updates a Personify company data.
 *
 * @QueueWorker(
 *   id = "pmmi_sso_personify_companies",
 *   title = @Translation("Update Personify Company information"),
 *   cron = {"time" = 60}
 * )
 */
class PMMISSOPersonifyCompaniesQueue extends PMMISSOBaseQueue {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->handleItem('pc', $data);
  }

}
