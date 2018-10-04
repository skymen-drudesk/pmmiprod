<?php

namespace Drupal\pmmi_sales_agent\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\rest\Plugin\views\row\DataFieldRow;

/**
 * Plugin which displays fields as raw data.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "csv data_field",
 *   title = @Translation("CSV Fields"),
 *   help = @Translation("Use fields as row data."),
 *   display_types = {"data"}
 * )
 */
class CsvFieldRow extends DataFieldRow {

  /**
   * {@inheritdoc}
   *
   * We should remove validation for aliases so we can use normal words for csv keys.
   */
  public function validateAliasName($element, FormStateInterface $form_state) {}

}
