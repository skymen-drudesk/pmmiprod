<?php

namespace Drupal\pmmi_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase;


/**
 * Plugin implementation of the 'pmmi_datetime_datelist' widget.
 *
 * @FieldWidget(
 *   id = "pmmi_datetime_datelist",
 *   label = @Translation("PMMI Date Select list"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class PMMIDateTimeDatelistWidget extends DateTimeWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'date_part' => 'Y',
      'increment' => '1',
      'time_type' => '24',
      'field_label' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $settings = $this->getSettings();
    $date_part = $settings['date_part'];
    // Change field label if overriden.
    if (!empty($settings['field_label'])) {
      $element['#title'] = $settings['field_label'];
    }

    $element['value'] = array(
      '#type' => 'datelist',
      '#date_increment' => $settings['increment'],
      '#date_part_order' => array($date_part),
      '#date_timezone' => DATETIME_STORAGE_TIMEZONE,
      '#required' => $element['#required'],
    );
    $default_date = new DrupalDateTime('2000-01-01 12:00:00');
    $element['value']['#default_value'] = $default_date;
    // Set up the date part.
    if ($items[$delta]->date) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $date */
      $date = $items[$delta]->date;
      switch ($date_part) {
        case 'year':
          $year = $date->format('Y');
          $date = new DrupalDateTime($year . '-01-01 12:00:00');
          break;

        case 'month':
          $month = $date->format('m');
          $date = new DrupalDateTime('2000-' . $month . '-01 12:00:00');
          break;

        case 'day':
          $day = $month = $date->format('d');
          $date = new DrupalDateTime('2000-01-' . $day . ' 12:00:00');
          break;
      }

      $element['value']['#default_value'] = $date;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['date_part'] = array(
      '#type' => 'select',
      '#title' => t('Date part order'),
      '#default_value' => $this->getSetting('date_part'),
      '#options' => array(
        'year' => t('Year'),
        'month' => t('Month'),
        'day' => t('Day'),
      ),
    );
    $element['field_label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label for field'),
      '#default_value' => $this->getSetting('field_label'),
    );
    $element['time_type'] = array(
      '#type' => 'hidden',
      '#value' => 'none',
    );
    $element['increment'] = [
      '#type' => 'hidden',
      '#value' => $this->getSetting('increment'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // The widget form element type has transformed the value to a
    // DrupalDateTime object at this point. We need to convert it back to the
    // storage timezone and format.
    foreach ($values as &$item) {
      if (!empty($item['value']) && $item['value'] instanceof DrupalDateTime) {
        $date = $item['value'];
        switch ($this->getFieldSetting('datetime_type')) {
          case DateTimeItem::DATETIME_TYPE_DATE:
            // If this is a date-only field, set it to the default time so the
            // timezone conversion can be reversed.
            datetime_date_default_time($date);
            $format = DATETIME_DATE_STORAGE_FORMAT;
            break;

          default:
            $format = DATETIME_DATETIME_STORAGE_FORMAT;
            break;
        }
        // Adjust the date for storage.
        switch ($this->getSetting('date_part')) {
          case 'year':
            $year = $date->format('Y');
            $date = new DrupalDateTime($year . '-01-01 12:00:00');
            break;

          case 'month':
            $month = $date->format('m');
            $date = new DrupalDateTime('2000-' . $month . '-01 12:00:00');
            break;

          case 'day':
            $day = $month = $date->format('d');
            $date = new DrupalDateTime('2000-01-' . $day . ' 12:00:00');
            break;
        }
        $date->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
        $item['value'] = $date->format($format);
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $summary[] = t('Date part: @part', array('@part' => $this->getSetting('date_part')));
    if ($this->getSetting('field_label')) {
      $summary[] = t('Field label: @label', array('@label' => $this->getSetting('field_label')));
    }

    return $summary;
  }

}
