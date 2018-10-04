<?php

namespace Drupal\pmmi_sso\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserDataInterface;

/**
 * Class PMMISSOCronDataCollector.
 *
 * @package Drupal\pmmi_sso
 */
class PMMISSOCronDataCollector {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\user\UserData definition.
   *
   * @var \Drupal\user\UserData
   */
  protected $userData;

  /**
   * Stores database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The immutable configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * Provider name.
   *
   * @var string
   */
  protected $provider = PMMISSOHelper::PROVIDER;

  /**
   * PMMISSOCronDataCollector constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param Connection $database_connection
   *   The database service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    UserDataInterface $user_data,
    Connection $database_connection
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->userData = $user_data;
    $this->connection = $database_connection;
    $this->settings = $config_factory->get('pmmi_sso.update.settings');
  }

  /**
   * Return PMMI SSO user's which needed update.
   *
   * @return array
   *   PMMI SSO IDs keyed by $uid.
   */
  public function getUsersForUpdate() {
    $users_data = $this->userData->get($this->provider);
    if (empty($users_data)) {
      return [];
    }
    $interval_block = $this->settings->get('interval_block');
    $interval_info = $this->settings->get('interval_info');
    $interval_roles = $this->settings->get('interval_roles');
    $pids = $this->getPersonifyIds(array_keys($users_data));
    foreach ($users_data as $uid => &$data) {
      $need_update = FALSE;
      foreach ($data['last_update_data'] as $key => $value) {
        if ($value < REQUEST_TIME - ${'interval_' . $key}) {
          $need_update = TRUE;
        }
        else {
          unset($data['last_update_data'][$key]);
        }
      }
      if ($need_update) {
        $users_data[$uid]['pid'] = $pids[$uid];
        $users_data[$uid]['uid'] = $uid;
      }
      else {
        unset($users_data[$uid]);
      }
    }
    return $users_data;
  }

  /**
   * Return PMMI SSO user IDs for Drupal accounts.
   *
   * @param array $uids
   *   User IDs.
   *
   * @return array
   *   PMMI SSO IDs keyed by $uid.
   */
  protected function getPersonifyIds(array $uids) {
    return $this->connection->select('authmap', 'am')
      ->fields('am', ['uid', 'authname'])
      ->condition('uid', $uids, 'IN')
      ->condition('provider', $this->provider)
      ->execute()
      ->fetchAllKeyed();
  }

  /**
   * Return PMMI SSO companies which need updating.
   *
   * @return array
   *   PMMI SSO Company keyed by $id.
   */
  public function getCompaniesForUpdate() {
    /** @var \Drupal\pmmi_sso\PMMIPersonifyCompanyStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('pmmi_personify_company');
    $interval = $this->settings->get('interval_company');
    $companies_data = $storage->getCompaniesForUpdate($interval);
    return $companies_data;
  }

}
