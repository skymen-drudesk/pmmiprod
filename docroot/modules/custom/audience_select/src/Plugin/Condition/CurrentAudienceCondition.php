<?php

namespace Drupal\audience_select\Plugin\Condition;

use Drupal\audience_select\Service\AudienceManager;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'audience' condition to enable a condition based in module selected status.
 *
 * @Condition(
 *   id = "audience",
 *   label = @Translation("Audience"),
 * )
 */
class CurrentAudienceCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

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
   * Creates a new AudienceCondition instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\audience_select\Service\AudienceManager $audience_manager
   *   The audience manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AudienceManager $audience_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->AudienceManager = $audience_manager;
    $this->audiences = $audience_manager->getData();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['audiences'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('When the viewer has the following audience'),
      '#default_value' => $this->configuration['audiences'],
      '#options' => $this->AudienceManager->getOptionsList(),
      '#description' => $this->t('If you select no audience, the condition will
        evaluate to TRUE for all viewers.'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'audiences' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['audiences'] = array_filter($form_state->getValue('audiences'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $audiences = array_intersect_key($this->AudienceManager->getOptionsList(), $this->configuration['audiences']);
    if (count($audiences) > 1) {
      $audiences = implode(', ', $audiences);
    }
    else {
      $audiences = reset($audiences);
    }
    if (!empty($this->configuration['negate'])) {
      return $this->t('The viewer is not a member of @audiences', ['@audiences' => $audiences]);
    }
    else {
      return $this->t('The viewer is a member of @audiences', ['@audiences' => $audiences]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['audiences']) && !$this->isNegated()) {
      return TRUE;
    }
    $audience = $this->AudienceManager->getCurrentAudience();
    return (bool) array_key_exists($audience, $this->configuration['audiences']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'audience';
    return $contexts;
  }

}
