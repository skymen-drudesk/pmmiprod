<?php

namespace Drupal\pmmi_address;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Filter selected countries.
 */
class FilterCountries {

  protected $fieldManager;

  protected $entityTypeManager;

  /**
   * FilterCountries constructor.
   */
  public function __construct(EntityFieldManagerInterface $fieldManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->fieldManager = $fieldManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Get used countries list.
   */
  public function getUsedCountries($bundle = '') {
    $table_mapping = $this->entityTypeManager->getStorage('node')->getTableMapping();

    $field_table = $table_mapping->getFieldTableName('field_address');
    $field_storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('node')['field_address'];
    $field_column = $table_mapping->getFieldColumnName($field_storage_definitions, 'country_code');

    $connection = \Drupal::database();
    $query = $connection->select($field_table, 'f')
      ->fields('f', array($field_column))
      ->distinct(TRUE);
    if (!empty($content_type)) {
      $query->condition('bundle', $bundle);
    }
    $result = $query->execute()->fetchCol();

    return array_combine($result, $result);
  }

}
