<?php

namespace Drupal\pmmi_sales_agent\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;
use Drupal\search_api\Entity\Index;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a node operations bulk form element.
 *
 * @ViewsField("search_index_bulk_form")
 */
class SearchIndexBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No content selected.');
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    // If the user has configured a relationship on the handler take that into
    // account.
    if (!empty($this->options['relationship']) && $this->options['relationship'] != 'none') {
      $relationship = $this->displayHandler->getOption('relationships')[$this->options['relationship']];
      $table_data = $this->getViewsData()->get($relationship['table']);
      $views_data = $this->getViewsData()->get($table_data[$relationship['field']]['relationship']['base']);
    }
    else {
      $views_data = $this->getViewsData()->get($this->view->storage->get('base_table'));
    }

    $index = Index::load($views_data['table']['base']['index']);
    $entity_types = array_values($index->getEntityTypes());
    // Get first only.
    $entity_type = reset($entity_types);
    if (isset($entity_type)) {
      return $entity_type;
    }
    else {
      throw new \Exception("No entity type for field {$this->options['id']} on view {$this->view->storage->id()}");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    // Make sure we do not accidentally cache this form.
    // @todo Evaluate this again in https://www.drupal.org/node/2503009.
    $form['#cache']['max-age'] = 0;

    // Add the tableselect javascript.
    $form['#attached']['library'][] = 'core/drupal.tableselect';
    $use_revision = array_key_exists('revision', $this->view->getQuery()->getEntityTableInfo());

    // Only add the bulk form options and buttons if there are results.
    if (!empty($this->view->result)) {
      // Render checkboxes for all rows.
      $form[$this->options['id']]['#tree'] = TRUE;
      foreach ($this->view->result as $row_index => $row) {
        $original_object = $row->_item->getOriginalObject()->getValue();
        $entity = $this->getEntityTranslation($original_object, $row);

        $form[$this->options['id']][$row_index] = [
          '#type' => 'checkbox',
          // We are not able to determine a main "title" for each row, so we can
          // only output a generic label.
          '#title' => $this->t('Update this item'),
          '#title_display' => 'invisible',
          '#default_value' => !empty($form_state->getValue($this->options['id'])[$row_index]) ? 1 : NULL,
          '#return_value' => $this->calculateEntityBulkFormKey($entity, $use_revision),
        ];
      }

      // Replace the form submit button label.
      $form['actions']['submit']['#value'] = $this->t('Apply to selected items');

      // Ensure a consistent container for filters/operations in the view header.
      $form['header'] = [
        '#type' => 'container',
        '#weight' => -100,
      ];

      // Build the bulk operations action widget for the header.
      // Allow themes to apply .container-inline on this separate container.
      $form['header'][$this->options['id']] = [
        '#type' => 'container',
      ];
      $form['header'][$this->options['id']]['action'] = [
        '#type' => 'select',
        '#title' => $this->options['action_title'],
        '#options' => $this->getBulkOptions(),
      ];

      // Duplicate the form actions into the action container in the header.
      $form['header'][$this->options['id']]['actions'] = $form['actions'];
    }
    else {
      // Remove the default actions build array.
      unset($form['actions']);
    }
  }

}
