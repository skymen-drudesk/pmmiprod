<?php

namespace Drupal\pmmi_sso\Form;

use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\pmmi_sso\Service\PMMISSOHelper;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PMMISSOSettings.
 *
 * @package Drupal\pmmi_sso\Form
 */
class PMMISSOSettings extends ConfigFormBase {

  /**
   * RequestPath condition that contains the paths to use for gateway.
   *
   * @var \Drupal\system\Plugin\Condition\RequestPath
   */
  protected $gatewayPaths;

  /**
   * Constructs a \Drupal\pmmi_sso\Form\PMMISSOSettings object.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param FactoryInterface $plugin_factory
   *   The condition plugin factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FactoryInterface $plugin_factory) {
    parent::__construct($config_factory);
    $this->gatewayPaths = $plugin_factory->createInstance('request_path');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pmmi_sso.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_sso_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pmmi_sso.settings');
    $form['sso'] = [
      '#type' => 'fieldset',
      '#title' => 'Personify SSO Services Data',
      '#description' => $this->t("Personify SSO service URIs and authentication data"),
    ];
    $form['login_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login URI'),
      '#maxlength' => 128,
      '#size' => 64,
      '#group' => 'sso',
      '#required' => TRUE,
      '#default_value' => $config->get('login_uri'),
    ];
    $form['service_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service URI'),
      '#maxlength' => 128,
      '#group' => 'sso',
      '#required' => TRUE,
      '#size' => 64,
      '#default_value' => $config->get('service_uri'),
    ];
    $form['vi'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor Identifier'),
      '#maxlength' => 64,
      '#size' => 64,
      '#group' => 'sso',
      '#required' => TRUE,
      '#default_value' => $config->get('vi'),
    ];
    $form['vu'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor username'),
      '#maxlength' => 64,
      '#size' => 64,
      '#group' => 'sso',
      '#required' => TRUE,
      '#default_value' => $config->get('vu'),
    ];
    $form['vp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor password (HEX)'),
      '#maxlength' => 32,
      '#size' => 64,
      '#group' => 'sso',
      '#required' => TRUE,
      '#default_value' => $config->get('vp'),
    ];
    $form['vib'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor initilization block (HEX)'),
      '#maxlength' => 32,
      '#size' => 64,
      '#group' => 'sso',
      '#required' => TRUE,
      '#default_value' => $config->get('vib'),
    ];
    $form['ims'] = [
      '#type' => 'fieldset',
      '#title' => 'Personify SSO IMS Data',
      '#group' => 'sso',
      '#description' => $this->t("Personify IM service URI and authentication data"),
    ];
    $form['ims_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IM Service URI'),
      '#maxlength' => 128,
      '#group' => 'ims',
      '#required' => TRUE,
      '#size' => 64,
      '#default_value' => $config->get('ims_uri'),
    ];
    $form['ims_vu'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IMS vendor username'),
      '#maxlength' => 64,
      '#size' => 64,
      '#group' => 'ims',
      '#required' => TRUE,
      '#default_value' => $config->get('ims_vu'),
    ];
    $form['ims_vp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IMS vendor password (HEX)'),
      '#maxlength' => 32,
      '#size' => 64,
      '#group' => 'ims',
      '#required' => TRUE,
      '#default_value' => $config->get('ims_vp'),
    ];
    $form['data_service'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Personify Data Service Information'),
      '#tree' => TRUE,
      '#description' => $this->t('Personify Endpoint and authentication data for the PMMI Data Service'),
    ];
    $form['data_service']['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Personify endpoint'),
      '#maxlength' => 128,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('data_service.endpoint'),
    ];
    $form['data_service']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Personify username'),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('data_service.username'),
    ];
    $form['data_service']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Personify password'),
      '#maxlength' => 32,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('data_service.password'),
    ];
    $form['user_accounts'] = [
      '#type' => 'details',
      '#title' => $this->t('SSO User Account Management'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $roles = user_role_names(TRUE);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);
    $form['user_accounts']['role_mapping'] = [
      '#type' => 'table',
      '#description' => $this->t('Role mapping'),
      '#header' => [
        $this->t('SSO Role'),
        $this->t('Drupal Role'),
        $this->t('Service Provider'),
        $this->t('Committee ID'),
        $this->t('Role Operations'),
      ],
      '#attributes' => ['id' => 'pmmi-sso-roles-table'],
      '#empty' => $this->t('No mapping available.'),
      '#caption' => $this->t("You can't delete all role mappings."),
    ];
    $services = [
      PMMISSOHelper::IMS => 'IMS Service',
      PMMISSOHelper::DATA => 'Data Service',
    ];
    if ($roles_map = $config->get('user_accounts.role_mapping')) {
      $roles_count = count($roles_map);
      foreach ($roles_map as $role_id => $role) {
        $form['user_accounts']['role_mapping'][$role_id] = [
          'sso_role' => [
            '#title' => $this->t('SSO Role'),
            '#title_display' => 'invisible',
            '#type' => 'textfield',
            '#default_value' => $role['sso_role'],
            '#size' => 20,
            '#required' => TRUE,
          ],
          'drupal_role' => [
            '#title' => $this->t('Drupal Role'),
            '#title_display' => 'invisible',
            '#type' => 'select',
            '#multiple' => FALSE,
            '#default_value' => $role_id,
            '#required' => TRUE,
            '#options' => $roles,
          ],
          'service' => [
            '#title' => $this->t('Service Provider'),
            '#title_display' => 'invisible',
            '#type' => 'select',
            '#multiple' => FALSE,
            '#default_value' => $role['service'],
            '#required' => TRUE,
            '#options' => $services,
          ],
          'committee_id' => [
            '#title' => $this->t('SSO Role'),
            '#title_display' => 'invisible',
            '#type' => 'textfield',
            '#default_value' => $role['committee_id'],
            '#size' => 20,
            '#maxlength' => 8,
            '#pattern' => '[A-Z][0-9]{7}',
            '#states' => [
              'disabled' => [
                'select[name="user_accounts[role_mapping][' . $role_id . '][service]"]' => ['value' => PMMISSOHelper::IMS],
              ],
              'required' => [
                'select[name="user_accounts[role_mapping][' . $role_id . '][service]"]' => ['value' => PMMISSOHelper::DATA],
              ],
            ],
          ],
        ];
        $form['user_accounts']['role_mapping'][$role_id]['operations'] = [
          '#type' => 'operations',
          '#links' => [],
          '#access' => $roles_count == 1 ? FALSE : TRUE,
        ];
        $form['user_accounts']['role_mapping'][$role_id]['operations']['#links']['delete'] = [
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('pmmi_sso.role_map_delete_form', ['role_id' => $role_id]),
        ];
      }
    }

    $form['user_accounts']['new_mapping'] = [
      '#type' => 'details',
      '#title' => $this->t('Add a new role mapping'),
      '#open' => FALSE,
      '#tree' => TRUE,
      'sso_role' => [
        '#title' => $this->t('SSO Role'),
        '#type' => 'textfield',
        '#size' => 20,
      ],
      'drupal_role' => [
        '#title' => $this->t('Drupal Role'),
        '#type' => 'select',
        '#multiple' => FALSE,
        '#options' => $roles,
        '#states' => [
          'required' => [
            ':input[name="user_accounts[new_mapping][sso_role]"]' => ['filled' => TRUE],
          ],
        ],
      ],
      'service' => [
        '#title' => $this->t('Service Provider'),
        '#type' => 'select',
        '#multiple' => FALSE,
        '#options' => $services,
        '#states' => [
          'required' => [
            ':input[name="user_accounts[new_mapping][sso_role]"]' => ['filled' => TRUE],
          ],
        ],
      ],
      'committee_id' => [
        '#title' => $this->t('SSO CommitteeMasterCustomer'),
        '#type' => 'textfield',
        '#size' => 20,
        '#pattern' => '[A-Z][0-9]{7}',
        '#maxlength' => 8,
        '#states' => [
          'disabled' => [
            'select[name="user_accounts[new_mapping][service]"]' => ['value' => PMMISSOHelper::IMS],
          ],
          'required' => [
            'select[name="user_accounts[new_mapping][service]"]' => ['value' => PMMISSOHelper::DATA],
          ],
        ],
      ],
    ];
    // Store temporary roles array.
    $form_state->setTemporaryValue('drupal_roles', $roles);

    $form['user_accounts']['login_link_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Login Link Enabled'),
      '#description' => $this->t('Display a link to login via SSO above the user login form.'),
      '#default_value' => $config->get('user_accounts.login_link_enabled'),
    ];
    $form['user_accounts']['login_link_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login Link Label'),
      '#description' => $this->t('The text that makes up the login link to this SSO server.'),
      '#default_value' => $config->get('user_accounts.login_link_label'),
      '#states' => [
        'visible' => [
          ':input[name="user_accounts[login_link_enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['gateway'] = [
      '#type' => 'details',
      '#title' => $this->t('Gateway Feature (Auto Login) & Token Handling'),
      '#open' => FALSE,
      '#tree' => TRUE,
      '#description' => $this->t('This implements the Gateway feature from the Personify SSO.' .
        'When enabled, Drupal will check if a visitor is already logged into your SSO server before ' .
        'serving a page request. If they have an active SSO session, they will be automatically ' .
        'logged into the Drupal site. This is done by quickly redirecting them to the SSO server to perform the ' .
        'active session check, and then redirecting them back to page they initially requested.<br/><br/>' .
        'If enabled, all pages on your site will trigger this feature by default. It is strongly recommended that ' .
        'you specify specific pages to trigger this feature below.<br/><br/>' .
        '<strong>WARNING:</strong> This feature will disable page caching for anonymous users on pages it is active on.'),
    ];
    $form['gateway']['check_frequency'] = [
      '#type' => 'radios',
      '#title' => $this->t('Check Frequency'),
      '#default_value' => $config->get('gateway.check_frequency'),
      '#options' => [
        PMMISSOHelper::CHECK_NEVER => 'Disable gateway feature',
        PMMISSOHelper::CHECK_ONCE => 'Once per browser session',
        PMMISSOHelper::CHECK_ALWAYS => 'Every page load (not recommended)',
      ],
    ];
    $form['gateway']['token_frequency'] = [
      '#type' => 'radios',
      '#title' => $this->t('Check Token Frequency'),
      '#default_value' => $config->get('gateway.token_frequency'),
      '#options' => [
        PMMISSOHelper::TOKEN_DISABLED => 'Disable feature',
        PMMISSOHelper::TOKEN_TTL => 'Every page load, if token TTL expired',
      ],
      '#description' => $this->t(
        'This implements the Token TTL feature for Drupal. When enabled, Drupal 
         will check if a visitor has a valid token in the time interval, 
         specified on this page: <a href="@link">Token settings page</a>.<br/>
         If enabled, all pages on your site will trigger this feature by 
         default. It is strongly recommended that you specify specific pages 
         to trigger this feature below.<br/>
         <strong>WARNING:</strong> This feature will disable page caching on 
         pages it is active on.', [
          '@link' => Url::fromRoute('pmmi_sso.token.settings')->toString(),
        ]
      ),
    ];
    $form['gateway']['token_action'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default action for the failed Token validation result'),
      '#default_value' => $config->get('gateway.token_action'),
      '#options' => [
        PMMISSOHelper::TOKEN_ACTION_LOGOUT => 'Logout from Drupal',
        PMMISSOHelper::TOKEN_ACTION_FORCE_LOGIN => 'Forced redirect to Personify Login Page',
      ],
      '#states' => [
        'invisible' => [
          ':input[name="gateway[token_frequency]"]' => ['value' => PMMISSOHelper::TOKEN_DISABLED],
        ],
      ],
      '#description' => $this->t(
        'This feature is only implemented on selected pages below or for all pages, 
         if no selection.<br/>
         If selected action Logout: If token is expired and not valid, after 
         verification, users will be logged out and stay on site.<br/>
         If selected action Forced Redirect: If token is expired and not valid, 
         after verification, users will be redirected to the Personify site.
         <br/>'
      ),
    ];
    $this->gatewayPaths->setConfiguration($config->get('gateway.paths'));
    $form['gateway']['paths'] = $this->gatewayPaths->buildConfigurationForm([], $form_state);
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];
    $form['advanced']['debug_log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log debug information?'),
      '#description' => $this->t(
        'This is not meant for production sites! Enable this to log debug 
        information about the interactions with the PMMI SSO Server 
        to the Drupal log.'),
      '#default_value' => $config->get('advanced.debug_log'),
    ];
    $form['advanced']['connection_timeout'] = [
      '#type' => 'textfield',
      '#size' => 3,
      '#title' => $this->t('Connection timeout'),
      '#field_suffix' => $this->t('seconds'),
      '#description' => $this->t(
        'This module makes HTTP requests to your PMMI SSO server. 
        This value determines the maximum amount of time to wait
        on those requests before canceling them.'),
      '#default_value' => $config->get('advanced.connection_timeout'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['gateway', 'paths']));
    $this->gatewayPaths->validateConfigurationForm($form, $condition_values);

    parent::validateForm($form, $form_state);

    $unique_values = [];
    $temporary_roles = $form_state->getTemporaryValue('drupal_roles');

    // Check all mappings.
    if ($form_state->hasValue(['user_accounts', 'role_mapping'])) {
      $roles_map = $form_state->getValue([
        'user_accounts',
        'role_mapping',
      ]);
      if (!empty($roles_map)) {
        foreach ($roles_map as $key => $data) {
          if (array_key_exists($key, $temporary_roles)) {
            $unique_values[$data['drupal_role']]['sso_role'] = $data['sso_role'];
            $unique_values[$data['drupal_role']]['service'] = $data['service'];
            $unique_values[$data['drupal_role']]['committee_id'] = $data['committee_id'];
            $unique_values[$data['drupal_role']]['drupal_role_label'] = $temporary_roles[$data['drupal_role']];
          }
          else {
            $form_state->setErrorByName('user_accounts][role_mapping][' . $key . '][drupal_role', $this->t('Drupal role does not exist.'));
          }
        }
      }
    }
    // Check new role mapping.
    $data = $form_state->getValue(['user_accounts', 'new_mapping']);

    if (!empty($data['drupal_role'])) {
      $temp_value = [];
      foreach ($data as $key => $value) {
        if ($key == 'drupal_role' && array_key_exists($value, $unique_values)) {
          $form_state->setErrorByName('user_accounts][role_mapping][' . $key . '][drupal_role', $this->t('Role mapping must be unique.'));
        }
        elseif (
          (empty($value) && $key != 'committee_id') ||
          (empty($value) && $key == 'committee_id' && $data['service'] == PMMISSOHelper::DATA)
        ) {
          $form_state->setErrorByName('user_accounts][new_mapping][' . $key, $this->t('This field is required.'));
        }
        else {
          $temp_value[$key] = $value;
        }
      }
      $temp_value['drupal_role_label'] = $temporary_roles[$data['drupal_role']];
      $unique_values[$data['drupal_role']] = $temp_value;
      unset($unique_values[$data['drupal_role']]['drupal_role']);
    }
    $all_values = $form_state->getValues();
    $all_values['user_accounts']['role_mapping'] = $unique_values;
    $form_state->setValues($all_values);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('pmmi_sso.settings');

    $config
      ->set('login_uri', $form_state->getValue('login_uri'))
      ->set('service_uri', $form_state->getValue('service_uri'))
      ->set('vi', $form_state->getValue('vi'))
      ->set('vu', $form_state->getValue('vu'))
      ->set('vp', $form_state->getValue('vp'))
      ->set('vib', $form_state->getValue('vib'))
      ->set('ims_uri', $form_state->getValue('ims_uri'))
      ->set('ims_vu', $form_state->getValue('ims_vu'))
      ->set('ims_vp', $form_state->getValue('ims_vp'))
      ->set('data_service.endpoint', $form_state->getValue([
        'data_service',
        'endpoint',
      ]))
      ->set('data_service.username', $form_state->getValue([
        'data_service',
        'username',
      ]))
      ->set('data_service.password', $form_state->getValue([
        'data_service',
        'password',
      ]))
      ->set('user_accounts.login_link_enabled', $form_state->getValue([
        'user_accounts',
        'login_link_enabled',
      ]))
      ->set('user_accounts.login_link_label', $form_state->getValue([
        'user_accounts',
        'login_link_label',
      ]))
      ->set('user_accounts.role_mapping', $form_state->getValue([
        'user_accounts',
        'role_mapping',
      ]));

    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['gateway', 'paths']));
    $this->gatewayPaths->submitConfigurationForm($form, $condition_values);
    $config
      ->set('gateway.check_frequency', $form_state->getValue([
        'gateway',
        'check_frequency',
      ]))
      ->set('gateway.token_frequency', $form_state->getValue([
        'gateway',
        'token_frequency',
      ]))
      ->set('gateway.token_action', $form_state->getValue([
        'gateway',
        'token_action',
      ]))
      ->set('gateway.paths', $this->gatewayPaths->getConfiguration());
    $config
      ->set('advanced.debug_log', $form_state->getValue([
        'advanced',
        'debug_log',
      ]))
      ->set('advanced.connection_timeout', $form_state->getValue([
        'advanced',
        'connection_timeout',
      ]));
    $config->save();
  }

}
