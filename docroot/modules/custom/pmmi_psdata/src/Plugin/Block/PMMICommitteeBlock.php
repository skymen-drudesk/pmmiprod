<?php

namespace Drupal\pmmi_psdata\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pmmi_psdata\Service\PMMIDataCollector;

/**
 * Provides a 'PMMICommitteeBlock' block.
 *
 * @Block(
 *  id = "pmmi_committee_block",
 *  admin_label = @Translation("PMMI Committee Block"),
 *  category = @Translation("PMMI Data Services")
 * )
 */
class PMMICommitteeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\pmmi_psdata\Service\PMMIDataCollector definition.
   *
   * @var \Drupal\pmmi_psdata\Service\PMMIDataCollector
   */
  protected $dataCollector;

  /**
   * Construct PMMICommitteeBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param PMMIDataCollector $psdata_collector
   *   The PMMIDataCollector service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PMMIDataCollector $psdata_collector
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dataCollector = $psdata_collector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('pmmi_psdata.collector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'committee_id' => '',
        'sort_options' => '',
      ] + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['committee_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Committee ID'),
      '#description' => $this->t('Personify Service CommitteeMasterCustomer'),
      '#default_value' => $this->configuration['committee_id'],
      '#required' => TRUE,
      '#maxlength' => 8,
      '#size' => 20,
      '#pattern' => '[A-Z][0-9]{7}',
      '#weight' => 1,
    ];
    $form['sort_options'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sorting Order'),
      '#description' => $this->t('Sorting order for the PositionCodeDescriptionDisplay property. Example: Chairman, Vice Chairman, Member'),
      '#default_value' => $this->configuration['sort_options'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => 4,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['committee_id'] = $form_state->getValue('committee_id');
    $this->configuration['sort_options'] = $form_state->getValue('sort_options');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $options = $this->dataCollector->createObjectFromOptions($this->configuration, 'committee');
    $data = $this->dataCollector->getData($options);
    if (!empty($data) && !empty($this->configuration['sort_options'])) {
      $this->sort($data);
    }
    $build['#theme'] = 'pmmi_psdata_committee_block';
    $build['#data'] = $data;
    $build['#cache']['tags'] = [$this->dataCollector->buildCid($options, 'main')];
    return $build;
  }

  /**
   * Sort helper function.
   *
   * @param array $data
   *   The data array to sort.
   */
  protected function sort(array &$data) {
    $sort_options = explode(',', $this->configuration['sort_options']);
    $sort_options = array_map('strtolower', $sort_options);
    $sort_options = array_filter(array_map('trim', $sort_options));
    uksort($data, function ($key1, $key2) use ($sort_options) {
      return (array_search(strtolower($key1), $sort_options) > array_search(strtolower($key2), $sort_options));
    });
  }

}
