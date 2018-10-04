<?php

namespace Drupal\pmmi_page_manager_search\Controller;

use Drupal\page_manager\Entity\PageVariant;
use Exception;

/**
 * Class PageManagerSearchGenerateBatch
 *
 * Batch for generating PageManagerSearch entities for each PageVariant entity.
 *
 * @package Drupal\pmmi_page_manager_search\Controller
 */
class PageManagerSearchGenerateBatch {

  /**
   * Bach process callback.
   *
   * @param $pvid
   *  Page Variant ID.
   * @param $context
   */
  public static function bulkGenerate($pvid, &$context) {
    try  {
      $page_variant = PageVariant::load($pvid);
      // Get Page from PageVariant
      $page = $page_variant->getPage();
      if ($page->id() !== 'site_template') {
        $page_variant->save();
      }
    }
    catch (Exception $e) {
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
  public static function bulkGenerateFinishedCallback($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One entity generated.', '@count entities generated.'
      );
    }
    else {
      $message = t('Encountered an error on generate.');
    }

    drupal_set_message($message);
  }
}
