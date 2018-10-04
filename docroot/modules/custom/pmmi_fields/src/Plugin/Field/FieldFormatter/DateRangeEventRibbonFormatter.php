<?php

namespace Drupal\pmmi_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeFormatterBase;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Plugin implementation of the 'Event ribbon date' formatter for 'daterange' fields.
 *
 * This formatter renders the data range as plain text, with a fully
 * configurable date format using the PHP date syntax and separator.
 *
 * @FieldFormatter(
 *   id = "daterange_event_ribbon_custom",
 *   label = @Translation("Event ribbon date"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DateRangeEventRibbonFormatter extends DateTimeFormatterBase {

  private $currentType;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_end_date' => FALSE,
      'date_format_month' => 'M',
      'date_format_day' => 'd',
      'date_format_year' => 'o',
    ] + parent::defaultSettings();
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

        $elements[$delta][] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['start-date'],
          ],
          '#value' => $this->buildDate($start_date),
        ];
        if ($show_end_date) {
          $elements[$delta][] = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#attributes' => [
              'class' => ['end-date'],
            ],
            '#value' => $this->buildDate($end_date),
          ];
        }
      }
    }

    return $elements;
  }

  /**
   * Build date values.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   A date object.
   *
   * @return string
   *   A rendered value.
   */
  protected function buildDate(DrupalDateTime $date) {
    $build = [];
    foreach (['month', 'day', 'year'] as $type) {
      $this->currentType = $this->getSetting('date_format_' . $type);
      $build[] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => ['class' => [$type]],
        '#value' => $this->formatDate($date),
        '#cache' => [
          'contexts' => [
            'timezone',
          ],
        ],
      ];
    }
    return render($build);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatDate($date) {
    $format = $this->currentType;
    $timezone = $this->getSetting('timezone_override');
    return $this->dateFormatter->format($date->getTimestamp(), 'custom', $format, $timezone != '' ? $timezone : NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    unset($form['date_format']);

    foreach (['month', 'day', 'year'] as $type) {
      $form['date_format_' . $type] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Date format @type', ['@type' => $type]),
        '#description' => $this->t('See <a href="http://php.net/manual/function.date.php" target="_blank">the documentation for PHP date formats</a>.'),
        '#default_value' => $this->getSetting('date_format_' . $type),
      );
    }
    $form['show_end_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show end date'),
      '#default_value' => $this->getSetting('show_end_date'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    return $summary;
  }

}
