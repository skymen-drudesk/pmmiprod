<?php

namespace Drupal\pmmi_training_provider\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\pmmi_search\Form\PMMIAdminPanelAgentSearchBlockForm;

/**
 * Builds the PMMI Admin Panel Training Provider Search Form.
 */
class PMMIAdminPanelTrainingProviderSearchBlockForm extends PMMIAdminPanelAgentSearchBlockForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_admin_panel_training_provider_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $data = NULL) {
    $data = [
      'wrapper_id' => 'admin-panel-training-provider-directory-address',
      'bundle' => 'training_provider',
      'countries_show_area' => ['US'],
    ];
    $form = parent::buildForm($form, $form_state, $data);
    unset($form['approval_state']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = [];
    $values = $form_state->getValues();

    // Filtering by country/state.
    foreach (['country_code', 'administrative_area'] as $filter) {
      if ($values[$filter] && $values[$filter] != '_none') {
        $query[$filter] = $values[$filter];
      }
    }

    // Fulltext filtering.
    if (!empty($values['keywords'])) {
      $query['keywords'] = str_replace(' ', '+', $values['keywords']);
    }

    $url = Url::fromUri('internal:/admin/config/tpd/admin-panel');
    $url->setOption('query', $query);
    $form_state->setRedirectUrl($url);
  }

}
