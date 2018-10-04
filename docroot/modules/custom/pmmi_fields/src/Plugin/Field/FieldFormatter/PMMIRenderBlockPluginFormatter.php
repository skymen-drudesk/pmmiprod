<?php

namespace Drupal\pmmi_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\block\Entity\Block;

/**
 * Plugin implementation of the 'rendered block plugin' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_rendered_block_plugin",
 *   label = @Translation("Rendered block plugin"),
 *   description = @Translation("Display the rendered block plugin of the referenced entities. Only for block config entity!"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class PMMIRenderBlockPluginFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $settings = $item->getDataDefinition()->getSettings();
      if (!empty($item->_loaded) && $settings['target_type'] == 'block') {
        $entity = $item->entity;
        $block = Block::load($entity->id());
        $render = \Drupal::entityTypeManager()
          ->getViewBuilder('block')
          ->view($block);
        $elements[] = $render;
      }
    }

    return $elements;
  }

}
