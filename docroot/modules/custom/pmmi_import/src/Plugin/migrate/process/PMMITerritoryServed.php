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
 * This is PMMI Territory Served plugin to handle specific address data.
 *
 * @MigrateProcessPlugin(
 *   id = "pmmi_territory_served"
 * )
 */
class PMMITerritoryServed extends ProcessPluginBase implements ContainerFactoryPluginInterface {

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
    $configs = $this->configuration;
    if (isset($configs['source_type']) && $configs['source_type'] == 'string') {
      if (!isset($configs['delimeter'])) {
        throw new MigrateException($this->t('Source type string selected but no delimeter defined.'));
      }
      $value = explode($configs['delimeter'], $value);
    }
    else {
      $configs['source_type'] = 'default';
    }
    if (empty($value[0])) {
      // Country field is empty. Do nothing!
      return;
    }

    // Areas definition.
    $areas = array();
    $areas_status = FALSE;
    if (!empty($value[1] && ($areas = explode(',', $value[1])))) {
      $areas = array_combine($areas, array_map('trim', $areas));
      $areas_status = TRUE;
    }

    $territory_served = array();
    $source = $row->getSource();
    foreach (explode(',', $value[0]) as $country) {
      $country = trim($country);

      // Empty string. Ignore it!
      if (!$country) {
        return;
      }

      if ($ccode = $this->getCountryCodeByName($country)) {
        $sub = FALSE;

        if ($areas && ($subdivisions = $this->subdivision_repository->getList(array($ccode)))) {
          $csubdivisions = array_flip($subdivisions);

          foreach ($areas as $area) {
            if ($by_name = array_search(strtolower($area), array_map('strtolower', $csubdivisions))) {
              $sub = TRUE;
              $territory_served[$ccode . '::' . $csubdivisions[$by_name]] = $ccode . '::' . $csubdivisions[$by_name];
              // Area CAN'T be added to more than one country!
              unset($areas[$area]);
            }
            elseif ($by_code = array_search(strtolower($area), array_map('strtolower', $subdivisions))) {
              $sub = TRUE;
              $territory_served[$ccode . '::' . $by_code] = $ccode . '::' . $by_code;
              // Area CAN'T be added to more than one country!
              unset($areas[$area]);
            }
          }
        }

        // Save simple country if any there are no any subdivisions.
        if (!$sub) {
          $territory_served[$ccode] = $ccode;
        }
      }
      else {
        throw new MigrateException($this->t('ID @id: the next country "@country" has not passed the validation. Please check values for the "@source" source.', array(
          '@id' => $source['ID'],
          '@country' => $country,
          '@source' => $this->configuration['source']['countries'],
        )));
      }
    }

    // Notify about wrong administrative areas if they aren't related to any
    // country.
    if ($areas_status && $areas) {
      throw new MigrateException($this->t('ID @id: the next administrative areas "@areas" have not passed the validation. They are not related to any country. Please check values for the "@source" source.', array(
        '@id' => $source['ID'],
        '@areas' => implode(', ', $areas),
        '@source' => $this->configuration['source']['areas'],
      )));
    }

    if ($territory_served) {
      if ($configs['source_type'] == 'string') {
        return reset($territory_served);
      }
      return array_values($territory_served);
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
