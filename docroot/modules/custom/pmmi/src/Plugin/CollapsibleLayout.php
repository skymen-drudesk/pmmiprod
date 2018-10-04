<?php

namespace Drupal\pmmi\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Layout class for all Display Suite layouts.
 */
class CollapsibleLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'query_params' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    if ($query = \Drupal::request()->query->all()) {
      $query_settings = explode(', ', $this->configuration['query_params']);
      if ($result = array_intersect(array_keys($query), $query_settings)) {
        $build['#opened'] = TRUE;
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $form['query_params'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Query params for initial collapse state'),
      '#description' => 'E.g. category, year, text',
      '#default_value' => $configuration['query_params'],
      '#weight' => 11,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['query_params'] = $form_state->getValue('query_params');
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

}
