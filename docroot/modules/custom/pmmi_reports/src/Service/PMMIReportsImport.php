<?php

namespace Drupal\pmmi_reports\Service;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\pmmi_psdata\Service\PMMIDataRequestHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Utility\DiffArray;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class PMMIReportsImport.
 *
 * @package Drupal\pmmi_reports
 */
class PMMIReportsImport {

  use StringTranslationTrait;

  /**
   * Default reports categories.
   *
   * @var array
   */
  const DEFAULT_CATEGORIES = [
    'BENCHMARKING',
    'ECONOMIC-TRENDS',
    'INDUSTRY-RPTS',
    'INTL-RESEARCH',
  ];

  /**
   * PMMIDataRequestHelper service.
   *
   * @var PMMIDataRequestHelper
   */
  protected $requestHelper;

  /**
   * QueueWorkerManager service.
   *
   * @var QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * The key value store to use.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $reportsImportStore;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * PMMIReportsImport constructor.
   *
   * @param PMMIDataRequestHelper $request_helper
   *   PMMI Data request helper.
   * @param ReportsImportStorageInterface $reports_storage
   *   The key value store to use.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to get related configs.
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   The queue factory.
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queue_manager
   *   The queue plugin manager.
   */
  public function __construct(
    PMMIDataRequestHelper $request_helper,
    ReportsImportStorageInterface $reports_storage,
    ConfigFactoryInterface $config_factory,
    QueueFactory $queue,
    QueueWorkerManagerInterface $queue_manager
  ) {
    $this->requestHelper = $request_helper;
    $this->reportsImportStore = $reports_storage;
    $this->configFactory = $config_factory;
    $this->queueManager = $queue_manager;
    $this->queue = $queue;

  }

  /**
   * Fetching and add to queue report items.
   *
   * @param bool $show_message
   *   Show done message or not.
   */
  public function fetchContent($show_message = TRUE) {
    $int_count = 0;
    $int_max_tries = 3;
    while ($int_count < $int_max_tries) {
      try {
        $reports_data = $this->getReportsData();
        $stored_items = $this->reportsImportStore->getAll();
        $fetched_items = [];
        $queue = $this->queue->get('pmmi_reports_import');
        foreach ($reports_data as $id => $item) {
          if (!isset($stored_items[$id]) || $diff = DiffArray::diffAssocRecursive($stored_items[$id], $item)) {
            $queue->createItem([$id => $item]);
            $fetched_items[$id] = $item;
          }
        }
        // Save fetched items to keyValue storage.
        if (!empty($fetched_items)) {
          $this->reportsImportStore->setMultiple($fetched_items);
        }
        // Show status message.
        if ($show_message) {
          drupal_set_message($this->formatPlural(count($fetched_items), 'Fetched 1 item', 'Fetched @count items.'));
        }
        \Drupal::logger('PMMI Reports')
          ->info("Successfully fetched reports data. Try number {$int_count}");
        break;
      } catch (\Exception $e) {
        \Drupal::logger('PMMI Reports')
          ->error("Error found when fetching reports data. Try number {$int_count}: " . $e->getMessage());
        $int_count++;
      }
    }
  }

  /**
   * Process queue cleaning.
   *
   * This will delete ALL data from queue table and keyValue table
   * with fetched content.
   *
   * @param bool $show_message
   *   Show done message or not.
   */
  public function cleanQueue($show_message = TRUE) {
    $this->queue->get('pmmi_reports_import')->deleteQueue();
    $this->reportsImportStore->deleteAll();

    // Show status message.
    if ($show_message) {
      drupal_set_message(t('Cleaning done.'));
    }
  }

  /**
   * Get all reports data.
   */
  protected function getReportsData() {
    $settings = $this->configFactory->get('pmmi_reports.import_settings');
    $categories = $settings->get('categories') ?? $this::DEFAULT_CATEGORIES;
    $filter = $this->requestHelper->addFilter('eq', 'ProductClassCodeString', $categories, TRUE);
    $query = [
      '$filter' => $filter,
    ];

    // Process products request.
    $request_options = $this->requestHelper->buildGetRequest('WebProductViews', $query);
    $reports_data = [];
    $report_ids = [];
    if ($response_data = $this->requestHelper->handleRequest($request_options)) {
      foreach ($response_data as $item) {
        $reports_data[$item->ProductId] = $this->collectItemData($item);
        $report_ids[] = $item->ProductId;
      }
    }

    // Process related links requests based on returned product id's higher.
    $related_links_request_opts = $this->buildLinksRequest($report_ids);
    if ($related_links_responce = $this->requestHelper->handleAsyncRequests($related_links_request_opts, 'postAsync', 'xml', 'ProductRelatedLinks')) {
      foreach ($related_links_responce as $item) {
        $reports_data[(string) $item->ProductID]['links'][] = $this->collectItemData($item, 'links');
      }
    }

    // Process prices requests based on returned product id's higher.
    $prices_requests = $this->buildPricesRequest($report_ids);
    if ($prices_response = $this->requestHelper->handleAsyncRequests($prices_requests)) {
      foreach ($prices_response as $item) {
        $reports_data[$item->ProductId] += $this->collectItemData($item, 'product_price');
      }
    }
    return $reports_data;
  }

  /**
   * Prepare reports item.
   */
  public function prepareItems($value, $type = 'date') {
    switch ($type) {
      case 'date':
        $value = $this->requestHelper->formatDate($value, DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
        break;
    }

    return $value;
  }

  /**
   * Mapping info that should be used in further import and saving processes.
   *
   * @return array
   *   Mapped fields.
   */
  protected function fieldsMapping() {
    return [
      'product_data' => [
        'title' => 'ShortName',
        'image' => 'LargeImageFileName',
        'status_date' => 'ProductStatusDate',
        'start_date' => 'StartDate',
        'available_date' => 'AvailableDate',
        'body' => 'WebShortDescription',
        'summary' => 'WebLongDescription',
        'category' => 'ProductClassCodeString',
        'member_only_flag' => 'MembersOnlyFlag',
      ],
      'product_price' => [
        'mem_price' => 'MemPrice',
        'mem_currency_symbol' => 'MemCurrencySymbol',
        'list_price' => 'ListPrice',
        'list_currency_symbol' => 'ListCurrencySymbol',
      ],
      'links' => [
        'title' => 'DisplayLabel',
        'url' => 'URL',
      ],
    ];
  }

  /**
   * Build requests to "ProductYourPriceInfos" personify collection.
   *
   * @param array $ids
   *   List of product ids.
   *
   * @return array
   *   List of prepared requests array.
   */
  protected function buildPricesRequest(array $ids) {
    $chunked = array_chunk($ids, 15);
    $requests_options = [];
    foreach ($chunked as $chunk) {
      $filter = $this->requestHelper->addFilter('eq', 'ProductId', $chunk, TRUE, TRUE);
      $query = [
        '$filter' => $filter,
      ];
      $requests_options[] = $this->requestHelper->buildGetRequest('ProductYourPriceInfos', $query);
    }
    return $requests_options;
  }

  /**
   * Build requests to "GetProductRelatedLinks" personify collection.
   *
   * @param array $ids
   *   List of product ids.
   *
   * @return array
   *   List of prepared requests array.
   */
  protected function buildLinksRequest(array $ids) {
    $chunked = array_chunk($ids, 15);
    $requests_options = [];
    $request = $this->requestHelper->buildGetRequest('GetProductRelatedLinks', [], 'application/xml');
    foreach ($chunked as $chunks) {
      foreach ($chunks as $chunk) {
        $body = <<<XML
<GetProductRelatedLinksInput>
  <ProductId>$chunk</ProductId>
  <SubsystemCode>ECD</SubsystemCode>
</GetProductRelatedLinksInput>
XML;
        $request['params']['body'] = $body;
        $requests_options[] = $request;
      }
    }
    return $requests_options;
  }

  /**
   * Parse items after personify response and get only needed data.
   *
   * @param object $item
   *   Personify response item.
   * @param string $map_key
   *   Mapping key.
   *
   * @return array
   *   Sanitized array with item data.
   */
  protected function collectItemData($item, $map_key = 'product_data') {
    $data = [];
    $mapping = $this->fieldsMapping();

    $date_fields = ['status_date', 'available_date', 'start_date'];
    foreach ($mapping[$map_key] as $key => $item_property) {
      if (isset($item->{$item_property})) {
        $value = in_array($key, $date_fields) ? $this->prepareItems($item->{$item_property}, 'date') : $item->{$item_property};
        $data[$key] = (string) $value;
      }
    }
    return $data;
  }

}
