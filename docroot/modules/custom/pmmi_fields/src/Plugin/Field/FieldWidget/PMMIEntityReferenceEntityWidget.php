<?php

namespace Drupal\pmmi_fields\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex;

/**
 * Plugin implementation of the 'pmmi_inline_entity_form_complex' widget.
 *
 * @FieldWidget(
 *   id = "pmmi_inline_entity_form_complex",
 *   label = @Translation("PMMI Inline entity form - Complex"),
 *   field_types = {
 *     "pmmi_entity_reference"
 *   },
 *   multiple_values = true
 * )
 */
class PMMIEntityReferenceEntityWidget extends InlineEntityFormComplex {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $settings = $this->getSettings();
    $target_type = $this->getFieldSetting('target_type');
    // Get the entity type labels for the UI strings.
    $labels = $this->getEntityTypeLabels();

    // Build a parents array for this element's values in the form.
    $parents = array_merge($element['#field_parents'], [
      $items->getName(),
      'form',
    ]);

    // Assign a unique identifier to each IEF widget.
    // Since $parents can get quite long, sha1() ensures that every id has
    // a consistent and relatively short length while maintaining uniqueness.
    $this->setIefId(sha1(implode('-', $parents)));

    // Get the langcode of the parent entity.
    $parent_langcode = $items->getEntity()->language()->getId();

    // Determine the wrapper ID for the entire element.
    $wrapper = 'inline-entity-form-' . $this->getIefId();

    $element = [
        '#type' => 'fieldset',
        '#tree' => TRUE,
        '#description' => $this->fieldDefinition->getDescription(),
        '#prefix' => '<div id="' . $wrapper . '">',
        '#suffix' => '</div>',
        '#ief_id' => $this->getIefId(),
        '#ief_root' => TRUE,
        '#translating' => $this->isTranslating($form_state),
        '#field_title' => $this->fieldDefinition->getLabel(),
        '#after_build' => [
          [get_class($this), 'removeTranslatabilityClue'],
        ],
      ] + $element;

    $element['#attached']['library'][] = 'inline_entity_form/widget';

    $this->prepareFormState($form_state, $items, $element['#translating']);
    $entities = $form_state->get([
      'inline_entity_form',
      $this->getIefId(),
      'entities',
    ]);

    // Build the "Multiple value" widget.
    // TODO - does this belong in #element_validate?
    $element['#element_validate'][] = [get_class($this), 'updateRowWeights'];
    // Add the required element marker & validation.
    if ($element['#required']) {
      $element['#element_validate'][] = [get_class($this), 'requiredField'];
    }

    $element['entities'] = [
      '#tree' => TRUE,
      '#theme' => 'pmmi_fields_entity_table',
      '#entity_type' => $target_type,
    ];

    // Get the fields that should be displayed in the table.
    $target_bundles = $this->getTargetBundles();
    $fields = $this->inlineFormHandler->getTableFields($target_bundles);


    // Add Table Header for view mode field.
    $fields['view_mode'] = [
      'type' => 'view_mode',
      'label' => $this->t('View Mode'),
      'weight' => 3,
    ];

    $context = [
      'parent_entity_type' => $this->fieldDefinition->getTargetEntityTypeId(),
      'parent_bundle' => $this->fieldDefinition->getTargetBundle(),
      'field_name' => $this->fieldDefinition->getName(),
      'entity_type' => $target_type,
      'allowed_bundles' => $target_bundles,
    ];
    $this->moduleHandler->alter('inline_entity_form_table_fields', $fields, $context);
    $element['entities']['#table_fields'] = $fields;

    $weight_delta = max(ceil(count($entities) * 1.2), 50);
    foreach ($entities as $key => $value) {
      // Data used by theme_inline_entity_form_entity_table().
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $value['entity'];
      $element['entities'][$key]['#label'] = $this->inlineFormHandler->getEntityLabel($value['entity']);
      $element['entities'][$key]['#entity'] = $value['entity'];
      $element['entities'][$key]['#needs_save'] = $value['needs_save'];

      // Handle row weights.
      $element['entities'][$key]['#weight'] = $value['weight'];

      // First check to see if this entity should be displayed as a form.
      if (!empty($value['form'])) {
        $element['entities'][$key]['title'] = [];
        $element['entities'][$key]['delta'] = [
          '#type' => 'value',
          '#value' => $value['weight'],
        ];

        // Add the appropriate form.
        if ($value['form'] == 'edit') {
          $element['entities'][$key]['form'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['ief-form', 'ief-form-row']],
            'inline_entity_form' => $this->getInlineEntityForm(
              $value['form'],
              $entity->bundle(),
              $parent_langcode,
              $key,
              array_merge($parents, [
                'inline_entity_form',
                'entities',
                $key,
                'form',
              ]),
              $entity
            ),
          ];

          $element['entities'][$key]['form']['inline_entity_form']['#process'] = [
            [
              '\Drupal\inline_entity_form\Element\InlineEntityForm',
              'processEntityForm',
            ],
            [get_class($this), 'addIefSubmitCallbacks'],
            [get_class($this), 'buildEntityFormActions'],
          ];
        }
        elseif ($value['form'] == 'remove') {
          $element['entities'][$key]['form'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['ief-form', 'ief-form-row']],
            // Used by Field API and controller methods to find the relevant
            // values in $form_state.
            '#parents' => array_merge($parents, ['entities', $key, 'form']),
            // Store the entity on the form, later modified in the controller.
            '#entity' => $entity,
            // Identifies the IEF widget to which the form belongs.
            '#ief_id' => $this->getIefId(),
            // Identifies the table row to which the form belongs.
            '#ief_row_delta' => $key,
          ];
          $this->buildRemoveForm($element['entities'][$key]['form']);
        }
      }
      else {
        $row = &$element['entities'][$key];
        $row['title'] = [];
        $row['delta'] = [
          '#type' => 'weight',
          '#delta' => $weight_delta,
          '#default_value' => $value['weight'],
          '#attributes' => ['class' => ['ief-entity-delta']],
        ];

        // Build Select list for available entity view modes.
        $value_entity = $value['entity'];

        $options = $this->entityDisplayRepository->getViewModeOptionsByBundle($value_entity->getEntityTypeId(), $value_entity->bundle());
        $saved_item = $items->get($key);
        $default_option = 'default';
        $saved_option = empty($saved_item) ? $default_option : $items->get($key)->getValue()['view_mode'];
        if (array_key_exists($saved_option, $options)) {
          $default_option = $saved_option;
        }
        $row['view_mode'] = [
          '#type' => 'select',
          '#default_value' => $default_option,
          '#options' => $options,
          '#weight' => 2,
        ];

        // Add an actions container with edit and delete buttons for the entity.
        $row['actions'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['ief-entity-operations']],
        ];

        // Make sure entity_access is not checked for unsaved entities.
        $entity_id = $entity->id();
        if (empty($entity_id) || $entity->access('update')) {
          $row['actions']['ief_entity_edit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Edit'),
            '#name' => 'ief-' . $this->getIefId() . '-entity-edit-' . $key,
            '#limit_validation_errors' => [],
            '#ajax' => [
              'callback' => 'inline_entity_form_get_element',
              'wrapper' => $wrapper,
            ],
            '#submit' => ['inline_entity_form_open_row_form'],
            '#ief_row_delta' => $key,
            '#ief_row_form' => 'edit',
          ];
        }

        // If 'allow_existing' is on, the default removal operation is unlink
        // and the access check for deleting happens inside the controller
        // removeForm() method.
        if (empty($entity_id) || $settings['allow_existing'] || $entity->access('delete')) {
          $row['actions']['ief_entity_remove'] = [
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#name' => 'ief-' . $this->getIefId() . '-entity-remove-' . $key,
            '#limit_validation_errors' => [],
            '#ajax' => [
              'callback' => 'inline_entity_form_get_element',
              'wrapper' => $wrapper,
            ],
            '#submit' => ['inline_entity_form_open_row_form'],
            '#ief_row_delta' => $key,
            '#ief_row_form' => 'remove',
            '#access' => !$element['#translating'],
          ];
        }
      }
    }

    // When in translation, the widget only supports editing (translating)
    // already added entities, so there's no need to show the rest.
    if ($element['#translating']) {
      if (empty($entities)) {
        // There are no entities available for translation, hide the widget.
        $element['#access'] = FALSE;
      }
      return $element;
    }

    $entities_count = count($entities);
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()
      ->getCardinality();
    if ($cardinality > 1) {
      // Add a visual cue of cardinality count.
      $message = $this->t('You have added @entities_count out of @cardinality_count allowed @label.', [
        '@entities_count' => $entities_count,
        '@cardinality_count' => $cardinality,
        '@label' => $labels['plural'],
      ]);
      $element['cardinality_count'] = [
        '#markup' => '<div class="ief-cardinality-count">' . $message . '</div>',
      ];
    }
    // Do not return the rest of the form if cardinality count has been reached.
    if ($cardinality > 0 && $entities_count == $cardinality) {
      return $element;
    }

    $create_bundles = $this->getCreateBundles();
    $create_bundles_count = count($create_bundles);
    $allow_new = $settings['allow_new'] && !empty($create_bundles);
    $hide_cancel = FALSE;
    // If the field is required and empty try to open one of the forms.
    if (empty($entities) && $this->fieldDefinition->isRequired()) {
      if ($settings['allow_existing'] && !$allow_new) {
        $form_state->set([
          'inline_entity_form',
          $this->getIefId(),
          'form',
        ], 'ief_add_existing');
        $hide_cancel = TRUE;
      }
      elseif ($create_bundles_count == 1 && $allow_new && !$settings['allow_existing']) {
        $bundle = reset($target_bundles);

        // The parent entity type and bundle must not be the same as the inline
        // entity type and bundle, to prevent recursion.
        $parent_entity_type = $this->fieldDefinition->getTargetEntityTypeId();
        $parent_bundle = $this->fieldDefinition->getTargetBundle();
        if ($parent_entity_type != $target_type || $parent_bundle != $bundle) {
          $form_state->set([
            'inline_entity_form',
            $this->getIefId(),
            'form',
          ], 'add');
          $form_state->set([
            'inline_entity_form',
            $this->getIefId(),
            'form settings',
          ], [
            'bundle' => $bundle,
          ]);
          $hide_cancel = TRUE;
        }
      }
    }

    // If no form is open, show buttons that open one.
    $open_form = $form_state->get([
      'inline_entity_form',
      $this->getIefId(),
      'form',
    ]);

    if (empty($open_form)) {
      $element['actions'] = [
        '#attributes' => ['class' => ['container-inline']],
        '#type' => 'container',
        '#weight' => 100,
      ];

      // The user is allowed to create an entity of at least one bundle.
      if ($allow_new) {
        // Let the user select the bundle, if multiple are available.
        if ($create_bundles_count > 1) {
          $bundles = [];
          foreach ($this->entityTypeBundleInfo->getBundleInfo($target_type) as $bundle_name => $bundle_info) {
            if (in_array($bundle_name, $create_bundles)) {
              $bundles[$bundle_name] = $bundle_info['label'];
            }
          }
          asort($bundles);

          $element['actions']['bundle'] = [
            '#type' => 'select',
            '#options' => $bundles,
          ];
        }
        else {
          $element['actions']['bundle'] = [
            '#type' => 'value',
            '#value' => reset($create_bundles),
          ];
        }

        $element['actions']['ief_add'] = [
          '#type' => 'submit',
          '#value' => $this->t('Add new @type_singular', ['@type_singular' => $labels['singular']]),
          '#name' => 'ief-' . $this->getIefId() . '-add',
          '#limit_validation_errors' => [array_merge($parents, ['actions'])],
          '#ajax' => [
            'callback' => 'inline_entity_form_get_element',
            'wrapper' => $wrapper,
          ],
          '#submit' => ['inline_entity_form_open_form'],
          '#ief_form' => 'add',
        ];
      }

      if ($settings['allow_existing']) {
        $element['actions']['ief_add_existing'] = [
          '#type' => 'submit',
          '#value' => $this->t('Add existing @type_singular', ['@type_singular' => $labels['singular']]),
          '#name' => 'ief-' . $this->getIefId() . '-add-existing',
          '#limit_validation_errors' => [array_merge($parents, ['actions'])],
          '#ajax' => [
            'callback' => 'inline_entity_form_get_element',
            'wrapper' => $wrapper,
          ],
          '#submit' => ['inline_entity_form_open_form'],
          '#ief_form' => 'ief_add_existing',
        ];
      }
    }
    else {
      // There's a form open, show it.
      if ($form_state->get([
          'inline_entity_form',
          $this->getIefId(),
          'form',
        ]) == 'add'
      ) {
        $element['form'] = [
          '#type' => 'fieldset',
          '#attributes' => ['class' => ['ief-form', 'ief-form-bottom']],
          'inline_entity_form' => $this->getInlineEntityForm(
            'add',
            $this->determineBundle($form_state),
            $parent_langcode,
            NULL,
            array_merge($parents, ['inline_entity_form'])
          ),
        ];
        $element['form']['inline_entity_form']['#process'] = [
          [
            '\Drupal\inline_entity_form\Element\InlineEntityForm',
            'processEntityForm',
          ],
          [get_class($this), 'addIefSubmitCallbacks'],
          [get_class($this), 'buildEntityFormActions'],
        ];
      }
      elseif ($form_state->get([
          'inline_entity_form',
          $this->getIefId(),
          'form',
        ]) == 'ief_add_existing'
      ) {
        $element['form'] = [
          '#type' => 'fieldset',
          '#attributes' => ['class' => ['ief-form', 'ief-form-bottom']],
          // Identifies the IEF widget to which the form belongs.
          '#ief_id' => $this->getIefId(),
          // Used by Field API and controller methods to find the relevant
          // values in $form_state.
          '#parents' => array_merge($parents),
          // Pass the current entity type.
          '#entity_type' => $target_type,
          // Pass the widget specific labels.
          '#ief_labels' => $this->getEntityTypeLabels(),
        ];

        $element['form'] += inline_entity_form_reference_form($element['form'], $form_state);
      }

      // Pre-opened forms can't be closed in order to force the user to
      // add / reference an entity.
      if ($hide_cancel) {
        if ($open_form == 'add') {
          $process_element = &$element['form']['inline_entity_form'];
        }
        elseif ($open_form == 'ief_add_existing') {
          $process_element = &$element['form'];
        }
        $process_element['#process'][] = [get_class($this), 'hideCancel'];
      }

      // No entities have been added. Remove the outer fieldset to reduce
      // visual noise caused by having two titles.
      if (empty($entities)) {
        $element['#type'] = 'container';
      }
    }

    return $element;

  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $field_definition = $this->fieldDefinition;
    foreach ($values as &$value) {
      $view_mode = NestedArray::getValue($form_state->getValues(), [$field_definition->getName(), 'entities', $value['weight'], 'view_mode']);
      $value['view_mode'] = $view_mode;
    }
    return $values;
  }

}
