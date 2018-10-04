<?php

namespace Drupal\pmmi_import\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;

/**
 * This plugin allows set necessary taxonomy term using some migration key.
 *
 * @MigrateProcessPlugin(
 *   id = "pmmi_term_by_key"
 * )
 */
class PMMITermByKey extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $new_values = $others_values = [];

    if ($value) {
      foreach (explode($this->getSeparator(), $value) as $delta => $migration_key) {
        $migration_key = trim($migration_key);
        $others_values[$delta] = $migration_key;
        $migration_key = strtolower($migration_key);

        // Try to find taxonomy term equivalent.
        $query = \Drupal::entityQuery('taxonomy_term')
          ->condition('field_migration_keys.value', '%' . Database::getConnection()->escapeLike($migration_key) . '%', 'LIKE');

        // If there are some taxonomy terms which are associated with current
        // key - use them.
        foreach ($query->execute() as $tid) {
          if ($term = Term::load($tid)) {
            $term_migration_keys = array_map('trim', explode(PHP_EOL, $term->get('field_migration_keys')->value));

            // Additional check by migrate key.
            if (in_array($migration_key, $term_migration_keys)) {
              $new_values[$tid] = $tid;
              unset($others_values[$delta]);
            }
          }
        }
      }

      // Set values to other field (other_field) if there are no necessary
      // taxonomy term equivalents.
      if ($others_values && $this->configuration['other_field']) {
        $row->setDestinationProperty($this->configuration['other_field'], implode(', ', $others_values));
      }
    }

    return array_values($new_values);
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

  /**
   * Get keys separator.
   *
   * @return string
   *   The separator of keys.
   */
  protected function getSeparator() {
    return isset($this->configuration['separator']) ? $this->configuration['separator'] : ',';
  }

}
