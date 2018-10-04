<?php

/**
 * @file
 * Definition of Drupal\pmmi_address\Plugin\Field\FieldWidget\CountryAreaAutocompleteWidget.
 */

namespace Drupal\pmmi_address\Plugin\Field\FieldWidget;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'country_area_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "country_area_autocomplete",
 *   label = @Translation("Country & Administrative area autocomplete"),
 *   field_types = {
 *     "country_area"
 *   },
 *  multiple_values = TRUE
 * )
 */
class CountryAreaAutocompleteWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Country\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Country\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * Abstract over the actual field columns, to allow different field types to
   * reuse those widgets.
   *
   * @var string
   */
  protected $column;

  /**
   * Constructs a AddressDefaultWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository
   *   The country repository.
   * @param \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
   *   The subdivision repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, CountryRepositoryInterface $country_repository, SubdivisionRepositoryInterface $subdivision_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->countryRepository = $country_repository;
    $this->subdivisionRepository = $subdivision_repository;

    $property_names = $this->fieldDefinition->getFieldStorageDefinition()->getPropertyNames();
    $this->column = $property_names[0];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // @see \Drupal\Core\Field\WidgetPluginManager::createInstance().
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo: this works for multiple fields only. Add ability to work with single
   * fields if needed.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $selected = $this->getSelectedOptions($items);
    $input = $form_state->getUserInput();
    if (empty($selected['countries']) && empty($selected['areas'])) {
      if (!empty($input[$field_name]['country_code'])) {
        $selected['countries'] = $input[$field_name]['country_code'];
      }
      if (!empty($input[$field_name]['administrative_area'])) {
        $selected['areas'] = $input[$field_name]['administrative_area'];
      }
    }

    $id_prefix = implode('-', array_merge($element['#field_parents'], [$field_name]));
    $wrapper_id = Crypt::hashBase64($id_prefix . '-ajax-wrapper');

    $element += [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#open' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      '#correlation' => array(),
      '#attributes' => [
        'class' => ['pmmi-autocomplete-address'],
      ],
    ];

    if ($cardinality == 1) {
      $country_settings = [
        '#multiple' => FALSE,
        '#options' => ['' => ''] + $this->countryRepository->getList(),
        '#settings' => [
          'placeholder' => $this->t('Select Country'),
          'plugins' => ['remove_button'],
        ],
      ];
    }
    else {
      $country_settings = [
        '#multiple' => TRUE,
        '#options' => $this->countryRepository->getList(),
        '#settings' => [
          'placeholder' => $this->t('Select Country'),
          'plugins' => ['remove_button', 'prevent_items_backspace_delete'],
        ],
      ];
    }

    $element['country_code'] = [
      '#type' => 'selectize',
      '#title' => $this->t('Country'),
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefresh'],
        'wrapper' => $wrapper_id,
      ],
    ] + $country_settings;

    $element['administrative_area'] = [
      '#type' => 'selectize',
      '#title' => $this->t('State/Region'),
      '#multiple' => TRUE,
      '#settings' => [
        'placeholder' => $this->t('Select State/Region'),
        'plugins' => ['remove_button', 'prevent_items_backspace_delete'],
      ],
      '#access' => FALSE,
    ];

    // Default values.
    $countries = !empty($selected['countries']) ? $selected['countries'] : array();
    if (!empty($selected['countries']) && empty($input)) {
      $element['country_code']['#default_value'] = $selected['countries'];
    }
    if (!empty($selected['areas']) && empty($input)) {
      $element['administrative_area']['#default_value'] = $selected['areas'];
    }

    // Override values after country has been changed!
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element && $triggering_element['#name'] == "{$field_name}[country_code]") {
      $countries = $triggering_element['#value'];
    }

    // Show or hide second level of hierarchy field only for US country.
    if ((is_array($countries) && in_array('US', $countries)) || $countries == 'US') {

      $subdivisions = $this->subdivisionRepository->getList(['US']);
      // Show subdivision field.
      if ($subdivisions) {
        $element['administrative_area']['#options'] = $subdivisions;
        $element['administrative_area']['#access'] = TRUE;
        $element['#correlation']['US'] = $subdivisions;
      }
    }

    $element['#element_validate'][] = array(
      get_class($this),
      'validateElement'
    );

    return $element;
  }

  /**
   * Form validation handler for widget elements.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    $field_name = $element['#field_name'];
    $items = array();
    $input = $form_state->getUserInput();

    if (!empty($element['country_code']['#value'])) {
      $countries = $element['country_code']['#value'];
    }
    elseif (!empty($input[$field_name]['country_code'])) {
      $countries = $input[$field_name]['country_code'];
    }
    else {
      $countries = array();
    }

    if (!empty($element['administrative_area']['#value'])) {
      $areas = $element['administrative_area']['#value'];
    }
    elseif (!empty($input[$field_name]['administrative_area'])) {
      $areas = $input[$field_name]['administrative_area'];
    }
    else {
      $areas = array();
    }

    // Validation error for required field, if there are no any countries.
    if ($element['#required'] && !$countries) {
      $form_state->setError($element, t('@name field is required.', array('@name' => $element['#title'])));
    }

    // Build list of items. Use the next styles:
    //  - for country only: COUNTRY_CODE;
    //  - for country and ares: COUNTRY_CODE::AREA_CODE.
    $countries = is_array($countries) ? $countries : [$countries];
    foreach ($countries as $country) {
      $sub = FALSE;

      foreach ($areas as $area) {
        if (isset($element['#correlation'][$country][$area])) {
          $items[$country . '::' . $area] = $country . '::' . $area;
          $sub = TRUE;
        }
      }

      if (!$sub) {
        $items[$country] = $country;
      }
    }

    $form_state->setValueForElement($element, array_values($items));
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $country_element = $form_state->getTriggeringElement();
    $address_element = NestedArray::getValue($form, array_slice($country_element['#array_parents'], 0, -1));

    return $address_element;
  }

  /**
   * Determines selected options from the incoming field values.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values.
   *
   * @return array
   *   The array of corresponding selected options.
   */
  protected function getSelectedOptions(FieldItemListInterface $items) {
    $selected_options = array('countries' => array(), 'areas' => array());

    // Get selected countries and subdivisions in the separate array elements.
    foreach ($items as $item) {
      $division = explode('::', $item->{$this->column});

      if (!empty($division[0])) {
        $selected_options['countries'][$division[0]] = $division[0];
        if (!empty($division[1])) {
          $selected_options['areas'][$division[1]] = $division[1];
        }
      }
    }

    return $selected_options;
  }

}
