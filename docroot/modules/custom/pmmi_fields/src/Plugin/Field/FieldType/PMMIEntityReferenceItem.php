<?php

namespace Drupal\pmmi_fields\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'pmmi_entity_reference' field type.
 *
 * @FieldType(
 *   id = "pmmi_entity_reference",
 *   label = @Translation("PMMI Entity reference"),
 *   description = @Translation("An PMMI entity field containing an entity reference."),
 *   category = @Translation("PMMI Reference"),
 *   default_widget = "pmmi_inline_entity_form_complex",
 *   default_formatter = "pmmi_entity_reference_entity_view",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class PMMIEntityReferenceItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $view_mode_definition = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Reference View mode'))
      ->setRequired(FALSE);
    $properties['view_mode'] = $view_mode_definition;
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['view_mode'] = array(
      'type' => 'varchar',
      'length' => 256,
    );

    return $schema;
  }

}
