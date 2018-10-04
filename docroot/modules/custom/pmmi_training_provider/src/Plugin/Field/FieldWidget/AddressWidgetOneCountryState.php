<?php

namespace Drupal\pmmi_training_provider\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget;
use Drupal\Component\Utility\NestedArray;

/**
 * Plugin implementation of the 'address_default_one_country_state' widget.
 *
 * @FieldWidget(
 *   id = "address_default_one_country_state",
 *   label = @Translation("Address (one country state)"),
 *   field_types = {
 *     "address"
 *   },
 * )
 */
class AddressWidgetOneCountryState extends AddressDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'default_country_state' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $country_list = $this->countryRepository->getList();
    $form['default_country_state'] = [
      '#type' => 'select',
      '#title' => $this->t('Show states only for selected country.'),
      '#options' => ['site_default' => $this->t('- Site default -')] + $country_list,
      '#default_value' => $this->getSetting('default_country_state'),
      '#empty_value' => '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $default_country_state = $this->getSetting('default_country_state');
    if (empty($default_country_state)) {
      $default_country_state = $this->t('None');
    }
    else {
      $country_list = $this->countryRepository->getList();
      $default_country_state = $country_list[$default_country_state];
    }
    $summary['default_country_state'] = $this->t('Default country state: @country', ['@country' => $default_country_state]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $field_name = $this->fieldDefinition->getName();
    $default_country_state = $this->getSetting('default_country_state');
    $parents = [$field_name, 0, 'address', 'country_code'];
    $input = NestedArray::getValue($form_state->getUserInput(), $parents) ?? $element['address']['#default_value']['country_code'];
    if (!empty($default_country_state) && $input && $input !== $default_country_state) {
      $element['address']['#used_fields']['administrativeArea'] = 0;
    }
    return $element;
  }

}
