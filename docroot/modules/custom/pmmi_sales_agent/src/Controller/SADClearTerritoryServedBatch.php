<?php

namespace Drupal\pmmi_sales_agent\Controller;

use Drupal\node\Entity\Node;
use Exception;

/**
 * Class PageManagerSearchGenerateBatch
 *
 * Batch for clearing field_territory_served field value in Company nodes;
 *
 * @package Drupal\pmmi_sales_agent\Controller
 */
class SADClearTerritoryServedBatch {

  /**
   * Bach process callback.
   *
   * @param $nid
   *  Node ID.
   * @param $context
   */
  public static function bulkUpdate($nid, &$context) {
    try  {
      $node = Node::load($nid);
      $node->set('field_territory_served', []);
      $node->save();
    }
    catch (Exception $e) {
      \Drupal::logger('pmmi_sales_agent')->error($e);

      return;
    }
  }

  /**
   * Batch finish callback.
   *
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function bulkUpdateFinishedCallback($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One node updated.', '@count nodes updated.'
      );
    }
    else {
      $message = t('Encountered an error on update.');
    }

    drupal_set_message($message);
  }
}
