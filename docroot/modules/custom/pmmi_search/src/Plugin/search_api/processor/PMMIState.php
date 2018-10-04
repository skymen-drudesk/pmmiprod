<?php

namespace Drupal\pmmi_search\Plugin\search_api\processor;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the 'PMMIState' processor plugin
 *
 * @SearchApiProcessor(
 *   id = "state",
 *   label = @Translation("State"),
 *   description = @Translation("The state name from address field."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class PMMIState extends ProcessorPluginBase {

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->countryRepository($container->get('address.country_repository'));
    $processor->stateRepository($container->get('address.subdivision_repository'));

    return $processor;
  }

  /**
   * Sets the country repository.
   *
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $countryRepository
   *   The country repository.
   *
   * @return $this
   */
  public function countryRepository(CountryRepositoryInterface $countryRepository) {
    $this->countryRepository = $countryRepository;
    return $this;
  }


  /**
   * Sets the state repository.
   *
   * @param \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
   *   The subdivision repository.
   *
   * @return $this
   */
  public function stateRepository(SubdivisionRepositoryInterface $subdivision_repository) {
    $this->subdivisionRepository = $subdivision_repository;
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if ($datasource && $datasource->getPluginId() == 'entity:node') {
      $definition = [
        'label' => $this->t('State'),
        'description' => $this->t('The state name from address field.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];

      $properties['state'] = new ProcessorProperty($definition);
    }

    // Let's do the same for 'pmmi_company_contact' entity.
    // @todo: find out why it doesn't work by reference!
    if ($datasource && $datasource->getPluginId() == 'entity:pmmi_company_contact') {
      $definition = [
        'label' => $this->t('Company » Content » State'),
        'description' => $this->t('The state name from address field.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];

      $properties['state'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Get the node object.
    $node = $this->getNode($item->getOriginalObject());
    if (!$node || !$node->hasField('field_address')) {
      // Apparently we were active for a wrong item.
      return;
    }

    $fields = $item->getFields();
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($fields, $item->getDatasourceId(), 'state');

    foreach ($fields as $field) {
      $address = $node->get('field_address')->getValue();
      $countries = $this->countryRepository->getList();
      if (!empty($address[0]['country_code']) && isset($countries[$address[0]['country_code']])) {
        $subdivisions = $this->subdivisionRepository->getList(array($address[0]['country_code']));
        if (!empty($address[0]['administrative_area']) && isset($subdivisions[$address[0]['administrative_area']])) {
          $field->addValue($subdivisions[$address[0]['administrative_area']]);
        }
      }
    }
  }

  /**
   * Retrieves the node related to an indexed search object.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $item
   *   A search object that is being indexed.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node related to that search object.
   */
  protected function getNode(ComplexDataInterface $item) {
    $item = $item->getValue();

    // Extend for 'pmmi_company_contact' entity.
    if ($item->getEntityTypeId() == 'pmmi_company_contact') {
      if ($target = $item->get('field_company')->getValue()) {
        $item = \Drupal::entityTypeManager()->getStorage('node')->load($target[0]['target_id']);
      }
    }

    if ($item instanceof NodeInterface) {
      return $item;
    }

    return NULL;
  }
}
