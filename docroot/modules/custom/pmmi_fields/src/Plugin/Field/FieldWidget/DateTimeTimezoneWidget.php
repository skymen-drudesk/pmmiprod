<?php

namespace Drupal\pmmi_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDefaultWidget;

/**
 * Plugin implementation of the 'datetime_timezone_default' widget.
 *
 * @FieldWidget(
 *   id = "datetime_timezone_default",
 *   label = @Translation("Date and time with timezone"),
 *   field_types = {
 *     "datetime_timezone"
 *   }
 * )
 */
class DateTimeTimezoneWidget extends DateTimeDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['timezone'] = array(
      '#type' => 'select',
      '#options' => array_combine(\DateTimeZone::listIdentifiers(), \DateTimeZone::listIdentifiers()),
      '#default_value' => $items[$delta]->timezone ?: drupal_get_user_timezone(),
      '#required' => $element['#required'],
    );

    return $element;
  }

}
