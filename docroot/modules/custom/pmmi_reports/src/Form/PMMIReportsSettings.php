<?php

namespace Drupal\pmmi_reports\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pmmi_reports\Service\PMMIReportsImport;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;

/**
 * Class PMMIReportsSettings.
 *
 * @package Drupal\pmmi_reports\Form
 */
class PMMIReportsSettings extends ConfigFormBase {

  /**
   * QueueWorkerManager service.
   *
   * @var QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    QueueWorkerManagerInterface $queue_manager
  ) {
    parent::__construct($config_factory);
    $this->queueManager = $queue_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.queue_worker')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pmmi_reports.import_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_reports_import_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('pmmi_reports.import_settings');
    $categories_list = implode(', ', $config->get('categories'));
    $form['categories'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Categories to import'),
      '#description' => $this->t('List categories to import separated by comma.'),
      '#default_value' => $categories_list,
    ];
    $form['images_base_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Personify images base path.'),
      '#description' => $this->t('This url used for getting personify product images. Should be in format like: <em>https://pmmiprod3ebiz.personifycloud.com/ProductImages/</em>'),
      '#default_value' => $config->get('images_base_path'),
    ];
    $form['actions']['fetch'] = [
      '#type' => 'submit',
      '#value' => $this->t('Fetch content for queue'),
      '#submit' => [[$this, 'fetchContent']],
    ];
    $form['import'] = [
      '#type' => 'details',
      '#title' => $this->t('Run import queue manually'),
      '#open' => TRUE,
    ];
    $form['import']['fetch_flag'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fetch before import'),
      '#default_value' => $this->t('Import content'),
    ];
    $form['import']['actions'] = ['#type' => 'actions'];
    $form['import']['actions']['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import content'),
      '#submit' => [[$this, 'importContent']],
    ];
    $form['clear'] = [
      '#type' => 'details',
      '#title' => $this->t('Clear queue and fetched content from DB store.'),
      '#open' => FALSE,
    ];
    $form['clear']['clear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear all queued data'),
      '#submit' => [[$this, 'cleanQueue']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $categories = explode(', ', $form_state->getValue('categories'));
    $configs = $this->config('pmmi_reports.import_settings');
    $configs->set('categories', $categories);
    $configs->set('images_base_path', $form_state->getValue('images_base_path'));
    $configs->save();
  }

  /**
   * Fetch content submit.
   */
  public function fetchContent() {
    $reports_service = \Drupal::service('pmmi_reports.reports_import');
    $reports_service->fetchContent();
  }

  /**
   * Clean queue submit.
   */
  public function cleanQueue() {
    $reports_service = \Drupal::service('pmmi_reports.reports_import');
    $reports_service->cleanQueue();
  }

  /**
   * Import content submit.
   */
  public function importContent(array &$form, FormStateInterface $form_state) {
    // If checkbox selected run content fetching.
    if ($form_state->getValue('fetch_flag')) {
      $this->fetchContent();
    }
    $batch = [
      'title' => $this->t('Processing report items queue.'),
      'operations' => [
        [[$this, 'processQueue'], []],
      ],
      'finished' => [$this, 'processQueueFinish'],
      'progress_message' => '@percentage% complete. Time elapsed: @elapsed',
    ];
    batch_set($batch);
  }

  /**
   * Process reports queue batch operation.
   */
  public function processQueue(&$context) {
    $queue = \Drupal::queue('pmmi_reports_import');
    $sandbox = &$context['sandbox'];
    if (empty($sandbox)) {
      $sandbox['progress'] = 0;
      $sandbox['max'] = $queue->numberOfItems();
    }
    $queue_worker = $this->queueManager->createInstance('pmmi_reports_import');

    if ($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
        $context['sandbox']['progress']++;
      }
      catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
      }
      catch (\Exception $e) {
        watchdog_exception('reports import', $e);
      }
    }
    else {
      $sandbox['progress'] = $sandbox['max'];
    }
    $context['finished'] = $sandbox['progress'] / $sandbox['max'];
  }

  /**
   * Batch finish callback.
   */
  public function processQueueFinish($success, $results) {
    drupal_set_message(t('Import done.'));
  }

}
