<?php

namespace Drupal\pmmi_search\Plugin\search_api\processor;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the 'PMMICountry' processor plugin
 *
 * @SearchApiProcessor(
 *   id = "country",
 *   label = @Translation("Country"),
 *   description = @Translation("The country name from address field."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class PMMICountry extends ProcessorPluginBase {

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Country\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->countryRepository($container->get('address.country_repository'));

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
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if ($datasource && $datasource->getPluginId() == 'entity:node') {
      $definition = [
        'label' => $this->t('Country'),
        'description' => $this->t('The country name from address field.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];

      $properties['country'] = new ProcessorProperty($definition);
    }

    // Let's do the same for 'pmmi_company_contact' entity.
    // @todo: find out why it doesn't work by reference!
    if ($datasource && $datasource->getPluginId() == 'entity:pmmi_company_contact') {
      $definition = [
        'label' => $this->t('Company » Content » Country'),
        'description' => $this->t('The country name from address field.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];

      $properties['country'] = new ProcessorProperty($definition);
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
      ->filterForPropertyPath($fields, $item->getDatasourceId(), 'country');

    foreach ($fields as $field) {
      $address = $node->get('field_address')->getValue();
      $countries = $this->countryRepository->getList();
      if (!empty($address[0]['country_code']) && isset($countries[$address[0]['country_code']])) {
        $field->addValue($countries[$address[0]['country_code']]);
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
