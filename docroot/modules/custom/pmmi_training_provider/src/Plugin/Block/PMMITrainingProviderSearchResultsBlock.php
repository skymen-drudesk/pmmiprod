<?php

namespace Drupal\pmmi_training_provider\Plugin\Block;

use Drupal\pmmi_search\Plugin\Block\PMMICompanySearchResultsBlock;

/**
 * Provides a 'PMMITrainingProviderSearchResultsBlock' block.
 *
 * @Block(
 *  id = "pmmi_training_provider_search_results_block",
 *  admin_label = @Translation("PMMI Training Provider Search Results block"),
 *  category = @Translation("PMMI Search")
 * )
 */
class PMMITrainingProviderSearchResultsBlock extends PMMICompanySearchResultsBlock {

  /**
   * {@inheritdoc}
   */
  public function build($data = NULL) {
    $data = [
      'view_name' => 'search_training_provider_directory',
    ];
    return parent::build($data);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildHeader($result_count, $data = []) {
    // Set widget data.
    $data = [
      'title' => $this->stringTranslation->formatPlural($result_count, 'Search Results <span>(@count training provider)</span>', 'Search Results <span>(@count training providers)</span>'),
      'class' => 'training-provider-search-results-header',
      'term_references' => ['course_topics', 'delivery_options'],
    ];

    $header = parent::buildHeader($result_count, $data);
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  protected function filterGetName($key) {
    $filter_name = parent::filterGetName($key);
    $filters = [
      'course_topics' => $this->t('Course topics'),
      'delivery_options' => $this->t('Delivery options'),
    ];
    return (!$filter_name && isset($filters[$key])) ? $filters[$key] : $filter_name;
  }

}
