<?php

namespace Drupal\pmmi_search\Plugin\search_api\processor;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;

/**
 * Specific pmmi processor to adapt country_area field for wide search.
 *
 * @SearchApiProcessor(
 *   id = "pmmi_country_area_ws",
 *   label = @Translation("PMMI country area wide search"),
 *   description = @Translation("Wide search processor for country_area fields."),
 *   stages = {
 *     "pre_index_save" = 0,
 *     "preprocess_index" = -6,
 *     "preprocess_query" = -6
 *   }
 * )
 */
class PMMISCountryAreaWS extends FieldsProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $fields = $this->index->getFields();
    $field_options = [];
    $default_fields = [];
    if (isset($this->configuration['fields'])) {
      $default_fields = $this->configuration['fields'];
    }
    foreach ($fields as $name => $field) {
      if ($this->fieldIsCountryArea($field)) {
        $field_options[$name] = Html::escape($field->getPrefixedLabel());
        if (!isset($this->configuration['fields']) && $this->testField($name, $field)) {
          $default_fields[] = $name;
        }
      }
    }

    $form['fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable this processor on the following fields'),
      '#options' => $field_options,
      '#default_value' => $default_fields,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function processField(FieldInterface $field) {
    $new_values = [];

    foreach ($field->getValues() as $value) {
      if ($value !== '') {
        if ($pos = strpos($value, '::')) {
          $new_values[] = substr($value, 0, $pos);
        }
        $new_values[] = $value;
      }
    }

    $field->setValues(array_values($new_values));
  }

  /**
   * Checks if field is country_area.
   *
   * @param \Drupal\search_api\Item\FieldInterface $field
   *   The field to process.
   *
   * @return bool
   *   TRUE if the field should be processed, FALSE otherwise.
   */
  protected function fieldIsCountryArea(FieldInterface $field) {
    $field_dd = $field->getDataDefinition();
    return ($field_dd instanceof FieldItemDataDefinition) && $field_dd->getDataType() == 'field_item:country_area';
  }
}
