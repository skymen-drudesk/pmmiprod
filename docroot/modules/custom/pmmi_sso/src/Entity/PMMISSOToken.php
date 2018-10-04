<?php

namespace Drupal\pmmi_sso\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\user\UserInterface;

/**
 * Defines the PMMI SSO Token entity.
 *
 * @ingroup pmmi_sso
 *
 * @ContentEntityType(
 *   id = "pmmi_sso_token",
 *   label = @Translation("Personify SSO token"),
 *   handlers = {
 *     "list_builder" = "Drupal\pmmi_sso\PMMISSOTokenListBuilder",
 *     "form" = {
 *       "delete" = "Drupal\pmmi_sso\Entity\Form\PMMISSOTokenDeleteForm",
 *     },
 *     "access" = "Drupal\pmmi_sso\AccessTokenAccessControlHandler",
 *   },
 *   base_table = "pmmi_sso_token",
 *   admin_permission = "administer pmmi personify sso token",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "value",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/pmmi_sso/token/{pmmi_sso_token}",
 *     "delete-form" = "/admin/pmmi_sso/token/{pmmi_sso_token}/delete"
 *   }
 * )
 */
class PMMISSOToken extends ContentEntityBase implements PMMISSOTokenInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Access Token entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Access Token entity.'))
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user ID of the user this access token is authenticating.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(FALSE)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'author',
        'weight' => 1,
      ])
      ->setCardinality(1)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ]);
    $fields['auth_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Auth User ID'))
      ->setDescription(t('The Auth user ID of the user this access token is authenticating.'))
      ->setTranslatable(FALSE)
      ->setReadOnly(TRUE);
    $fields['value'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Token'))
      ->setDescription(t('The token value.'))
      ->setSettings([
        'max_length' => 128,
        'text_processing' => 0,
      ])
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 4,
      ]);
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 5,
      ]);
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 6,
      ]);
    $fields['expire'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expire'))
      ->setDescription(t('The time when the token expires.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 7,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 7,
      ])
      ->setRequired(TRUE);
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the token is available.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => 8,
      ])
      ->setRevisionable(FALSE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE);

    return $fields;
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
  public function revoke() {
    $this->set('status', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function isRevoked() {
    if ($this->get('expire') > time()) {
      $this->revoke();
    }
    return !$this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setToken($token, $expire) {
    $this->set('status', TRUE);
    $this->set('value', $token);
    $this->set('expire', $expire);
  }

  /**
   * {@inheritdoc}
   */
  public function getToken() {
    return $this->get('value')->value;
  }

}
