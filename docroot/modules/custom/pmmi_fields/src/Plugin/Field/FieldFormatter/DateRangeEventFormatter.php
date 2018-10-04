<?php

namespace Drupal\pmmi_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'daterange_event_custom' formatter.
 *
 * @FieldFormatter(
 *   id = "daterange_event_custom",
 *   label = @Translation("Event range date"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DateRangeEventFormatter extends DateRangeEventRibbonFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_end_date' => TRUE,
      'date_format_month' => 'F',
      'date_format_day' => 'j',
      'date_format_year' => 'Y',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $show_end_date = $this->getSetting('show_end_date');

    foreach ($items as $delta => $item) {
      if (!empty($item->start_date)) {

        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
        $start_date = $item->start_date;
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
        $end_date = $item->end_date;

        if (!$show_end_date) {
          $elements[$delta] = [
            '#markup' => $this->prettyDateFormat($start_date),
          ];
        }
        else {
          $elements[$delta] = [
            '#markup' => $this->prettyDateFormat($start_date, $end_date),
          ];
        }

      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function prettyDateFormat($start_date, $end_date = FALSE) {
    $m = $this->getSetting('date_format_month');
    $d = $this->getSetting('date_format_day');
    $y = $this->getSetting('date_format_year');
    $date_range = '';

    // If only one date, or dates are the same set to FULL verbose date.
    if (empty($start_date) || empty($end_date) || ($this->formatCustomDate($m . $d . $y, $start_date) == $this->formatCustomDate($m . $d . $y, $end_date))) {
      $start_date_pretty = $this->formatCustomDate("{$m} {$d}, {$y}", $start_date);
      $end_date_pretty = $this->formatCustomDate("{$m} {$d}, {$y}", $end_date);
    }
    else {
      // Setup basic dates.
      $start_date_pretty = $this->formatCustomDate("{$m} {$d}", $start_date);
      $end_date_pretty = $this->formatCustomDate("{$d}, {$y}", $end_date);
      // If years differ add suffix and year to start_date.
      if ($this->formatCustomDate($y, $start_date) != $this->formatCustomDate($y, $end_date)) {
        $start_date_pretty .= $this->formatCustomDate(', ' . $y, $start_date);
      }

      // If months differ add suffix and year to end_date.
      if ($this->formatCustomDate($m, $start_date) != $this->formatCustomDate($m, $end_date)) {
        $end_date_pretty = $this->formatCustomDate($m . ' ', $end_date) . $end_date_pretty;
      }
    }

    // Build date_range return string.
    if (!empty($start_date)) {
      $date_range .= $start_date_pretty;
    }
    // Check if there is an end date and append if not identical.
    if (!empty($end_date)) {
      if ($end_date_pretty != $start_date_pretty) {
        $date_range .= ' - ' . $end_date_pretty;
      }
    }
    return $date_range;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatCustomDate($format, $date) {
    if (empty($date)) {
      return FALSE;
    }
    $timezone = $this->getSetting('timezone_override');
    return $this->dateFormatter->format($date->getTimestamp(), 'custom', $format, $timezone != '' ? $timezone : NULL);
  }

}
