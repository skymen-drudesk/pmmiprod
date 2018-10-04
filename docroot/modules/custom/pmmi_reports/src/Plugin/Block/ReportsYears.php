<?php

namespace Drupal\pmmi_reports\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\pmmi_reports\Plugin\QueueWorker\PMMIReportsQueue;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a 'ReportsYears' block.
 *
 * @Block(
 *  id = "reports_years",
 *  admin_label = @Translation("Reports archives by year"),
 * )
 */
class ReportsYears extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'personify_class' => '',
      'select_type' => 'class',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['select_type'] = [
      '#type' => 'radios',
      '#options' => [
        'class' => $this->t('By personify class ID'),
        'query' => $this->t('By context(query param)'),
      ],
      '#default_value' => $this->configuration['select_type'],
    ];
    $form['personify_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Personify Class'),
      '#description' => $this->t('Personify category class, i.e. <em>BENCHMARKING, ECONOMIC-TRENDS, INDUSTRY-RPTS, INTL-RESEARCH</em>.'),
      '#default_value' => $this->configuration['personify_class'],
      '#states' => [
        'required' => [
          ':input[name*="select_type"]' => ['value' => 'class'],
        ],
        'visible' => [
          ':input[name*="select_type"]' => ['value' => 'class'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['personify_class'] = $form_state->getValue('personify_class');
    $this->configuration['select_type'] = $form_state->getValue('select_type');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    // Get term id by personify product class or from query.
    $is_query_select = $this->configuration['select_type'] == 'query';
    if ($is_query_select) {
      $tid = \Drupal::request()->query->get('category');
    }
    else {
      $tid = PMMIReportsQueue::getTermIdByProductClass($this->configuration['personify_class']);
    }
    if (!$tid) {
      return $build;
    }
    $term = Term::load($tid);
    if ($years = $this->getYearsList($tid)) {
      if ($is_query_select) {
        $term_name = explode(' ', trim($term->getName()));
        $build['title'] = [
          '#markup' => $this->t('@name Archives', ['@name' => $term_name[0]]),
          '#prefix' => '<h2 class="block-title sidehead1">',
          '#suffix' => '</h2>',
        ];
      }
      $items = [];
      $current_path = \Drupal::service('path.current')->getPath();
      $query = \Drupal::request()->query->all();
      // Unset pager and text get params to prevent "No results" issue.
      unset($query['page'], $query['text']);
      foreach ($years as $year) {
        $query['year'] = $year;
        $url = Url::fromUserInput($current_path, ['query' => $query]);
        $item = Link::fromTextAndUrl($term->getName() . ' ' . $year, $url)->toRenderable();
        $item['#wrapper_attributes'] = ['class' => 'linked-list-item'];
        $items[] = $item;
      }

      $build['years'] = [
        '#attributes' => ['class' => 'linked-list'],
        '#theme' => 'item_list',
        '#items' => $items,
      ];

    }

    return $build;
  }

  /**
   * Get years list for selected personify class.
   */
  protected function getYearsList($tid) {
    $years = [];

    $connection = Database::getConnection();
    // Select all dates for reports with particular personify class.
    $query = $connection->select('node__field_product_status_date', 'date_table')
      ->fields('date_table', ['field_product_status_date_value', 'entity_id'])
      ->condition('date_table.bundle', 'report');
    $query->join('node__field_category', 'cat_table', 'cat_table.entity_id=date_table.entity_id');
    $query->condition('cat_table.field_category_target_id', $tid);
    $results = $query->execute()->fetchAll();

    // Sort all available years.
    foreach ($results as $field) {
      $timestamp = strtotime($field->field_product_status_date_value);
      $year = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'Y');
      $years[$year] = $year;
    }
    krsort($years);

    return $years;
  }

}
