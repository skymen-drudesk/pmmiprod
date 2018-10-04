<?php

namespace Drupal\pmmi_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeCustomFormatter;

/**
 * Plugin implementation of the 'Custom' formatter for 'datetime_timezone' fields.
 *
 * @FieldFormatter(
 *   id = "datetime_timezone_custom",
 *   label = @Translation("Custom"),
 *   field_types = {
 *     "datetime_timezone"
 *   }
 * )
 */
class DateTimeTimezoneCustomFormatter extends DateTimeCustomFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $output ='';
      if (!empty($item->date)) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $date */
        $date = $item->date;

        if ($this->getFieldSetting('datetime_type') == 'date') {
          // A date without time will pick up the current time, use the default.
          datetime_date_default_time($date);
        }
        $this->setTimeZone($date, $item->timezone);

        $output = '<span class="date">' . $this->formatDate($date) . '</span>';
        $output .= '<span class="timezone">' . $item->timezone . '</span>';
      }
      $elements[$delta] = [
        '#markup' => $output,
        '#cache' => [
          'contexts' => [
            'timezone',
          ],
        ],
      ];
    }

    return $elements;
  }

}
