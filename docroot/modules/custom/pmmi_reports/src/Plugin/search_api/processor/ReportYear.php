<?php

namespace Drupal\pmmi_reports\Plugin\search_api\processor;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Provides the 'ReportYear' processor plugin.
 *
 * @SearchApiProcessor(
 *   id = "report_year",
 *   label = @Translation("Report Year"),
 *   description = @Translation("The report available date(year)."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class ReportYear extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Report available date(year)'),
        'description' => $this->t('Report available date(year).'),
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
      ];

      $properties['report_year'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Only run for node items.
    if ($item->getDatasource()->getEntityTypeId() != 'node') {
      return;
    }

    // Get the node object.
    $node = $this->getNode($item->getOriginalObject());
    if (!$node) {
      // Apparently we were active for a wrong item.
      return;
    }

    // Get the node object.
    $node = $this->getNode($item->getOriginalObject());
    if (!$node || !$node->hasField('field_available_from_date')) {
      // Apparently we were active for a wrong item.
      return;
    }
    $fields = $item->getFields();
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($fields, NULL, 'report_year');

    foreach ($fields as $field) {
      if ($available_date = $node->field_available_from_date->value) {
        $timestamp = strtotime($available_date);
        $year = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'Y');
        $field->addValue($year);
      }
    }
  }

  /**
   * Retrieves the node related to an indexed search object.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $item
   *   A search object that is being indexed.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node related to that search object.
   */
  protected function getNode(ComplexDataInterface $item) {
    $item = $item->getValue();
    if ($item instanceof NodeInterface) {
      return $item;
    }

    return NULL;
  }

}
