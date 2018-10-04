<?php

namespace Drupal\pmmi_training_provider\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the Entity view count entity.
 *
 * @ingroup pmmi_training_provider
 *
 * @ContentEntityType(
 *   id = "entity_view_count",
 *   label = @Translation("Entity view count"),
 *   base_table = "entity_view_count",
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\pmmi_training_provider\Entity\EntityViewCountViewsData",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "bundle",
 *     "created" = "created",
 *     "changed" = "changed",
 *   }
 * )
 */
class EntityViewCount extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The ID of the entity of which this comment is a reply.'))
      ->setRequired(TRUE);
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setReadOnly(TRUE);
    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Entity Type'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);
    $fields['views_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Views Count'))
      ->setRequired(TRUE);
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the node was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the node was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function record(array $values = []) {
    $entity_manager = \Drupal::entityTypeManager();
    $storage = $entity_manager->getStorage('entity_view_count');
    if ($entity = $storage->loadByProperties($values)) {
      $entity = reset($entity);
      $count = (int) $entity->views_count->value;
      $entity->set('views_count', ++$count);
      $entity->setChangedTime(\Drupal::time()->getRequestTime());
    }
    else {
      $values['views_count'] = 1;
      $entity = static::create($values);
    }
    return $entity;
  }

}
