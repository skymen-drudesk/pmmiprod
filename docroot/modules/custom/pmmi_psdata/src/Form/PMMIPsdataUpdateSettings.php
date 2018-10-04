<?php

namespace Drupal\pmmi_psdata\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pmmi_psdata\Service\PMMIDataCollector;

/**
 * Class PMMIPsdataUpdateSettings.
 *
 * @package Drupal\pmmi_psdata\Form
 */
class PMMIPsdataUpdateSettings extends ConfigFormBase {

  /**
   * Drupal\pmmi_psdata\Service\PMMIDataCollector definition.
   *
   * @var \Drupal\pmmi_psdata\Service\PMMIDataCollector
   */
  protected $dataCollector;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    PMMIDataCollector $data_collector
  ) {
    parent::__construct($config_factory);
    $this->dataCollector = $data_collector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('pmmi_psdata.collector')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pmmi_psdata.updatesettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_psdata_update_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pmmi_psdata.updatesettings');
    $options = [
      60 => $this->t('1 minute'),
      300 => $this->t('5 minutes'),
      3600 => $this->t('1 hour'),
      10800 => $this->t('3 hours'),
      21600 => $this->t('6 hours'),
      43200 => $this->t('12 hours'),
      86400 => $this->t('1 day'),
      172800 => $this->t('2 days'),
    ];
    $form['committee'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Committees cron options"),
      '#tree' => TRUE,
    ];
    $form['committee']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable cron job'),
      '#description' => $this->t('Enable cron jobs to update information about Committees'),
      '#default_value' => $config->get('committee.enabled'),
    ];
    $form['committee']['interval'] = [
      '#type' => 'select',
      '#title' => $this->t("Interval: Committees blocks"),
      '#description' => $this->t('Update time: updating committees blocks. Also time after which pmmi_psdata_committee cron will respond to a processing request.'),
      '#default_value' => $config->get('committee.interval'),
      '#options' => $options,
      '#states' => [
        'required' => [
          ':input[name="committee[enabled]"]' => ['checked' => TRUE],
        ],
        'visible' => [
          ':input[name="committee[enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['committee']['warm_up'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Warm up cache'),
      '#description' => $this->t('Warm up cache for the Committees block after clearing the cache.'),
      '#default_value' => $config->get('committee.warm_up'),
    ];
    $form['company'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Company Staff Pages cron options"),
      '#tree' => TRUE,
    ];
    $form['company']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable cron job'),
      '#description' => $this->t('Enable cron jobs to update information about Company Staff'),
      '#default_value' => $config->get('company.enabled'),
    ];
    $form['company']['interval'] = [
      '#type' => 'select',
      '#title' => $this->t("Main interval"),
      '#description' => $this->t('Time after which pmmi_psdata_company cron will respond to a processing request.'),
      '#default_value' => $config->get('company.interval'),
      '#options' => $options,
      '#states' => [
        'required' => [
          ':input[name="company[enabled]"]' => ['checked' => TRUE],
        ],
        'visible' => [
          ':input[name="company[enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['company']['warm_up'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Warm up cache'),
      '#description' => $this->t('Warm up cache for the Companies block after clearing the cache.'),
      '#default_value' => $config->get('company.warm_up'),
    ];
    return parent::buildForm($form, $form_state);
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

    $this->config('pmmi_psdata.updatesettings')
      ->set('committee.enabled', $form_state->getValue([
        'committee',
        'enabled',
      ]))
      ->set('committee.interval', $form_state->getValue([
        'committee',
        'interval',
      ]))
      ->set('committee.warm_up', $form_state->getValue([
        'committee',
        'warm_up',
      ]))
      ->set('company.enabled', $form_state->getValue(['company', 'enabled']))
      ->set('company.interval', $form_state->getValue(['company', 'interval']))
      ->set('company.warm_up', $form_state->getValue(['company', 'warm_up']))
      ->save();
  }

}
