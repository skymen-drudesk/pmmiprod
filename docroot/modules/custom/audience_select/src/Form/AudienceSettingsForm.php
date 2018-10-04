<?php

namespace Drupal\audience_select\Form;

use Drupal\audience_select\Service\AudienceManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AudienceSettingsForm.
 *
 * @package Drupal\audience_select\Form
 */
class AudienceSettingsForm extends ConfigFormBase {

  /**
   * The audience manager service.
   *
   * @var \Drupal\audience_select\Service\AudienceManager
   */
  protected $AudienceManager;

  /**
   * The configured audiences.
   *
   * @var null
   */
  protected $audiences;

  /**
   * {@inheritdoc}
   *
   *   The plugin implementation definition.
   *
   * @param \Drupal\audience_select\Service\AudienceManager $audience_manager
   *   The Audience manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AudienceManager $audience_manager) {
    parent::__construct($config_factory);
    $this->AudienceManager = $audience_manager;
    $this->audiences = $audience_manager->getData();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('audience_select.audience_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'audience_select.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'audience_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->AudienceManager->getConfig();
    $crawler_audience = $config->get('default_bot_audience');
    $form['gateway_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gateway Url'),
      '#description' => $this->t('Paths should start with /, ? or #.'),
      '#default_value' => $this->AudienceManager->getGateway(),
      '#element_validate' => [
        [
          get_called_class(),
          'validateUriElement',
        ],
      ],
    ];
    $form['excluded_pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Excluded Pages'),
      '#default_value' => $config->get('excluded_pages'),
      '#description' => $this->t(
        "Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. An example path is %user-wildcard for every user page. %front is the front page.",
        [
          '%user-wildcard' => '/user/*',
          '%front' => '<front>',
        ]
      ),
    ];
    $form['default_bot_audience'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Auidience'),
      '#description' => $this->t('Default Auidience for Bot/Crawler'),
      '#required' => TRUE,
      '#default_value' => $crawler_audience,
      '#empty_option' => $this->t('Select Audience'),
      '#options' => $this->AudienceManager->getOptionsList(),
    ];
    $form['audiences'] = [
      '#type' => 'table',
      '#description' => $this->t('Default Auidience for Bot/Crawler'),
      '#header' => [
        $this->t('Audience ID'),
        $this->t('Audience Title'),
        $this->t('Audience Redirect Url'),
        $this->t('Audience Image'),
        $this->t('Audience Operations'),
      ],
      '#attributes' => ['id' => 'audience-table'],
      '#empty' => $this->t('No audiences available.'),
      '#caption' => $this->t('You can`t delete Default Crawler Audience, until you remove selection from list.'),
    ];
    if (!empty($this->audiences)) {
      $audiences = $this->audiences;
      foreach ($audiences as $audience_id => $audience) {
        $form['audiences'][$audience_id] = [
          'audience_id' => [
            '#title' => $this->t('Audience ID'),
            '#title_display' => 'invisible',
            '#type' => 'textfield',
            '#disabled' => TRUE,
            '#default_value' => $audience_id,
            '#size' => 20,
            '#required' => TRUE,
          ],
          'audience_title' => [
            '#title' => $this->t('Audience Title'),
            '#title_display' => 'invisible',
            '#type' => 'textfield',
            '#default_value' => $audience['audience_title'],
            '#required' => TRUE,
          ],
          'audience_redirect_url' => [
            '#title' => $this->t('Audience Redirect Url'),
            '#title_display' => 'invisible',
            '#type' => 'entity_autocomplete',
            '#target_type' => 'node',
            '#size' => 20,
            '#default_value' => $audience['audience_redirect_url'],
            '#placeholder' => $this->t('Input URL'),
            '#maxlength' => 200,
            '#attributes' => [
              'data-autocomplete-first-character-blacklist' => '/#?',
            ],
            '#element_validate' => [
              [
                get_called_class(),
                'validateUriElement',
              ],
            ],
            '#process_default_value' => FALSE,
            '#field_prefix' => rtrim(Url::fromRoute('<front>', [], ['absolute' => TRUE])
              ->toString(), '/'),
          ],
          'audience_image' => [
            '#type' => 'managed_file',
            '#title' => $this->t('Image'),
            '#title_display' => 'invisible',
            '#upload_location' => 'public://audience/image/',
            '#default_value' => (!empty($audience['audience_image'])) ? $audience['audience_image'] : NULL,
            '#upload_validators' => [
              'file_validate_extensions' => ['png jpg jpeg'],
            ],
          ],
        ];
        $access = $crawler_audience != $audience_id ? TRUE : FALSE;
        // Operations column.
        $form['audiences'][$audience_id]['operations'] = [
          '#type' => 'operations',
          '#links' => [],
          '#access' => $access,
        ];
        $form['audiences'][$audience_id]['operations']['#links']['delete'] = [
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('audience_select.audience_settings_delete_form', ['audience_id' => $audience_id]),
        ];
      }
    }

    // Add empty row.
    $form['new_audience'] = [
      '#type' => 'details',
      '#title' => $this->t('Add a new Audience'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['new_audience']['audience_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Audience Title'),
      '#size' => 48,
    ];
    $form['new_audience']['audience_id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Audience ID'),
      '#maxlength' => 20,
      '#required' => FALSE,
      '#machine_name' => [
        'exists' => ['Drupal\audience_select\Service\AudienceManager', 'load'],
        'label' => $this->t('Audience ID'),
        'replace_pattern' => '[^a-z0-9_.]+',
        'source' => ['new_audience', 'audience_title'],
      ],
      '#description' => t('A unique machine-readable name for this Audience. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL, in which underscores will be converted into hyphens.'),
      '#states' => [
        'required' => [
          ':input[name="new_audience[audience_title]"]' => ['filled' => TRUE],
        ],
      ],
    ];
    $form['new_audience']['audience_redirect_url'] = [
      '#title' => $this->t('Audience Redirect Url'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#description' => $this->t('Referenced to node. Manually entered paths should start with /, ? or #.'),
      '#size' => 30,
      '#placeholder' => $this->t('Input URL'),
      '#maxlength' => 200,
      '#attributes' => [
        'data-autocomplete-first-character-blacklist' => '/#?',
      ],
      '#element_validate' => [
        [
          get_called_class(),
          'validateUriElement',
        ],
      ],
      '#process_default_value' => FALSE,
      '#states' => [
        'required' => [
          ':input[name="new_audience[audience_title]"]' => ['filled' => TRUE],
        ],
      ],
      '#field_prefix' => rtrim(Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(), '/'),
    ];
    $form['new_audience']['audience_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Audience Image'),
      '#required' => FALSE,
      '#description' => $this->t('Default background image for Audience block'),
      '#upload_location' => 'public://audience/image/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $unique_values = [];

    // Check all mappings.
    if ($form_state->hasValue('audiences')) {
      $audiences = $form_state->getValue('audiences');
      if (!empty($audiences)) {
        foreach ($audiences as $key => $data) {
          $unique_values[$data['audience_id']]['audience_title'] = $data['audience_title'];
          $unique_values[$data['audience_id']]['audience_redirect_url'] = $data['audience_redirect_url'];
          $unique_values[$data['audience_id']]['audience_image'] = $data['audience_image'];
        }
      }
    }

    // Check new audience.
    $data = $form_state->getValue('new_audience');
    if (!empty($data['audience_id'])) {
      $temp_value = [];
      foreach ($data as $key => $value) {
        if ($key == 'audience_id' && array_key_exists($value, $unique_values)) {
          $form_state->setErrorByName('audiences][' . $key . '][audience_id', $this->t('Audience ID must be unique.'));
        }
        elseif (empty($value) && $key != 'audience_image') {
          $form_state->setErrorByName('new_audience][' . $key, $this->t('This field is required.'));
        }
        else {
          $temp_value[$key] = $value;
        }
      }
      $unique_values[$data['audience_id']] = $temp_value;
    }

    $form_state->set('audiences', $unique_values);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mappings = $form_state->get('audiences');
    $config = $this->config('audience_select.settings');
    if (!empty($mappings)) {
      $config->setData(['map' => $mappings]);
      foreach ($mappings as $audience) {
        if (!empty($audience['audience_image'])) {
          $image = File::load($audience['audience_image'][0]);
          if ($image->isTemporary()) {
            $image->setPermanent();
            $image->save();
            /** @var \Drupal\file\FileUsage\DatabaseFileUsageBackend $file_usage */
            $file_usage = \Drupal::service('file.usage');
            $file_usage->add($image, 'audience_select', 'user', 1);
          }
        }
      }
    }
    $config->set('gateway_url', $form_state->getValue('gateway_url'));
    $config->set('excluded_pages', $form_state->getValue('excluded_pages'));
    $config->set('default_bot_audience', $form_state->getValue('default_bot_audience'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Form element validation handler for the 'uri' element.
   *
   * Disallows saving inaccessible or untrusted URLs.
   */
  public static function validateUriElement($element, FormStateInterface $form_state, $form) {
    LinkWidget::validateUriElement($element, $form_state, $form);
  }

}
