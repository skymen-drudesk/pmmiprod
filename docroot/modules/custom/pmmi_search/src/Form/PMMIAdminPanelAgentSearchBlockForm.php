<?php

namespace Drupal\pmmi_search\Form;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pmmi_address\FilterCountries;

/**
 * Builds the PMMI Admin Panel Agent Search Form.
 */
class PMMIAdminPanelAgentSearchBlockForm extends FormBase {

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
   * The filter countries service.
   *
   * @var \Drupal\pmmi_address\FilterCountries
   */
  protected $filterCountries;

  /**
   * Constructs a PMMIAdminPanelAgentSearchBlockForm object.
   *
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository
   *   The country repository.
   * @param \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
   *   The subdivision repository.
   * @param \Drupal\pmmi_address\FilterCountries;
   *   The filter countries service.
   */
  public function __construct(CountryRepositoryInterface $country_repository, SubdivisionRepositoryInterface $subdivision_repository, FilterCountries $filter_countries) {
    $this->countryRepository = $country_repository;
    $this->subdivisionRepository = $subdivision_repository;
    $this->filterCountries = $filter_countries;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository'),
      $container->get('pmmi_address.filter_countries')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_admin_panel_agent_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $data = NULL) {
    $bundle = !empty($data['bundle']) ? $data['bundle'] : 'company';
    $qp = \Drupal::request()->query->all();
    $countries = $this->countryRepository->getList();
    $used_countries = $this->filterCountries->getUsedCountries($bundle);
    $filtered_countries = array_intersect_key($countries, $used_countries);

    // We can use static wrapper here.
    $wrapper_id = !empty($data['wrapper_id']) ? $data['wrapper_id'] : 'admin-panel-sales-agent-directory-address';

    // Add link to quick add new company node.
    $url = Url::fromUri('internal:/node/add/' . $bundle);
    $link_options = [
      'attributes' => [
        'class' => ['button'],
      ],
    ];
    $url->setOptions($link_options);
    $form['create_company']['#markup'] = Link::fromTextAndUrl($this->t('Add new @company', ['@company' => str_replace('_', ' ', $bundle)]), $url)->toString();

    // Describe address filters.
    $form['address'] = [
      '#type' => 'container',
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];
    $form['address']['country_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => ['_none' => $this->t('- Any -')] + $filtered_countries,
      '#default_value' => isset($qp['country_code']) ? $qp['country_code'] : '_none',
      '#ajax' => [
        'callback' => [get_class($this), 'addressAjaxRefresh'],
        'wrapper' => $wrapper_id,
      ],
    ];
    $form['address']['administrative_area'] = [
      '#type' => 'select',
      '#title' => $this->t('State/Region'),
      '#access' => FALSE,
    ];

    $selected_country = NULL;

    // Override values after country has been changed!
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element && $triggering_element['#name'] == 'country_code') {
      $selected_country = $triggering_element['#value'];
    }
    elseif (isset($qp['country_code']) && !empty($countries[$qp['country_code']])) {
      $selected_country = $qp['country_code'];
    }

    // Show or hide second level of hierarchy in accordance with selected
    // country.
    $countries_show_area = isset($data['countries_show_area']) ? $data['countries_show_area'] : [];
    if ($selected_country && $selected_country != '_none' && in_array($selected_country, $countries_show_area)) {
      $subdivisions = $this->subdivisionRepository->getList([$selected_country]);

      // Show subdivision field.
      if ($subdivisions) {
        $form['address']['administrative_area']['#options'] = ['_none' => $this->t('- Any -')] + $subdivisions;
        $form['address']['administrative_area']['#access'] = TRUE;
        $form['address']['administrative_area']['#default_value'] = isset($qp['administrative_area']) ? $qp['administrative_area'] : '_none';
      }
    }
    else {
      $form['address']['administrative_area']['#options'] = [];
    }

    // Allow fulltext filtering.
    $form['keywords'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyword search'),
      '#default_value' => isset($qp['keywords']) ? str_replace('+', ' ', $qp['keywords']) : '',
    ];
    // Allow fulltext filtering.
    $form['approval_state'] = [
      '#type' => 'select',
      '#title' => $this->t('Approval state'),
      '#options' => [
        '_none' => $this->t('- Any -'),
        'approved' => $this->t('Approved'),
        'not_approved' => $this->t('Not approved'),
        'updated' => $this->t('Updated (needs approve)'),
      ],
      '#default_value' => isset($qp['approval_state']) ? $qp['approval_state'] : 'all',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    return $form;
  }

  /**
   * Address ajax callback.
   */
  public static function addressAjaxRefresh(array $form, FormStateInterface $form_state) {
    return $form['address'];
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

    // Approval state filtering.
    if (!empty($values['approval_state']) && $values['approval_state'] !== '_none') {
      $query['approval_state'] = str_replace(' ', '+', $values['approval_state']);
    }

    $url = Url::fromUri('internal:/admin/config/sad/admin-panel');
    $url->setOption('query', $query);
    $form_state->setRedirectUrl($url);
  }

}
