<?php

namespace Drupal\pmmi_fields\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;

/**
 * Plugin implementation of the 'datetime_timezone' field type.
 *
 * @FieldType(
 *   id = "datetime_timezone",
 *   label = @Translation("Date with timezone"),
 *   description = @Translation("Create and store date and timezone values."),
 *   default_widget = "datetime_timezone_default",
 *   default_formatter = "datetime_timezone_custom",
 * )
 */
class DateTimeTimezone extends DateTimeItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['timezone'] = DataDefinition::create('string')
      ->setLabel(t('Timezone'));

    return $properties;
  }


  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['timezone'] = [
      'description' => 'The date timezone',
      'type' => 'varchar',
      'length' => 50,
    ];
    $schema['indexes']['value_timezone'] = ['value', 'timezone'];
    return $schema;
  }

}
