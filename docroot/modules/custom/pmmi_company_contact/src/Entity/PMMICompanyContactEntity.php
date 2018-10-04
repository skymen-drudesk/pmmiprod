<?php

/**
 * @file
 * Contains \Drupal\pmmi_company_contact\Entity\PMMICompanyContactEntity.
 */

namespace Drupal\pmmi_company_contact\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\pmmi_company_contact\PMMICompanyContactEntityInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Company contact entity.
 *
 * @ingroup pmmi_company_contact
 *
 * @ContentEntityType(
 *   id = "pmmi_company_contact",
 *   label = @Translation("Company contact"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\pmmi_company_contact\PMMICompanyContactEntityListBuilder",
 *     "views_data" = "Drupal\pmmi_company_contact\Entity\PMMICompanyContactEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\pmmi_company_contact\Entity\Form\PMMICompanyContactEntityForm",
 *       "add" = "Drupal\pmmi_company_contact\Entity\Form\PMMICompanyContactEntityForm",
 *       "edit" = "Drupal\pmmi_company_contact\Entity\Form\PMMICompanyContactEntityForm",
 *       "delete" = "Drupal\pmmi_company_contact\Entity\Form\PMMICompanyContactEntityDeleteForm",
 *     },
 *     "access" = "Drupal\pmmi_company_contact\PMMICompanyContactEntityAccessControlHandler",
 *   },
 *   base_table = "pmmi_company_contact",
 *   admin_permission = "administer PMMICompanyContactEntity entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/pmmi_company_contact/{pmmi_company_contact}",
 *     "edit-form" = "/admin/pmmi_company_contact/{pmmi_company_contact}/edit",
 *     "delete-form" = "/admin/pmmi_company_contact/{pmmi_company_contact}/delete"
 *   },
 *   field_ui_base_route = "pmmi_company_contact.settings"
 * )
 */
class PMMICompanyContactEntity extends ContentEntityBase implements PMMICompanyContactEntityInterface {
  use EntityChangedTrait;
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
  public function getCreatedTime() {
    return $this->get('created')->value;
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
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Company contact entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Company contact entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Company contact entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
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

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Company contact entity.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Company contact entity.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
