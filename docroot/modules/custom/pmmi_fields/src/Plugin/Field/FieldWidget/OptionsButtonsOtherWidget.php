<?php

namespace Drupal\pmmi_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Plugin implementation of the 'options_buttons' widget.
 *
 * @FieldWidget(
 *   id = "options_buttons_other",
 *   label = @Translation("Check boxes/radio buttons with other option"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class OptionsButtonsOtherWidget extends OptionsButtonsWidget implements ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   */
  protected $entityManager;

  /**
   * Field settings.
   */
  protected $fieldSettings;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityManager = $entity_manager;
    $this->fieldSettings = $field_definition->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'other_field_name' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $display = $form_state->getFormObject()->getEntity();
    $fields = [];
    $definitions = $this->entityManager->getFieldDefinitions($display->getTargetEntityTypeId(), $display->getTargetBundle());
    $current_field = $this->fieldDefinition->getName();
    foreach ($display->getComponents() as $name => $field) {
      if (!isset($definitions[$name]) || $name === $current_field) {
        continue;
      }
      $fields[$name] = $definitions[$name]->getLabel();
    }
    asort($fields, SORT_NATURAL | SORT_FLAG_CASE);

    $element['other_field_name'] = [
      '#type' => 'select',
      '#options' => $fields,
      '#title' => t('Target'),
      '#default_value' => $this->getSetting('other_field_name'),
      '#description' => t('The field to show/hide.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $this->searchForOther($element);

    $element['#attached']['library'][] = 'pmmi_fields/other-field';

    return $element;
  }

  /**
   * Search for "Other" value and create it if no one exists.
   */
  public function searchForOther(&$element) {
    $other_id = array_search('Other', $element['#options']);

    if (empty($other_id)) {
      $bundle = reset($this->fieldSettings['handler_settings']['target_bundles']);
      $entity = $this->createNewEntity(
        $this->fieldSettings['target_type'],
        $bundle,
        'Other'
      );
      $other_id = $entity->id();
      $element['#options'][$other_id] = $entity->label();
    }
    $element[$other_id] = [
      '#weight' => 999,
      '#attributes' => ['data-other-field' => $this->getSetting('other_field_name')],
    ];
  }

  /**
   * Create new entity.
   */
  public function createNewEntity($entity_type_id, $bundle, $label) {
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    $bundle_key = $entity_type->getKey('bundle');
    $label_key = $entity_type->getKey('label');

    $entity = $this->entityManager->getStorage($entity_type_id)->create([
      $bundle_key => $bundle,
      $label_key => $label,
      'weight' => 999,
    ]);
    $entity->save();

    return $entity;
  }

}
