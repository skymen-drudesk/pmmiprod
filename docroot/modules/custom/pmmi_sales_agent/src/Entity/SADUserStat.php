<?php

namespace Drupal\pmmi_sales_agent\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Sales agent user stat entity.
 *
 * @ingroup pmmi_sales_agent
 *
 * @ContentEntityType(
 *   id = "sad_user_stat",
 *   label = @Translation("Sales agent user stat"),
 *   bundle_label = @Translation("Sales agent user stat type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\pmmi_sales_agent\SADUserStatListBuilder",
 *     "views_data" = "Drupal\pmmi_sales_agent\Entity\SADUserStatViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\pmmi_sales_agent\Form\SADUserStatForm",
 *       "add" = "Drupal\pmmi_sales_agent\Form\SADUserStatForm",
 *       "edit" = "Drupal\pmmi_sales_agent\Form\SADUserStatForm",
 *       "delete" = "Drupal\pmmi_sales_agent\Form\SADUserStatDeleteForm",
 *     },
 *     "access" = "Drupal\pmmi_sales_agent\SADUserStatAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\pmmi_sales_agent\SADUserStatHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "sad_user_stat",
 *   admin_permission = "administer sales agent user stat entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/sad_user_stat/{sad_user_stat}",
 *     "add-page" = "/admin/structure/sad_user_stat/add",
 *     "add-form" = "/admin/structure/sad_user_stat/add/{sad_user_stat_type}",
 *     "edit-form" = "/admin/structure/sad_user_stat/{sad_user_stat}/edit",
 *     "delete-form" = "/admin/structure/sad_user_stat/{sad_user_stat}/delete",
 *     "collection" = "/admin/structure/sad_user_stat",
 *   },
 *   bundle_entity_type = "sad_user_stat_type",
 *   field_ui_base_route = "entity.sad_user_stat_type.edit_form"
 * )
 */
class SADUserStat extends ContentEntityBase implements SADUserStatInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Sales agent user stat entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }

}
