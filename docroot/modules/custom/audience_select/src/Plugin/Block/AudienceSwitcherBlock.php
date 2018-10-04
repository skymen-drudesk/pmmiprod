<?php

namespace Drupal\audience_select\Plugin\Block;

use Drupal\audience_select\Service\AudienceManager;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Audience Switcher' block.
 *
 * @Block(
 *   id = "audience_switcher",
 *   admin_label = @Translation("Audience Switcher")
 * )
 */
class AudienceSwitcherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The audience manager service.
   *
   * @var \Drupal\audience_select\Service\AudienceManager
   */
  protected $AudienceManager;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\audience_select\Service\AudienceManager $audience_manager
   *   The audience manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AudienceManager $audience_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->AudienceManager = $audience_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('audience_select.audience_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'excluded_audiences' => [],
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form['excluded_audiences'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Exclude Auidiences'),
      '#description' => $this->t('Please select the Auidience to exclude from block'),
      '#default_value' => $this->configuration['excluded_audiences'],
      '#options' => $this->AudienceManager->getOptionsList(),
      '#weight' => '1',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $audience = $form_state->getValue('excluded_audiences');
    $excluded_audiences = array_filter($audience);
    if (!empty($excluded_audiences)) {
      $this->setConfigurationValue('excluded_audiences', array_keys($excluded_audiences));
    }
    else {
      $this->setConfigurationValue('excluded_audiences', []);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $unselected_audiences = $this->AudienceManager->getUnselectedAudiences();
    $excluded_audiences = $this->configuration['excluded_audiences'];
    $excluded_audiences = array_flip($excluded_audiences);
    $result_audiences = [];
    foreach ($unselected_audiences as $audience_id => $item) {
      if (!array_key_exists($audience_id, $excluded_audiences)) {
        $options = [
          'query' => ['audience' => $audience_id],
        ];
        $result_audiences[$audience_id]['title'] = $item['audience_title'];
        $result_audiences[$audience_id]['url'] = Url::fromUri($item['audience_redirect_url'], $options);
      }
    }
    return [
      '#theme' => 'audience_switcher_block',
      '#audiences' => $result_audiences,
    ];
  }

}
