<?php

namespace Drupal\pmmi_training_provider\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\pmmi_search\Form\PMMICompanySearchBlockForm;

/**
 * Builds the PMMI Training Provider Search Form.
 */
class PMMITrainingProviderSearchForm extends PMMICompanySearchBlockForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_training_provider_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $data = NULL) {
    $data = [
      'wrapper_id' => 'training-provider-directory-address',
      'bundle' => 'training_provider',
    ];
    $form = parent::buildForm($form, $form_state, $data);
    unset($form['industries'], $form['equipments'], $form['shows']);

    // Describe "Course Topics Offered" filter.
    $form['course_topics'] = [
      '#type' => 'details',
      '#title' => $this->t('Course Topics Offered'),
      '#weight' => 2,
    ];
    $form['course_topics']['course_topics'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getTermReferenceOptions('course_topics_offered'),
      '#limit_validation_errors' => [],
    ];

    // Describe "Delivery Options Offered" filter.
    $form['delivery_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Delivery Options Offered'),
      '#weight' => 3,
    ];
    $form['delivery_options']['delivery_options'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getTermReferenceOptions('delivery_options_offered'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = [];
    $values = $form_state->getValues();

    $filters = [
      'course_topics',
      'delivery_options',
    ];

    foreach ($filters as $filter) {
      if (!empty($values[$filter])) {
        $items = array_filter($values[$filter]);
        foreach (array_values($items) as $key => $item) {
          $query["{$filter}[$key]"] = $item;
        }
      }
    }

    $cc = is_array($values['country_code']) ? $values['country_code'] : [];
    $sc = is_array($values['administrative_area']) ? $values['administrative_area'] : [];
    $territoryServed = $this->filterTerritoryServedBuild($cc, $sc);
    if ($territoryServed) {
      $query += $territoryServed;
    }

    // Fulltext filter.
    if (!empty($values['keywords'])) {
      $query['keywords'] = str_replace(' ', '+', $values['keywords']);
    }

    // Save search record to reports entity.
    $fields_to_save = [
      'field_country' => 'country_code',
      'field_course_topics' => 'course_topics',
      'field_delivery_options' => 'delivery_options',
      'field_keywords' => 'keywords',
    ];
    $save_values = [];
    foreach ($fields_to_save as $field => $search_key) {
      if (!empty($values[$search_key])) {
        $value = is_array($values[$search_key])
          ? array_filter($values[$search_key]) : $values[$search_key];
        if (!empty($value)) {
          $save_values[$field] = $value;
        }
      }
    }
    // Create new training provider report entity.
    if (!empty($save_values)) {
      \Drupal::entityTypeManager()->getStorage('tpd_report')
        ->create([
          'uid' => \Drupal::currentUser()->id(),
          'type' => 'training_provider_directory',
        ] + $save_values)->save();
    }

    $url = Url::fromUri('internal:/training-provider-directory/search/results');
    $url->setOption('query', $query);
    $form_state->setRedirectUrl($url);
  }

}
