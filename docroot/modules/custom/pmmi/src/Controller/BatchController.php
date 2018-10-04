<?php

namespace Drupal\pmmi\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for batch routes.
 */
class BatchController implements ContainerInjectionInterface {

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Constructs a new BatchController.
   *
   * @param string $root
   *   The app root.
   */
  public function __construct($root) {
    $this->root = $root;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('app.root')
    );
  }

  /**
   * Returns a system batch page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response|array
   *   A \Symfony\Component\HttpFoundation\Response object or render array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function batchPage(Request $request) {
    require_once $this->root . '/core/includes/batch.inc';
    $output = _batch_page($request);

    if ($output === FALSE) {
      throw new AccessDeniedHttpException();
    }
    elseif (($output instanceof RedirectResponse) || ($output instanceof Response)) {
      return $output;
    }

    $batch_content = $output['content'];
    unset($output['content']);

    $output['container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => 'batch-container']
    ];
    $output['container']['content'] = $batch_content;

    // Attach additional page components to the 'favorites' batch.
    $current_set = _batch_current_set();
    if (!empty($current_set['type']) && $current_set['type'] == 'favorites') {
      $output['container']['title'] = [
        '#markup' => t('My Favorites'),
        '#prefix' => '<h2 class="favorites-block-title">',
        '#suffix' => '</h2>',
        '#weight' => -5,
      ];
      $output += $this->favoritesBatchTopComponents();
    }

    return $output;
  }

  /**
   * The _title_callback for the system.batch_page.normal route.
   *
   * @return string
   *   The page title.
   */
  public function batchPageTitle() {
    $current_set = _batch_current_set();
    return !empty($current_set['title']) ? $current_set['title'] : '';
  }

  /**
   * Additional page elements for the "Favorites" batch.
   */
  protected function favoritesBatchTopComponents() {
    $result = [];

    $result['top'] = [
      '#type' => 'container',
      '#weight' => -5,
      '#attributes' => [
        'class' => 'batch-top',
      ]
    ];

    $img_header = \Drupal::config('pmmi_sales_agent.reporting_settings')
      ->get('img_header');

    // Add image header.
    if ($img_header) {
      $block = \Drupal\block_content\Entity\BlockContent::load($img_header);
      $render = \Drupal::entityTypeManager()->getViewBuilder('block_content')->view($block);
      $result['top']['img_header'] = $render;
      $result['top']['img_header']['#weight'] = -10;
    }

    // Add action toolbar.
    $action_toolbar = \Drupal::service('plugin.manager.block')
      ->createInstance('pmmi_sales_agent_action_toolbar', [
        'links' => ['company_add', 'favorites_download', 'search_new'],
      ])
      ->build();

    if ($action_toolbar) {
      $result['top']['action_toolbar'] = $action_toolbar;
      $result['top']['action_toolbar']['#weight'] = -9;
      $result['top']['action_toolbar']['#prefix'] = '<div class="block-pmmi-sales-agent-action-toolbar">';
      $result['top']['action_toolbar']['#suffix'] = '</div>';
    }

    return $result;
  }
}
