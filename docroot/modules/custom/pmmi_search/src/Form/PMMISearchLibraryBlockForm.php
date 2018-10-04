<?php

namespace Drupal\pmmi_search\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Builds the PMMI search form for the search block.
 */
class PMMISearchLibraryBlockForm extends PMMISearchBlockForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_search_library_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $data = NULL) {
    if (empty($data)) {
      $form['message'] = array(
        '#markup' => $this->t('PMMI Search block is currently disabled'),
      );
      return $form;
    }
    $form_state->setTemporaryValue('data', $data);
    $form['keys'] = array(
      '#type' => 'search',
      '#title' => $this->t('Enter a keyword'),
      '#title_display' => 'before',
      '#placeholder' => '',
      '#description' => $this->t('Enter the keywords you wish to search for.'),
      '#size' => 15,
      '#default_value' => \Drupal::request()->query->get($data['search_identifier']) ?: '',
      '#name' => $data['search_identifier'],
      '#bootstrap_ignore_process' => TRUE,
      '#attributes' => array(
        'class' => array('search-field'),
      ),
    );
    /** @var \Drupal\taxonomy\TermStorageInterface $taxonomy_storage */
    $taxonomy_storage = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term');
    $tree = $taxonomy_storage->loadTree($data['vid']);
    foreach ($tree as $item) {
      $options[$item->tid] = $item->name;
    }
    $form['term'] = array(
      '#type' => 'select',
      '#title' => $this->t('Filter By Topic'),
      '#title_display' => 'before',
      '#options' => $options,
      '#empty_value' => 0,
      '#empty_option' => $this->t('Select a Topic'),
      '#name' => $data['term_identifier'],
      '#attributes' => array('class' => array('search-filter')),
      '#default_value' => \Drupal::request()->query->get($data['term_identifier']) ?: '',
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      // Prevent op from showing up in the query string.
      '#name' => '',
    );

    $this->renderer->addCacheableDependency($form, $data);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form submits to the search page, so processing happens there.
    $query = [];
    $data = $form_state->getTemporaryValue('data');
    $search_identifier = $data['search_identifier'];
    $term_identifier = $data['term_identifier'];
    $url = Url::fromUri($data['search_path']);
    if ($url->isExternal()) {
      $parse_url = UrlHelper::parse($data['search_path']);
      $url = Url::fromUri($parse_url['path'], ['fragment' => $parse_url['fragment']]);
      $default_query = $parse_url['query'];
    }
    else {
      $default_query = $url->getOption('query');
    }
    // Saved default path query param.
    foreach ([$search_identifier, $term_identifier] as $param) {
      if ($input = $form_state->getUserInput()[$param]) {
        $query[$param] = $input;
      }
    }

    if (!empty($default_query) && is_array($default_query)) {
      foreach ($query as $key => $value) {
        if (array_key_exists($key, $default_query)) {
          unset($default_query[$key]);
        }
      }
      $query += $default_query;
    }
    $url->setOption('query', $query);
    if ($url->isExternal()) {
      $response = new TrustedRedirectResponse($url->toString());
      $form_state->setResponse($response);
    }
    else {
      $form_state->setRedirectUrl($url);
    }

  }

}
