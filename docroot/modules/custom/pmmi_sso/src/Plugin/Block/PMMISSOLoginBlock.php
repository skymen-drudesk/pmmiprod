<?php

namespace Drupal\pmmi_sso\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'PMMISSOLoginBlock' block.
 *
 * @Block(
 *  id = "pmmi_sso_login_block",
 *  admin_label = @Translation("PMMI SSO Login Block"),
 *  category = @Translation("PMMI")
 * )
 */
class PMMISSOLoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'label' => $this->t('Login block'),
      ] + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Block Label'),
      '#default_value' => $this->configuration['label'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['label'] = $form_state->getValue('label');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'link',
      '#title' => $this->t('Login'),
      '#attributes' => ['class' => ['button', 'button--primary']],
      '#url' => Url::fromRoute('pmmi_sso.login'),
      '#cache' => [
        'contexts' => [
          'url.query_args:destination',
        ],
      ],
    ];
  }

}
