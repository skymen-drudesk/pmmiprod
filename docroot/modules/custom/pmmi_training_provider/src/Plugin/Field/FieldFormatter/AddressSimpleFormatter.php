<?php

namespace Drupal\pmmi_training_provider\Plugin\Field\FieldFormatter;

use Drupal\address\AddressInterface;
use Drupal\address\FieldHelper;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter;

/**
 * Plugin implementation of the 'address_simple' formatter.
 *
 * @FieldFormatter(
 *   id = "address_simple",
 *   label = @Translation("Default (address simple)"),
 *   field_types = {
 *     "address",
 *   },
 * )
 */
class AddressSimpleFormatter extends AddressDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#prefix' => '<div class="address-simple">',
        '#suffix' => '</div>',
        '#cache' => [
          'contexts' => [
            'languages:' . LanguageInterface::TYPE_INTERFACE,
          ],
        ],
      ];
      $elements[$delta] += $this->viewElement($item, $langcode);
    }

    return $elements;
  }

  /**
   * Builds a renderable array for a single address item.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array.
   */
  protected function viewElement(AddressInterface $address, $langcode) {
    $country_code = $address->getCountryCode();
    $locality = $address->getLocality();
    $countries = $this->countryRepository->getList();
    $country = $countries[$country_code];
    $state = $address->getAdministrativeArea();
    $organization = $address->getOrganization();

    $organization_render = '';
    if (!empty($organization)) {
      $organization_render = '<span class="organization">' . $organization . '</span><br>';
    }

    // Use 'city and state' format for USA
    // and 'city and country' for other countries.
    if ($country_code == 'US') {
      $element['address_simple'] = [
        '#markup' => $organization_render . implode(', ', array_filter([
          $locality,
          $state,
        ])),
      ];
    }
    else {
      $element['address_simple'] = [
        '#markup' => $organization_render . implode(', ', array_filter([
          $locality,
          $country,
        ])),
      ];
    }


    return $element;
  }

}
