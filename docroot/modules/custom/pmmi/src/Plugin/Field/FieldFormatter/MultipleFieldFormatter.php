<?php

namespace Drupal\pmmi\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 * @FieldFormatter(
 *   id = "pmmi_multiple_formatter",
 *   label = @Translation("Multiple items"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MultipleFieldFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'separator' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['separator'] = [
      '#title' => t('Separator For title and url'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('separator'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->getSetting('separator') ? 'Separator : ' . $this->getSetting('separator') : t('No Separator');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $items_count = count($this->getEntitiesToView($items, $langcode));

    $i = 0;
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $label = $entity->label();

      // Check if element isn't last and add separator after it.
      if ($i < $items_count - 1) {
        $elements[$delta] = ['#markup' => $label . $this->getSetting('separator')];
      }
      else {
        $elements[$delta] = ['#plain_text' => $label];
      }

      $i++;
    }
    return $elements;
  }
}
