<?php

namespace Drupal\pmmi_sso\Plugin\QueueWorker;

/**
 * Updates a user's data.
 *
 * @QueueWorker(
 *   id = "pmmi_sso_users",
 *   title = @Translation("Update User's information"),
 *   cron = {"time" = 60}
 * )
 */
class PMMISSOUsersQueue extends PMMISSOBaseQueue {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->handleItem('users', $data);
  }

}
