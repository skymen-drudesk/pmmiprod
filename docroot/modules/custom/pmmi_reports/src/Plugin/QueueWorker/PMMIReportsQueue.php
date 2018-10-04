<?php

namespace Drupal\pmmi_reports\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\pmmi_reports\Service\ReportsImportStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\Entity\Node;
use Drupal\Component\Utility\DiffArray;

/**
 * Processing reports import.
 *
 * @QueueWorker(
 *   id = "pmmi_reports_import",
 *   title = @Translation("Import reports data"),
 *   cron = {"time" = 60}
 * )
 */
class PMMIReportsQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

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
   * PMMIReportsImport constructor.
   *
   * @param array $configuration
   *   The configuration of the instance.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param ReportsImportStorageInterface $reports_storage
   *   The key value store to use.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to get related configs.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ReportsImportStorageInterface $reports_storage,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->reportsImportStore = $reports_storage;
    $this->configFactory = $config_factory;
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
      $container->get('pmmi_reports.reports_storage'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    $product_id = key($item);
    $data = reset($item);
    $prepared_data = $this->prepareNode($product_id, $data);
    // Check if node with current product id exists.
    if ($node = $this->getNodeByProductId($product_id)) {
      // Get diff between existing node and prepared array.
      if ($diff = $this->checkNode($node, $prepared_data)) {
        foreach ($diff as $field => $field_diff) {
          $node->set($field, $prepared_data[$field]);
        }
        $node->save();
      }
    }
    else {
      // Create new report node.
      $node = Node::create([
        'uid' => 1,
        'type' => 'report',

      ] + $prepared_data);
      $node->save();
    }
  }

  /**
   * Get existing node by product id.
   *
   * @param int $product_id
   *   Personify product id.
   *
   * @return Node|null
   *   Node object.
   */
  protected function getNodeByProductId($product_id) {
    $node = NULL;
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'report')
      ->condition('field_product_id', $product_id);
    if ($result = $query->execute()) {
      $node = Node::load(reset($result));
    }
    return $node;
  }

  /**
   * Prepare node fields based on import data.
   *
   * @param int $product_id
   *   Personify product id.
   * @param array $data
   *   Import data from personify.
   *
   * @return array
   *   Node values array.
   */
  protected function prepareNode($product_id, array $data) {
    return [
      'title' => $data['title'],
      'body' => [
        'value' => !empty($data['body']) ? $this->lowerTags($data['body']) : $data['title'],
        'summary' => $this->lowerTags($data['summary']),
        'format' => 'full_html',
      ],
      'field_member_price' => $data['mem_currency_symbol'] . $data['mem_price'],
      'field_non_member_price' => $data["member_only_flag"] == 1 ? '' : $data['list_currency_symbol'] . $data['list_price'],
      'field_product_id' => (string) $product_id,
      'field_product_status_date' => $data['status_date'],
      'field_available_from_date' => $data['available_date'],
      'field_category' => $this->getTermIdByProductClass($data['category']),
      'field_image' => $this->getImage($data['image']),
      'field_links' => isset($data['links']) ? $this->setLinks($data['links']) : NULL,
    ];
  }

  /**
   * Compare existing node with item data for diff.
   *
   * @param object $node
   *   Node object.
   * @param array $prepared_data
   *   Prepared node array from fetched content.
   *
   * @return array
   *   Existing $node and prepared content diff.
   */
  protected function checkNode($node, array $prepared_data) {
    $fields = array_keys($prepared_data);
    $current_node_data = [];
    foreach ($fields as $field) {
      switch ($field) {
        case 'field_image':
        case 'field_category':
          $current_node_data[$field] = $node->{$field}->target_id;
          break;

        case 'body':
          $value = $node->get($field)->getValue()
            ? $node->get($field)->getValue()[0]
            : ['value' => '', 'summary' => '', 'format' => 'full_html'];
          $current_node_data[$field] = $value;
          break;

        default:
          $current_node_data[$field] = $node->get($field)->value;

      }
    }
    return DiffArray::diffAssocRecursive($current_node_data, $prepared_data);
  }

  /**
   * Get category term.
   */
  public static function getTermIdByProductClass($product_class) {
    $term_id = NULL;
    $result = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'report_type')
      ->condition('field_personify_class', $product_class)
      ->execute();
    if (!empty($result)) {
      $term_id = reset($result);
    }
    return $term_id;
  }

  /**
   * Prepare image object.
   */
  protected function getImage($filename = '', $resave = FALSE) {
    $fid = NULL;
    if (!empty($filename)) {
      try {
        $image_base_path = $this->configFactory->get('pmmi_reports.import_settings')->get('images_base_path');
        $img_path = $image_base_path . $filename;
        $directory_path = 'public://report_images';
        $drupal_uri = $directory_path . '/' . $filename;
        if (!file_exists($drupal_uri) || $resave) {
          file_prepare_directory($directory_path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
          $data = ($resave && file_exists($drupal_uri))
            ? file_get_contents($drupal_uri)
            : file_get_contents($img_path);
          $file = file_save_data($data, $drupal_uri, FILE_EXISTS_REPLACE);
        }
        elseif ($file = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $drupal_uri])) {
          $file = reset($file);
        }
        else {
          // Re-save file if it physically exists but not stored in DB.
          $fid = $this->getImage($filename, TRUE);
        }
        if (!empty($file)) {
          $fid = $file->id();
        }
      }
      catch (\Exception $e) {
        watchdog_exception('reports import', $e);
      }
    }
    return $fid;
  }

  /**
   * Set related links.
   */
  protected function setLinks($links) {
    $data = [];
    if (!empty($links)) {
      foreach ($links as $link) {
        $data[] = [
          'uri' => $link['url'],
          'title' => $link['title'],
          'options' => [
            'attributes' => [
              'target' => '_blank',
            ],
          ],
        ];
      }
    }
    return $data;
  }

  /**
   * Change html tags to lower case.
   */
  public function lowerTags($value) {
    $new_value = preg_replace_callback('/(<\/?\b[A-Z0-9]+\b)(.*?>)/', function ($m) {
      return strtolower($m[1]) . $m[2];
    }, $value);
    return $new_value;
  }

}
