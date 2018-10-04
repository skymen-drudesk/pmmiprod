<?php

namespace Drupal\pmmi_psdata\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\pmmi_sso\Service\PMMISSOHelper;

/**
 * Class PMMIDataCollector.
 *
 * @package Drupal\pmmi_psdata
 */
class PMMIDataCollector {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Provider name.
   *
   * @var string
   */
  protected $provider = PMMISSOHelper::PROVIDER;

  /**
   * PMMIDataCollector constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_default
   *   The cache backend interface to use for the complete theme registry data.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   The queue factory.
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queue_manager
   *   The queue plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to get the installed themes.
   */
  public function __construct(
    CacheBackendInterface $cache_default,
    CacheTagsInvalidatorInterface $cache_tags_invalidator,
    QueueFactory $queue,
    QueueWorkerManagerInterface $queue_manager,
    ConfigFactoryInterface $config_factory
  ) {
    $this->cache = $cache_default;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->queue = $queue;
    $this->queueManager = $queue_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Get the main data array.
   *
   * @param object $options
   *   The type of requested data.
   *
   * @return array
   *   The array of data.
   */
  public function getData($options) {
    $data = NULL;
    $cid = $this->buildCid($options, 'main');
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }
    else {
      if ($options->type == 'company') {
        $this->buildCompanyData($options, $cid);
      }
      else {
        $qid = 'pmmi_psdata_committee_real';
        $this->processQueue($qid, $options);
      }
    }
    $this->invalidateTags([$cid]);
    return $this->cache->get($cid)->data;
  }

  /**
   * Wrapper for the function invalidateTags.
   *
   * @param array $tags
   *   The array og tags to invalidate.
   */
  public function invalidateTags(array $tags) {
    $this->cacheTagsInvalidator->invalidateTags($tags);
  }

  /**
   * Build company data array.
   *
   * @param object $options
   *   An object representing the current block settings.
   * @param string $main_cid
   *   Cache ID for the data.
   */
  private function buildCompanyData($options, $main_cid) {
    $data = [];
    // Get company data.
    $cid = $this->buildCid($options, 'company');
    if ($this->cache->get($cid)) {
      $company_data = $this->cache->get($cid)->data;
    }
    else {
      $company_data = $this->getCompanyData($options, $cid);
    }
    // Get staff data.
    $csid = $this->buildCid($options, 'staff');
    if ($this->cache->get($csid)) {
      $staff_data = $this->cache->get($csid)->data;
    }
    else {
      $staff_data = $this->getCompanyStaffData($options, $csid);
    }
    if (!empty($company_data) && !empty($staff_data)) {
      $data = $this->sortCompanyData($options, $company_data, $staff_data);
    }
    // Delete all empty values from array.
    $data = array_map('array_filter', $data);
    $data = array_filter($data);
    if ($data) {
      $tags = [$cid, $csid];
      $this->cache->set($main_cid, $data, CacheBackendInterface::CACHE_PERMANENT, $tags);
    }
  }

  /**
   * Sort company data array.
   *
   * @param object $options
   *   An object representing the current block settings.
   * @param array $company_data
   *   An array with main information about the company.
   * @param array $staff_data
   *   An array of information about the company's employees.
   *
   * @return array
   *   The main array with data about the company (sorted and filtered).
   */
  protected function sortCompanyData($options, array $company_data, array $staff_data) {
    $result = [];
    $country_code = $options->data['company']['country_code'];
    $result['company'] = $company_data[$options->id][$options->data['company']['country_code']];
    $staff_data = array_filter($staff_data, function ($row) use ($country_code) {
      return (
        array_key_exists('country', $row) && $row['country'] == $country_code
      );
    });
    if ($sort_empl = $options->data['company']['sort_empl']) {
      $result['company']['staff'] = $this->filterAndSortCommunications(
        $this->filterCompanyStaff($staff_data, $sort_empl),
        $options->data['company']['comm_empl']
      );
      $result['staff'] = $this->filterAndSortCommunications(
        array_diff_key($staff_data, $result['company']['staff']),
        $options->data['staff']['comm_empl']
      );
    }
    else {
      $result['staff'] = $this->filterAndSortCommunications(
        $staff_data,
        $options->data['staff']['comm_empl']
      );
    }
    if ($options->data['staff']['enabled']) {
      $result['staff'] = $this->sortByArrayKey($result['staff'], 'last_name');
    }
    return $result;
  }

  /**
   * Filter the company's employees to represent in the company section.
   *
   * @param array $array_to_filter
   *   An array of information about the company's employees.
   * @param array $filter_by
   *   An array of information about the necessary employees of the company for
   *   the company section.
   *
   * @return array
   *   Filtered array.
   */
  public function filterCompanyStaff(array $array_to_filter, array $filter_by) {
    $result = [];
    foreach ($filter_by as $employee) {
      $last_first_name = explode(' ', trim($employee));
      $result = array_replace($result, array_filter($array_to_filter, function ($row) use ($last_first_name) {
        return (
          strtolower(trim($row['first_name'])) == strtolower(trim($last_first_name[0])) &&
          strtolower(trim($row['last_name'])) == strtolower(trim($last_first_name[1]))
        );
      }));
    }
    return $result;
  }

  /**
   * Filter and sort information about the communication.
   *
   * @param array $array_to_sort
   *   An array of information about communication data.
   * @param array $array_by_sort
   *   An array of communication information that is allowed to be mapped.
   *
   * @return array
   *   Filtered and sorted array.
   */
  public function filterAndSortCommunications(array $array_to_sort, array $array_by_sort) {
    foreach ($array_to_sort as &$item) {
      if (!empty($item['comm'])) {
        // Get filter and sort keys.
        $sort_keys = array_flip(array_map('strtolower', $array_by_sort));
        // Get available keys in sorted array.
        $sort_keys = array_intersect_key($sort_keys, $item['comm']);
        $item['comm'] = array_intersect_key($item['comm'], $sort_keys);
        // Reorder values.
        $item['comm'] = array_replace($sort_keys, $item['comm']);
      }
    }
    return $array_to_sort;
  }

  /**
   * Sort information by array key.
   *
   * @param array $array_to_sort
   *   An array of information for sorting.
   * @param string $key
   *   Array key for sorting.
   *
   * @return array
   *   Sorted array.
   */
  public function sortByArrayKey(array $array_to_sort, $key) {
    uasort($array_to_sort, function ($a, $b) use ($key) {
      return strnatcmp($a[$key], $b[$key]);
    });
    return $array_to_sort;
  }

  /**
   * Get main information about the company.
   *
   * @param object $options
   *   An object representing the current block settings.
   * @param string $cid
   *   Array key for sorting.
   *
   * @return array
   *   Information about the company.
   */
  private function getCompanyData($options, $cid) {
    $qid = 'pmmi_psdata_company_real';
    $data_item = $options;
    $this->processQueue($qid, $data_item);
    return $this->cache->get($cid)->data;
  }

  /**
   * Get main information for the company staff section.
   *
   * @param object $options
   *   An object representing the current block settings.
   * @param string $cid
   *   Cache ID.
   *
   * @return array
   *   Information about the company staff.
   */
  private function getCompanyStaffData($options, $cid) {
    $qid = 'pmmi_psdata_staff_real';
    $data_item = $options;
    $this->processQueue($qid, $data_item);
    return $this->cache->get($cid)->data;
  }

  /**
   * Helper function for building Cache ID.
   *
   * @param object $options
   *   An object representing the current block settings.
   * @param string $type
   *   The type of cache ID.
   *
   * @return string
   *   Cache ID.
   */
  public function buildCid($options, $type) {
    $cid = '';
    $id = $options->id;
    switch ($type) {
      case 'main':
        if ($options->type == 'committee') {
          $cid = $this->provider . ':committee_' . $id;
        }
        else {
          $cid = $this->provider . ':' . $options->uuid;
        }
        break;

      case 'company':
        $additional_key = $this->convertOptions(
          $options->data['company']['comm_type'],
          $options->data['company']['comm_location']
        );
        $country = '_' . strtolower($options->data['company']['country_code']);
        $cid = $this->provider . ':' . $type . '_' . $id . $country . '_' . $additional_key;
        break;

      case 'staff':
        $method = $options->data['company']['method'];
        $comm_str = $this->convertOptions($options->data['company']['comm_empl'], $options->data['staff']['comm_empl']);
        $cid = $this->provider . ':' . $type . '_' . $method . '_' . $comm_str . '_' . $id;
        break;

    }
    return $cid;
  }

  /**
   * Helper function: converting arrays to a string.
   *
   * @param array $first
   *    The array of parameters.
   * @param array $second
   *    The array of parameters.
   *
   * @return string
   *    Values from arrays as a string.
   */
  private function convertOptions(array $first, array $second = []) {
    $first = array_map('strtolower', $first);
    $second = array_map('strtolower', $second);
    $result = array_merge($first, $second);
    sort($result);
    $result = array_unique($result);
    return implode('_', $result);
  }

  /**
   * Process the queue.
   *
   * @param string $qid
   *   The queue ID.
   * @param object $data_item
   *   The item to process.
   */
  public function processQueue($qid, $data_item) {
    $queue = $this->queue->get($qid);
    $queue->createItem($data_item);
    $queue_worker = $this->queueManager->createInstance($qid);
    while ($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
      }
      catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        break;
      }
      catch (\Exception $e) {
        watchdog_exception('pmmi_psdata', $e);
      }
    }
  }

  /**
   * Get the block type.
   *
   * @param array $config
   *   The array with block settings.
   *
   * @return string
   *   The block type.
   */
  public function getBlockType(array $config) {
    $type = '';
    switch ($config['id']) {
      case 'pmmi_committee_block':
        $type = 'committee';
        break;

      case 'pmmi_company_staff_block':
        $type = 'company';
    }
    return $type;
  }

  /**
   * Get out-of-date configurations for blocks.
   *
   * @param array $configs
   *   The array with blocks settings.
   *
   * @return array
   *   The array with out-of-date configurations for blocks.
   */
  public function getExpiredData(array $configs) {
    $result = [];
    $settings = $this->configFactory->get('pmmi_psdata.updatesettings');
    $committee_interval = $settings->get('committee.interval');
    $company_interval = $settings->get('company.interval');
    if (!$settings->get('committee.enabled')) {
      unset($configs['committee']);
    }
    if (!$settings->get('company.enabled')) {
      unset($configs['company']);
      unset($configs['company']);
    }
    foreach ($configs as $type => $collection) {
      $cache_coll = array_keys($collection);
      $cache_items = $this->cache->getMultiple($cache_coll);
      $result[$type] = array_intersect_key($collection, array_flip($cache_coll));
      foreach ($cache_items as $cid => $item) {
        switch ($type) {
          case 'committee':
            if (REQUEST_TIME >= ($item->expire - $committee_interval)) {
              $result[$type][$cid] = $collection[$cid];
            }
            break;

          case 'company';
          case 'staff';
            if (REQUEST_TIME >= ($item->expire - $company_interval)) {
              $result[$type][$cid] = $collection[$cid];
            }
            break;
        }
      }
    }
    return array_filter($result);
  }

  /**
   * Collect all configs for the blocks that need to be updated.
   *
   * @return array
   *   The array with blocks settings.
   */
  public function collectConfigsToUpdate() {
    $company_block_config = $this->configFactory->listAll('block.block.pmmicompanystaffblock');
    $committee_block_config = $this->configFactory->listAll('block.block.pmmicommitteeblock');
    $panels_variant_config = $this->configFactory->listAll('page_manager.page_variant');
    $all_configs = array_merge($company_block_config, $committee_block_config, $panels_variant_config);
    $configs = $this->configFactory->loadMultiple($all_configs);
    $result = [];
    foreach ($configs as $config) {
      $dependency = $config->get('dependencies.module');
      if (!empty($dependency)) {
        if (in_array('panels', $dependency) && in_array('pmmi_psdata', $dependency)) {
          foreach ($config->get('variant_settings.blocks') as $uuid => $block) {
            $type = $this->getBlockType($block);
            $this->fillArray($result, $block, $type);
          }
        }
        elseif (in_array('pmmi_psdata', $dependency)) {
          $settings = $config->get('settings');
          $type = $this->getBlockType($settings);
          $this->fillArray($result, $settings, $type);
        }
      }
    }
    return $result;
  }

  /**
   * Create object from options.
   *
   * @param array $config
   *   The array with block settings.
   * @param string $type
   *   The block type.
   *
   * @return object
   *   The object (used in Queue workers).
   */
  public function createObjectFromOptions(array $config, $type) {
    $item = new \stdClass();
    switch ($type) {
      case 'committee':
        $item->id = $config['committee_id'];
        $item->type = 'committee';
        $config = $this->configFactory->get('pmmi_psdata.updatesettings');
        if ($config->get('committee.enabled')) {
          $expiration = $config->get('committee.interval');
        }
        else {
          $expiration = CacheBackendInterface::CACHE_PERMANENT;
        }
        $item->expiration = $expiration;
        break;

      case 'company':
        $item->id = $config['company']['id'];
        $item->type = 'company';
        if (array_key_exists('uuid', $config)) {
          $uuid = $config['uuid'];
        }
        else {
          $uuid = md5(serialize($config));
        }
        $item->uuid = $uuid;
        $item->data = [
          'company' => $config['company'],
          'staff' => $config['staff'],
        ];
        break;
    }
    return $item;
  }

  /**
   * Helper function: To populate the result array with values.
   *
   * @param array $result
   *   The array to fill.
   * @param array $config
   *   The array with block settings.
   * @param string $type
   *   The block type.
   */
  public function fillArray(array &$result, array $config, $type) {
    $item = $this->createObjectFromOptions($config, $type);
    switch ($type) {
      case 'committee':
        $cid = $this->buildCid($item, 'main');
        $result[$type][$cid] = $item;
        break;

      case 'company':
        $cid = $this->buildCid($item, $type);
        $csid = $this->buildCid($item, 'staff');
        $result[$type][$cid] = $item;
        $result['staff'][$csid] = $item;
        break;
    }
  }

}
