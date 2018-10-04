<?php

namespace Drupal\pmmi_import\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Plugin to import migration keys.
 *
 * @MigrateProcessPlugin(
 *   id = "pmmi_migration_keys"
 * )
 */
class PMMIMigrationKeys extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($value) {
      $values = array_map('trim', explode(',', $value));
      return implode(PHP_EOL, $values);
    }
  }
}
