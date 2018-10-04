<?php

namespace Drupal\pmmi_sso;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\user\RoleInterface;

/**
 * Defines a class to build a listing of Access Token entities.
 *
 * @ingroup pmmi_sso
 */
class PMMISSOTokenListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'id' => $this->t('ID'),
      'user' => $this->t('User'),
      'name' => $this->t('Token'),
      'auth_id' => $this->t('AuthID'),
      'expire' => $this->t('Expire'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\pmmi_sso\Entity\PMMISSOToken */
    $row = [
      'id' => $entity->id(),
      'user' => NULL,
      'name' => $entity->toLink(sprintf('%s…', substr($entity->label(), 0, 10))),
      'auth_id' => $entity->get('auth_id')->value,
      'expire' => date('M j H:m:s', $entity->get('expire')->value),
    ];
    if (($user = $entity->get('uid')) && $user->entity) {
      $row['user'] = $user->entity->toLink($user->entity->label());
    }
    return $row + parent::buildRow($entity);
  }

}
