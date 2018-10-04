<?php

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin returns date storage formats from \DateTime::createFromFormat.
 *
 * @MigrateProcessPlugin(
 *   id = "format_date"
 * )
 *
 * Example usage for date only fields (DATETIME_DATE_STORAGE_FORMAT):
 * @code
 * process:
 *     field_date:
 *       plugin: format_date
 *       from_format: 'm/d/Y'
 *       to_format: 'Y-m-d'
 *       source: event_date
 * @endcode
 *
 * Example usage for datetime fields (DATETIME_DATETIME_STORAGE_FORMAT):
 * @code
 * process:
 *     field_time:
 *       plugin: format_date
 *       from_format: 'm/d/Y H:i:s'
 *       to_format: 'Y-m-d\TH:i:s'
 *       source: event_time
 * @endcode
 *
 */
class FormatDate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($this->configuration['from_format'])) {
      throw new MigrateException('Format date plugin is missing from_format configuration.');
    }
    if (empty($this->configuration['to_format'])) {
      throw new MigrateException('Format date plugin is missing to_format configuration.');
    }
    $fromFormat = $this->configuration['from_format'];
    $toFormat = $this->configuration['to_format'];
    $timezone = isset($this->configuration['timezone']) ? $this->configuration['timezone'] : NULL;
    $settings = isset($this->configuration['settings']) ? $this->configuration['settings'] : [];
    return DateTimePlus::createFromFormat($fromFormat, $value, $timezone, $settings)->format($toFormat);
  }

}
