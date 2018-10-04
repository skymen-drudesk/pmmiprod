<?php

namespace Drupal\pmmi_psdata\Plugin\QueueWorker;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\pmmi_psdata\Service\PMMIDataCollector;
use Drupal\pmmi_psdata\Service\PMMIDataRequestHelper;
use Drupal\pmmi_sso\Service\PMMISSOHelper;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base functionality for the Workers.
 */
abstract class PMMIBaseDataQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * A cache backend interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Drupal\pmmi_psdata\Service\PMMIDataCollector definition.
   *
   * @var \Drupal\pmmi_psdata\Service\PMMIDataCollector
   */
  protected $dataCollector;

  /**
   * Provider name.
   *
   * @var string
   */
  protected $provider = PMMISSOHelper::PROVIDER;

  /**
   * Drupal\pmmi_psdata\Service\PMMIDataRequestHelper definition.
   *
   * @var \Drupal\pmmi_psdata\Service\PMMIDataRequestHelper
   */
  protected $requestHelper;

  /**
   * ReportWorkerBase constructor.
   *
   * @param array $configuration
   *   The configuration of the instance.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   A cache backend interface.
   * @param PMMIDataCollector $psdata_collector
   *   The PMMIDataCollector service.
   * @param PMMIDataRequestHelper $request_helper
   *   The PMMIDataRequestHelper service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CacheBackendInterface $cache,
    PMMIDataCollector $psdata_collector,
    PMMIDataRequestHelper $request_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cache = $cache;
    $this->dataCollector = $psdata_collector;
    $this->requestHelper = $request_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache.default'),
      $container->get('pmmi_psdata.collector'),
      $container->get('pmmi_psdata.request_helper')
    );
  }

  /**
   * Building a request for a job title for committee members.
   *
   * @param array $ids
   *   An array of Committee member IDs.
   *
   * @return array
   *   The request options array.
   */
  protected function buildCommitteeRequest(array $ids) {
    // AddressInfos?$select=MasterCustomerId,JobTitle&$filter=
    // (MasterCustomerId eq '00000159' or MasterCustomerId eq '00000357' or
    // MasterCustomerId eq '00000375') and AddressStatusCode eq 'GOOD' and
    // PrioritySeq eq 0 .
    $path_element = 'AddressInfos';
    $filter = $this->requestHelper->addFilter('eq', 'MasterCustomerId', $ids, TRUE);
    $filter .= $this->requestHelper->addFilter('eq', 'AddressStatusCode', ['GOOD']);
    $filter .= $this->requestHelper->addFilter('eq', 'PrioritySeq', [0], FALSE, TRUE);
    $query = [
      '$filter' => $filter,
      '$select' => 'MasterCustomerId,JobTitle',
    ];
    return $this->requestHelper->buildGetRequest($path_element, $query);
  }

  /**
   * Helper function for creating a request for information about employees.
   *
   * @param array $ids
   *   An array of member IDs.
   *
   * @return array
   *   The request options array.
   */
  protected function buildMembersInfoRequest(array $ids) {
    // CustomerInfos?$filter=(MasterCustomerId eq '00026974' or MasterCustomerId
    // eq '12081383') &$select=MasterCustomerId,LabelName,FirstName,LastName .
    $path_element = 'CustomerInfos';
    $filter = $this->requestHelper->addFilter('eq', 'MasterCustomerId', $ids, TRUE);
    $query = [
      '$filter' => $filter,
      '$select' => 'MasterCustomerId,LabelName,FirstName,LastName',
    ];
    return $this->requestHelper->buildGetRequest($path_element, $query);
  }

  /**
   * Helper function for creating a request for a job title for employees.
   *
   * @param array $ids
   *   An array of member IDs.
   *
   * @return array
   *   The request options array.
   */
  protected function buildAddressRequest(array $ids) {
    // AddressInfos?$select=MasterCustomerId,JobTitle&$filter=
    // (MasterCustomerId eq '00000159' or MasterCustomerId eq '00000357' or
    // MasterCustomerId eq '00000375') and PrioritySeq eq 0 .
    $path_element = 'AddressInfos';
    $filter = $this->requestHelper->addFilter('eq', 'MasterCustomerId', $ids, TRUE);
    $filter .= $this->requestHelper->addFilter('eq', 'PrioritySeq', [0], FALSE, TRUE);
    $query = [
      '$filter' => $filter,
      '$select' => 'MasterCustomerId,JobTitle,CountryCode',
    ];
    return $this->requestHelper->buildGetRequest($path_element, $query);
  }

  /**
   * Building a request for communication information about employees.
   *
   * @param array $ids
   *   An array of member IDs.
   * @param array $types
   *   An array of required types of communications.
   *
   * @return array
   *   The request options array.
   */
  protected function buildCommunicationRequest(array $ids, array $types) {
    // CusCommunications?$filter=(MasterCustomerId eq '00000357' or
    // MasterCustomerId eq '00000159') and CommLocationCodeString eq 'WORK' and
    // (CommTypeCodeString eq 'EMAIL' or CommTypeCodeString eq 'PHONE')
    // &$select=CommTypeCodeString,FormattedPhoneAddress,CountryCode .
    $path_element = 'CusCommunications';
    $filter = $this->requestHelper->addFilter('eq', 'MasterCustomerId', $ids, TRUE);
    $filter .= $this->requestHelper->addFilter('eq', 'CommTypeCodeString', $types);
    $filter .= $this->requestHelper->addFilter('eq', 'CommLocationCodeString', ['WORK']);
    $query = [
      '$filter' => $filter,
      '$select' => 'MasterCustomerId,CommTypeCodeString,FormattedPhoneAddress,CountryCode',
    ];
    return $this->requestHelper->buildGetRequest($path_element, $query);
  }

  /**
   * Helper function for separating requests.
   *
   * @param array $ids
   *   An array of member IDs.
   * @param string $collection
   *   The requested collection.
   * @param array $options
   *   An additional array of requested options.
   *
   * @return array
   *   The requests options array.
   */
  protected function separateRequest(array $ids, $collection, array $options = []) {
    $requests_options = [];
    $chunked = array_chunk($ids, 20);
    foreach ($chunked as $chunk) {
      switch ($collection) {
        case 'job_title':
          $requests_options[] = $this->buildCommitteeRequest($chunk);
          break;

        case 'info':
          $requests_options[] = $this->buildMembersInfoRequest($chunk);
          break;

        case 'address':
          $requests_options[] = $this->buildAddressRequest($chunk);
          break;

        case 'communication':
          $requests_options[] = $this->buildCommunicationRequest($chunk, $options);
          break;
      }
    }
    return $requests_options;
  }

  /**
   * Helper function for sorting data.
   *
   * @param array $data
   *   The Data array.
   *
   * @return array
   *   The Data array.
   */
  protected function sort(array &$data) {
    foreach ($data as &$value) {
      ksort($value);
    }
    return ksort($data);
  }

}
