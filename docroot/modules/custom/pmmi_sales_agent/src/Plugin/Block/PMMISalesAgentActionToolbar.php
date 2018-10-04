<?php

namespace Drupal\pmmi_sales_agent\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\flag\FlagLinkBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the "PMMISalesAgentActionToolbar" block
 *
 * @Block(
 *   id = "pmmi_sales_agent_action_toolbar",
 *   admin_label = @Translation("PMMI Sales Agent Action Toolbar"),
 * )
 */
class PMMISalesAgentActionToolbar extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The flag link builder.
   *
   * @var \Drupal\flag\FlagLinkBuilderInterface
   */
  protected $flagLinkBuilder;

  /**
   * Constructs a PMMIAgentSalesActionToolbar object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\flag\FlagLinkBuilderInterface
   *   The flag link builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FlagLinkBuilderInterface $flag_link_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->flagLinkBuilder = $flag_link_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('flag.link_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['links' => []] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // A list of the available links.
    $available_items = [
      'favorites_add' => $this->t('Add to favorites'),
      'company_add' => $this->t('Add your company'),
      'favorites_download' => $this->t('Download favorites'),
      'search_new' => $this->t('New search'),
    ];

    $form['links'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Links to display'),
      '#default_value' => $this->configuration['links'],
      '#options' => $available_items,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['links'] = $form_state->getValue('links');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $links = array_filter($this->configuration['links']);

    // Do nothing if there are no any selected items.
    if (!$links) {
      return $build;
    }

    // Display the links we need.
    $links_to_display = [];
    foreach ($links as $link) {
      switch ($link) {
        case 'favorites_add':
          $node = \Drupal::routeMatch()->getParameter('node');

          // Load a node if we didn't get an object.
          if (is_numeric($node)) {
            $node = \Drupal\node\Entity\Node::load($node);
          }

          $links_to_display[$link] = $this->flagLinkBuilder->build('node', $node->id(), 'favorites_content');
          $links_to_display[$link]['#cache']['max-age'] = 0;
          break;

        case 'favorites_download':
          $hasPermission = \Drupal::currentUser()
            ->hasPermission('pmmi sales agent favorites');

          if ($hasPermission) {
            $url = Url::fromUri('internal:/sales-agent-directory/favorites');
            $link_options = ['attributes' => ['class' => ['pmmi-download-favorites']]];
            $url->setOptions($link_options);

            // @todo: replace link when the favorites page will be exist.
            $links_to_display[$link] = Link::fromTextAndUrl($this->t('Download favorites'), $url)->toString();
          }
          break;

        case 'search_new':
          $hasPermission = \Drupal::currentUser()
            ->hasPermission('pmmi sales agent search');

          if ($hasPermission) {
            $url = Url::fromUri('internal:/sales-agent-directory/search');
            $link_options = ['attributes' => ['class' => ['pmmi-search-new']]];
            $url->setOptions($link_options);

            $links_to_display[$link] = Link::fromTextAndUrl($this->t('New search'), $url)->toString();
          }
          break;
      }
    }

    $build = [
      '#theme' => 'item_list',
      '#items' => $links_to_display,
    ];

    return $build;
  }
}
