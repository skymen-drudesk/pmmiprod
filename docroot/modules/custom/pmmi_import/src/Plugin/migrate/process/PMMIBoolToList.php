<?php

namespace Drupal\pmmi_import\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * This is PMMI plugin to move data from boolean field to the lis.
 *
 * @MigrateProcessPlugin(
 *   id = "pmmi_bool_to_list"
 * )
 */
class PMMIBoolToList extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $new_values = [];

    if (!empty($this->configuration['correlation'])) {
      $source = $row->getSource();
      foreach ($this->configuration['correlation'] as $key => $column) {
        if (!isset($source[$column])) {
          continue;
        }

        // Specific case for the 'spreadsheet' plugin.
        // @todo:
        if ($source['plugin'] == 'spreadsheet' && is_string($source[$column])) {
          if ($source[$column] == '=TRUE()') {
            $new_values[] = $key;
          }
        }
        elseif ($source[$column]) {
          $new_values[] = $key;
        }
      }
    }

    return $new_values;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }
}
