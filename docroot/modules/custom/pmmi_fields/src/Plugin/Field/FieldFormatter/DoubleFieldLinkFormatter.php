<?php

namespace Drupal\pmmi_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\double_field\Plugin\Field\FieldFormatter\Base;

/**
 * Plugin implementations for 'double_field' formatter.
 *
 * @FieldFormatter(
 *   id = "double_field_link",
 *   label = @Translation("Double Field link formatter"),
 *   field_types = {"double_field"}
 * )
 */
class DoubleFieldLinkFormatter extends Base {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#item' => $item,
        '#theme' => 'double_field_mail_link',
      ];
    }

    return $element;
  }

}
