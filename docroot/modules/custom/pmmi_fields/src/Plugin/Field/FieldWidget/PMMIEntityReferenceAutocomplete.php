<?php

namespace Drupal\pmmi_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'pmmi_entity_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "pmmi_entity_reference_autocomplete",
 *   label = @Translation("PMMI Autocomplete"),
 *   field_types = {
 *     "pmmi_entity_reference"
 *   }
 * )
 */
class PMMIEntityReferenceAutocomplete extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $widget = parent::formElement($items, $delta, $element, $form, $form_state);
    $entity_type_id = $this->fieldDefinition->getTargetEntityTypeId();
    $view_modes = \Drupal::service('entity_display.repository')->getViewModes($entity_type_id);
    $options['default'] = $this->t('Default');
    foreach ($view_modes as $key => $view_mode) {
      $options[$key] = $view_mode['label'];
    }
    $widget['view_mode'] = array(
      '#title' => $this->t('View mode'),
      '#type' => 'select',
      '#default_value' => isset($items[$delta]) ? $items[$delta]->view_mode : 'default',
      '#options' => $options,
      '#weight' => 10,
    );

    return $widget;
  }

}
