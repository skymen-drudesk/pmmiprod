<?php

namespace Drupal\pmmi_psdata\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\pmmi_sso\Service\PMMISSOHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pmmi_psdata\Service\PMMIDataCollector;

/**
 * Provides a 'PMMICompanyStaffBlock' block.
 *
 * @Block(
 *  id = "pmmi_company_staff_block",
 *  admin_label = @Translation("PMMI Company Staff Block"),
 *  category = @Translation("PMMI Data Services")
 * )
 */
class PMMICompanyStaffBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\pmmi_psdata\Service\PMMIDataCollector definition.
   *
   * @var \Drupal\pmmi_psdata\Service\PMMIDataCollector
   */
  protected $dataCollector;

  /**
   * Construct.
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
  public function setConfiguration(array $configuration) {
    $this->configuration = array_merge(
      $this->baseConfigurationDefaults(),
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'company' => [
          'label' => '',
          'id' => '',
          'sub_id' => 0,
          'address' => ['BUSINESS'],
          'country_code' => 'USA',
          'comm_type' => ['EMAIL', 'PHONE', 'FAX'],
          'comm_location' => ['WORK'],
          'method' => 'ci_rel',
          'method_data' => '',
          'sort_empl' => [],
          'comm_empl' => ['EMAIL', 'PHONE', 'FAX'],
          'expiration' => 86400,
        ],
        'staff' => [
          'enabled' => TRUE,
          'label' => '',
          'comm_empl' => ['EMAIL', 'PHONE', 'FAX'],
          'expiration' => 86400,
        ],

      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['company'] = [
      '#type' => 'fieldset',
      '#title' => 'Company section',
      '#description' => $this->t("Company options"),
      '#tree' => TRUE,
    ];
    $form['company']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company label'),
      '#description' => $this->t('Label for company section'),
      '#default_value' => $this->configuration['company']['label'],
      '#size' => 20,
      '#weight' => 1,
    ];
    $form['company']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company MasterCustomerId'),
      '#description' => $this->t('Personify Service MasterCustomerIds for the company (02010445)'),
      '#default_value' => $this->configuration['company']['id'],
      '#required' => TRUE,
      '#size' => 20,
      '#pattern' => '[0-9]{8}',
      '#weight' => 1,
    ];
    $form['company']['sub_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Company SubCustomerId'),
      '#description' => $this->t('Personify Service MasterCustomerIds for the company (0)'),
      '#default_value' => $this->configuration['company']['sub_id'],
      '#required' => TRUE,
      '#min' => 0,
      '#step' => 1,
      '#size' => 2,
      '#weight' => 2,
    ];
    $form['company']['address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company AddressTypeCodeString'),
      '#description' => $this->t('Company AddressTypeCodeString (BUSINESS or BUSINESS2 ...)'),
      '#default_value' => $this->formatValue($this->configuration['company']['address'], TRUE),
      '#required' => TRUE,
      '#size' => 20,
      '#weight' => 3,
    ];
    $form['company']['country_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company CountryCode'),
      '#description' => $this->t('Company CountryCode 1 item (USA or MEX ...)'),
      '#default_value' => $this->configuration['company']['country_code'],
      '#required' => TRUE,
      '#size' => 20,
      '#weight' => 4,
    ];
    $form['company']['comm_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company CommTypeCodeString'),
      '#description' => $this->t('Company CommTypeCodeString separated by comma (EMAIL, PHONE, FAX, WEB)'),
      '#default_value' => $this->formatValue($this->configuration['company']['comm_type'], TRUE),
      '#required' => TRUE,
      '#size' => 20,
      '#weight' => 5,
    ];
    $form['company']['comm_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company CommLocationCodeString'),
      '#description' => $this->t('Company CommLocationCodeString separated by comma (WORK, WORK2, TOLLFREE)'),
      '#default_value' => $this->formatValue($this->configuration['company']['comm_location'], TRUE),
      '#required' => TRUE,
      '#size' => 20,
      '#weight' => 6,
    ];
    $form['company']['method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Method to get Information about company Staff'),
      '#default_value' => $this->configuration['company']['method'],
      '#required' => TRUE,
      '#options' => [
        'code' => 'By CustomerClassCode',
        'ci_rel' => 'By CustomerInfos\Relationship',
      ],
      '#weight' => 7,
    ];
    $form['company']['method_data'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Method Data'),
      '#description' => $this->t('Method Data'),
      '#default_value' => $this->configuration['company']['method_data'],
      '#visible' => FALSE,
      '#size' => 20,
      '#states' => [
        'visible' => [
          ':input[name="settings[company][method]"]' => ['value' => 'code'],
        ],
        'required' => [
          ':input[name="settings[company][method]"]' => ['value' => 'code'],
        ],
      ],
      '#weight' => 8,
    ];
    $form['company']['sort_empl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company section: People'),
      '#description' => $this->t('Company section: Sort and display visible people (Format: FirstName LastName, delimeter - comma)'),
      '#default_value' => $this->formatValue($this->configuration['company']['sort_empl'], TRUE),
      '#visible' => FALSE,
      '#size' => 64,
      '#weight' => 9,
    ];
    $form['company']['comm_empl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company section: Filter Communications Type'),
      '#description' => $this->t('Company section: Filter Communications Type (EMAIL,PHONE,FAX)'),
      '#default_value' => $this->formatValue($this->configuration['company']['comm_empl'], TRUE),
      '#visible' => FALSE,
      '#size' => 64,
      '#weight' => 9,
    ];
    $form['company']['expiration'] = [
      '#type' => 'number',
      '#title' => $this->t('Company section Expiration time'),
      '#min' => 10,
      '#step' => 10,
      '#description' => $this->t(
        "The default value, in seconds, to be used as the expiration time for 
        the Company section."
      ),
      '#default_value' => $this->configuration['company']['expiration'],
      '#required' => TRUE,
      '#weight' => 10,
    ];
    $form['staff'] = [
      '#type' => 'fieldset',
      '#title' => 'Staff section',
      '#description' => $this->t("Staff section options"),
      '#tree' => TRUE,
    ];
    $form['staff']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Staff Section'),
      '#description' => $this->t('Display Staff Section in the block.'),
      '#default_value' => $this->configuration['staff']['enabled'],
      '#weight' => 1,
    ];
    $form['staff']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Section label'),
      '#description' => $this->t('Label to display in the section'),
      '#default_value' => $this->configuration['staff']['label'],
      '#weight' => 2,
      '#states' => [
        'visible' => [
          ':input[name="settings[staff][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['staff']['comm_empl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company section: Filter Communications Type'),
      '#description' => $this->t('Company section: Filter Communications Type (EMAIL,PHONE,FAX)'),
      '#default_value' => $this->formatValue($this->configuration['staff']['comm_empl'], TRUE),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => 5,
      '#states' => [
        'visible' => [
          ':input[name="settings[staff][enabled]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="settings[staff][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['staff']['expiration'] = [
      '#type' => 'number',
      '#title' => $this->t('Staff section Expiration time'),
      '#min' => 10,
      '#step' => 10,
      '#description' => $this->t(
        "The default value, in seconds, to be used as the expiration time for 
        the Staff section."
      ),
      '#default_value' => $this->configuration['staff']['expiration'],
      '#required' => TRUE,
      '#weight' => 5,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    if (!array_key_exists('uuid', $this->configuration)) {
      $old_cid = PMMISSOHelper::PROVIDER . ':' . md5(serialize($this->configuration));
    }
    if (
      $form_state->getValue(['company', 'method']) == 'code' &&
      $form_state->hasValue(['company', 'method_data'])
    ) {
      $method_data = $form_state->getValue(['company', 'method_data']);
    }
    else {
      $method_data = '';
    }
    $settings = [
      'company' => [
        'label' => $form_state->getValue(['company', 'label']),
        'id' => $form_state->getValue(['company', 'id']),
        'sub_id' => $form_state->getValue(['company', 'sub_id']),
        'address' => $this->formatValue($form_state->getValue([
          'company',
          'address',
        ])),
        'country_code' => $form_state->getValue(['company', 'country_code']),
        'comm_type' => $this->formatValue($form_state->getValue([
          'company',
          'comm_type',
        ])),
        'comm_location' => $this->formatValue($form_state->getValue([
          'company',
          'comm_location',
        ])),
        'method' => $form_state->getValue(['company', 'method']),
        'method_data' => $method_data,
        'sort_empl' => $this->formatValue($form_state->getValue([
          'company',
          'sort_empl',
        ])),
        'comm_empl' => $this->formatValue($form_state->getValue([
          'company',
          'comm_empl',
        ])),
        'expiration' => $form_state->getValue(['company', 'expiration']),
      ],
      'staff' => [
        'enabled' => $form_state->getValue(['staff', 'enabled']),
        'label' => $form_state->getValue(['staff', 'label']),
        'comm_empl' => $this->formatValue($form_state->getValue([
          'staff',
          'comm_empl',
        ])),
        'expiration' => $form_state->getValue(['staff', 'expiration']),
      ],
    ];
    $this->configuration = array_merge($this->configuration, $settings);
    if (array_key_exists('uuid', $this->configuration)) {
      $cid = PMMISSOHelper::PROVIDER . ':' . $this->configuration['uuid'];
      \Drupal::cache()->invalidate($cid);
    }
    else {
      $cid = PMMISSOHelper::PROVIDER . ':' . md5(serialize($this->configuration));
      $old_cid == $cid ?: \Drupal::cache()->delete($old_cid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $options = $this->dataCollector->createObjectFromOptions($this->configuration, 'company');
    $data = $this->dataCollector->getData($options);
    $build['#theme'] = 'pmmi_psdata_company_staff_block';
    $build['#data'] = $data;
    $build['#staff_enabled'] = $this->configuration['staff']['enabled'];
    $build['#staff_label'] = $this->configuration['staff']['label'];
    $build['#cache']['tags'] = [$this->dataCollector->buildCid($options, 'main')];
    return $build;
  }

  /**
   * Format value - helper function.
   *
   * @param array|string $value
   *   The value to filter.
   * @param bool $implode
   *   Implode flag.
   *
   * @return array|string
   *   Filtered value.
   */
  protected function formatValue($value, $implode = FALSE) {
    if ($implode) {
      $value = array_unique($value);
      return implode(',', $value);
    }
    else {
      $data = explode(',', $value);
      $data = array_map('trim', $data);
      $data = array_unique($data);
      return array_filter($data);
    }
  }

}
