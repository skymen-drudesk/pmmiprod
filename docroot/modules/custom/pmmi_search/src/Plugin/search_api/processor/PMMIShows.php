<?php

namespace Drupal\pmmi_search\Plugin\search_api\processor;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Provides the 'PMMIShows' processor plugin
 *
 * @SearchApiProcessor(
 *   id = "pmmi_shows",
 *   label = @Translation("PMMI trade shows"),
 *   description = @Translation("PMMI trade shows to keep information about trade shows."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class PMMIShows extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('PMMI Trade shows'),
        'description' => $this->t('PMMI trade shows to keep information about trade shows.'),
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
      ];

      $properties['pmmi_shows'] = new ProcessorProperty($definition);
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

    $fields = $item->getFields();
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($fields, NULL, 'pmmi_shows');
    foreach ($fields as $field) {

      $query = \Drupal::entityQuery('pmmi_company_contact')
        ->condition('field_company', $node->id());
      $result = $query->execute();

      foreach ($result as $contact_id) {
        $contact = \Drupal::entityTypeManager()->getStorage('pmmi_company_contact')->load($contact_id);
        if ($contact && ($show = $contact->get('field_trade_show')->getValue())) {
          $field->addValue((int) $show[0]['target_id']);
        }
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
