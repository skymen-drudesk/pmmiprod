<?php

namespace Drupal\pmmi_search\Form;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pmmi_address\FilterCountries;

/**
 * Builds the PMMI Company Search Form.
 */
class PMMICompanySearchBlockForm extends FormBase {

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
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The filter countries service.
   *
   * @var \Drupal\pmmi_address\FilterCountries
   */
  protected $filterCountries;

  /**
   * Constructs a PMMICompanySearchBlockForm object.
   *
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository
   *   The country repository.
   * @param \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
   *   The subdivision repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface;
   *   The entity type manager service.
   * @param \Drupal\pmmi_address\FilterCountries;
   *   The filter countries service.
   */
  public function __construct(CountryRepositoryInterface $country_repository, SubdivisionRepositoryInterface $subdivision_repository, EntityTypeManagerInterface $entityTypeManager, FilterCountries $filter_countries) {
    $this->countryRepository = $country_repository;
    $this->subdivisionRepository = $subdivision_repository;
    $this->entityTypeManager = $entityTypeManager;
    $this->filterCountries = $filter_countries;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository'),
      $container->get('entity_type.manager'),
      $container->get('pmmi_address.filter_countries')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_company_search_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $data = NULL) {
    $wrapper_id = !empty($data['wrapper_id']) ? $data['wrapper_id'] : 'sales-agent-directory-address';
    $bundle = !empty($data['bundle']) ? $data['bundle'] : 'company';

    // Describe address filter.
    $form['address'] = [
      '#type' => 'container',
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];
    $list = $this->countryRepository->getList();
    $used_countries = $this->filterCountries->getUsedCountries($bundle);
    $filtered_countries = array_intersect_key($list, $used_countries);
    $form['address']['country_code'] = [
      '#type' => 'selectize',
      '#title' => $this->t('Country'),
      '#options' => $filtered_countries,
      '#multiple' => TRUE,
      '#settings' => [
        'placeholder' => $this->t('Select Country'),
        'plugins' => ['remove_button', 'prevent_items_backspace_delete'],
      ],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [get_class($this), 'addressAjaxRefresh'],
        'wrapper' => $wrapper_id,
      ],
      '#weight' => 0,
    ];
    $form['address']['administrative_area'] = [
      '#type' => 'selectize',
      '#title' => $this->t('State/Region'),
      '#multiple' => TRUE,
      '#settings' => [
        'placeholder' => $this->t('Select State/Region'),
        'plugins' => ['remove_button', 'prevent_items_backspace_delete'],
      ],
      '#input_group' => TRUE,
      '#access' => FALSE,
      '#weight' => 1,
    ];

    $countries = [];
    // Override values after country has been changed!
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element && $triggering_element['#name'] == 'country_code') {
      $countries = $triggering_element['#value'];
    }

    // Show "State/Region" field only for US.
    if (in_array('US', $countries)) {
      $subdivisions = $this->subdivisionRepository->getList(['US']);
      // Show subdivision field.
      if ($subdivisions) {
        $form['address']['administrative_area']['#options'] = $subdivisions;
        $form['address']['administrative_area']['#access'] = TRUE;
      }
    }

    // Describe "Industries Served" filter.
    $form['industries'] = [
      '#type' => 'details',
      '#title' => $this->t('Industries Served'),
      '#weight' => 2,
    ];
    $form['industries']['field_industries_served'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getTermReferenceOptions('industries_served'),
      '#limit_validation_errors' => [],
    ];

    // Describe "Types of equipment sold" filter.
    $form['equipments'] = [
      '#type' => 'details',
      '#title' => $this->t('Types of equipment sold'),
    ];
    $form['equipments']['field_equipment_sold_type'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getTermReferenceOptions('equipment_sold_type'),
      '#weight' => 3,
    ];

    // Describe "Attending PMMI show" filter.
    $form['shows'] = [
      '#type' => 'details',
      '#title' => $this->t('Attending PMMI show'),
      '#weight' => 4,
    ];
    $form['shows']['pmmi_shows'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getTradeShowsOptions(),
    ];

    // Describe "Keyword" filter.
    $form['keywords'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyword'),
      '#placeholder' => $this->t('Enter keyword'),
      '#weight' => 5,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#weight' => 6,
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

    $filters = [
      'field_industries_served',
      'field_equipment_sold_type',
      'pmmi_shows',
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
      $query['keywords'] =  str_replace(' ', '+', $values['keywords']);
    }

    $url = Url::fromUri('internal:/sales-agent-directory/search/results');
    $url->setOption('query', $query);
    $form_state->setRedirectUrl($url);
  }

  /**
   * Get term reference options.
   */
  protected function getTermReferenceOptions($vid) {
    $options = array();

    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vid);
    foreach ($terms as $term) {
      if ($term->name !== 'Other') {
        $options[$term->tid] = $term->name;
      }
    }

    return $options;
  }

  /**
   * Get trade shows options.
   */
  protected function getTradeShowsOptions() {
    $trade_shows = array();

    foreach (pmmi_company_contact_get_active_trade_shows() as $show) {
      $trade_shows[$show->id()] = $show->getName();
    }

    return $trade_shows;
  }

  /**
   * Build territory served filter.
   */
  protected function filterTerritoryServedBuild($ccodes, $scodes = array()) {
    $items = [];
    $countries = $this->countryRepository->getList();

    // Build list of items. Use the next styles:
    //  - for country only: COUNTRY_CODE;
    //  - for country and area: COUNTRY_CODE::AREA_CODE.
    foreach ($ccodes as $key => $ccode) {
      $sub = FALSE;
      $subdivisions = $this->subdivisionRepository->getList([$ccode]);

      foreach ($scodes as $scode) {
        if (isset($subdivisions[$scode])) {
          $sub = TRUE;
          $items['ts'][] = $ccode . '::' . $scode;
          $items['country'][] = $countries[$ccode];
        }
      }

      if (!$sub) {
        $items['ts'][] = $ccode;
        $items['country'][] = $countries[$ccode];
      }
    }

    return $items;
  }

}
