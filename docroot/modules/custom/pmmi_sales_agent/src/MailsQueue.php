<?php

namespace  Drupal\pmmi_sales_agent;

use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The mails queue service.
 */
class MailsQueue {

  /**
   * The mails queue database table name.
   */
  const MAILS_QUEUE_DB_TABLE = 'pmmi_sales_agent_mails';

  /**
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * Constructs a MailsQueue object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * Delete mails queue item.
   *
   * @param integer $id
   *   The mails queue item ID.
   */
  public function delete($id) {
    $this->database
      ->delete(static::MAILS_QUEUE_DB_TABLE)
      ->condition('mid', $id)
      ->execute();
  }

  /**
   * Update sending data.
   *
   * @param integer $id
   *   The mails queue item ID.
   * @param integer $new_sending_date
   *   The mails sending date in timestamp.
   */
  public function updateSendingDate($id, $new_sending_date) {
    $this->database
      ->update(static::MAILS_QUEUE_DB_TABLE)
      ->fields(['sending_date' => $new_sending_date])
      ->condition('mid', $id)
      ->execute();
  }

  /**
   * Delete mails queue per company.
   *
   * @param integer $nid
   *   The company ID.
   */
  public function deletePerCompany($nid) {
    $this->database
      ->delete(static::MAILS_QUEUE_DB_TABLE)
      ->condition('nid', $nid)
      ->execute();
  }

  /**
   * Insert mails queue per company.
   *
   * @param integer $nid
   *   The company ID.
   * @param string $type
   *   The mails queue type.
   * @param integer $sending_date
   *   The mails sending date in timestamp.
   */
  public function insertPerCompany($nid, $type, $sending_date) {
    $this->database
      ->insert(static::MAILS_QUEUE_DB_TABLE)
      ->fields(['nid' => $nid, 'type' => $type, 'sending_date' => $sending_date])
      ->execute();
  }

  /**
   * Get mails queue by type.
   *
   * @param array $types
   *   The mails queue types.
   *
   * @return array
   *   The mails queue items.
   */
  public function selectQueueByTypes(array $types) {
    $query = $this->database
      ->select(static::MAILS_QUEUE_DB_TABLE, 'sam')
      ->fields('sam', ['mid', 'nid'])
      ->condition('sam.type', $types, 'IN')
      ->condition('sam.sending_date', REQUEST_TIME, '<');

    return $query->execute()->fetchAllKeyed();
  }
}
