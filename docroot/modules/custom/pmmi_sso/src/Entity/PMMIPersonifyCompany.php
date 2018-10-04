<?php

namespace Drupal\pmmi_sso\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Personify company entity.
 *
 * @ingroup pmmi_sso
 *
 * @ContentEntityType(
 *   id = "pmmi_personify_company",
 *   label = @Translation("Personify company"),
 *   handlers = {
 *     "storage" = "Drupal\pmmi_sso\PMMIPersonifyCompanyStorage",
 *     "storage_schema" = "Drupal\pmmi_sso\PMMIPersonifyCompanyStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\pmmi_sso\PMMIPersonifyCompanyListBuilder",
 *     "views_data" = "Drupal\pmmi_sso\Entity\PMMIPersonifyCompanyViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\pmmi_sso\Entity\Form\PMMIPersonifyCompanyForm",
 *       "edit" = "Drupal\pmmi_sso\Entity\Form\PMMIPersonifyCompanyForm",
 *       "delete" = "Drupal\pmmi_sso\Entity\Form\PMMIPersonifyCompanyDeleteForm",
 *     },
 *     "access" = "Drupal\pmmi_sso\PMMIPersonifyCompanyAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\pmmi_sso\PMMIPersonifyCompanyHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "pmmi_personify_company",
 *   admin_permission = "administer personify company entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "personify_id" = "personify_id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/pmmi_sso/personify_company/{pmmi_personify_company}",
 *     "edit-form" = "/admin/pmmi_sso/personify_company/{pmmi_personify_company}/edit",
 *     "delete-form" = "/admin/pmmi_sso/personify_company/{pmmi_personify_company}/delete",
 *     "collection" = "/admin/structure/personify_company",
 *   },
 *   field_ui_base_route = "pmmi_personify_company.settings"
 * )
 */
class PMMIPersonifyCompany extends ContentEntityBase implements PMMIPersonifyCompanyInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPersonifyId() {
    return $this->get('personify_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPersonifyId($personify_id) {
    $this->set('personify_id', $personify_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCode() {
    return $this->get('code')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCode($code) {
    $this->set('code', $code);
    return $this;
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
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['personify_id'] = BaseFieldDefinition::create('string')
      ->setReadOnly(TRUE)
      ->setLabel(t('Master Customer Id'))
      ->setDescription(t('The Master Customer Id of the Personify company entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The Label Name of the Personify company entity.'))
      ->setSettings([
        'max_length' => 128,
        'text_processing' => 0,
      ])
      ->setReadOnly(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer Class Code'))
      ->setDescription(t('The Customer Class Code of the Personify company entity.'))
      ->setReadOnly(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Personify company is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
