<?php

namespace Drupal\pmmi_import\Plugin\migrate\process;

use Drupal\address\Repository\CountryRepository;
use Drupal\address\Repository\SubdivisionRepository;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This is PMMI Address plugin to handle specific address data.
 *
 * @MigrateProcessPlugin(
 *   id = "pmmiaddressfield"
 * )
 */
class PMMIAddressField extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The country repository.
   *
   * @var \Drupal\address\Repository\CountryRepository;
   */
  protected $country_repository;

  /**
   * The subdivision repository.
   *
   * @var \Drupal\address\Repository\SubdivisionRepository;
   */
  protected $subdivision_repository;

  /**
   * Constructs a MachineName plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\address\Repository\CountryRepository $country_repository
   *   The country repository.
   * @param \Drupal\address\Repository\SubdivisionRepository $subdivision_repository
   *   The subdivision repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CountryRepository $country_repository, SubdivisionRepository $subdivision_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->country_repository = $country_repository;
    $this->subdivision_repository = $subdivision_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $address_field = substr($destination_property, strpos($destination_property, '/') + 1);

    switch ($address_field) {
      case 'country_code':
        if ($country_code = $this->getCountryCodeByName($value)) {
          return $country_code;
        }
        else {
          throw new MigrateException($this->t('The next country "@country" hasn\'t passed the validation. Please check values for the "@source" source.', array(
            '@country' => $value,
            '@source' => $this->configuration['source'],
          )));
        }
        break;

      case 'administrative_area':
        $source = $row->getSource();
        $country = $source[$this->configuration['parent']];

        $country_code = $this->getCountryCodeByName($country);
        if ($value && $country_code) {
          if ($areas = $this->subdivision_repository->getList(array($country_code))) {
            $area_codes = array_flip($areas);

            // Try find necessary area by its name (case is ignored).
            if ($by_name = array_search(strtolower($value), array_map('strtolower', $area_codes))) {
              return $area_codes[$by_name];
            }
            // Try find necessary area by its code (case is ignored).
            elseif ($by_code = array_search(strtolower($value), array_map('strtolower', $areas))) {
              return $by_code;
            }
            else {
              throw new MigrateException($this->t('ID @id: the next state/region "@area" hasn\'t passed the validation. Please check values for the "@source" source.', array(
                '@id' => $source['ID'],
                '@area' => $value,
                '@source' => $this->configuration['source'],
              )));
            }
          }
        }
        elseif ($value && !$country_code) {
          throw new MigrateException($this->t('ID @id: the next country "@country" hasn\'t passed the validation. Please check values for the "@source" source.', array(
            '@id' => $source['ID'],
            '@country' => $country,
            '@source' => $this->configuration['parent'],
          )));
        }
        break;
    }
  }

  /**
   * Get country code by its name (case is ignored).
   *
   * @param $country string
   *   The country name.
   *
   * @return
   *   The country code if country was found, otherwise - FALSE.
   */
  protected function getCountryCodeByName($country) {
    $countries = $this->country_repository->getList();
    return array_search(strtolower($country), array_map('strtolower', $countries));
  }
}
