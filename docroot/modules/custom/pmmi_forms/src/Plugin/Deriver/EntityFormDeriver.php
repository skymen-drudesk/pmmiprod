<?php

/**
 * @file
 * Contains \Drupal\pmmi_forms\Plugin\Deriver\EntityFormDeriver.
 */

namespace Drupal\pmmi_forms\Plugin\Deriver;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\ctools\Plugin\Deriver\EntityDeriverBase;

/**
 * Provides entity form block definitions for each entity type.
 */
class EntityFormDeriver extends EntityDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->hasViewBuilderClass() || $entity_type_id == 'node_type') {
        $this->derivatives[$entity_type_id] = $base_plugin_definition;
        $this->derivatives[$entity_type_id]['admin_label'] = $this->t('Entity form (@label)', ['@label' => $entity_type->getLabel()]);
        $this->derivatives[$entity_type_id]['context'] = [
          'entity' => new ContextDefinition('entity:' . $entity_type_id),
        ];
      }
    }
    return $this->derivatives;
  }

}
