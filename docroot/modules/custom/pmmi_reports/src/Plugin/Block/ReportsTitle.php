<?php

namespace Drupal\pmmi_reports\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'ReportsTitle' block.
 *
 * @Block(
 *  id = "reports_title",
 *  admin_label = @Translation("Reports title"),
 * )
 */
class ReportsTitle extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'title' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['label'], $form['label_display']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#description' => $this->t('Reports title.'),
      '#default_value' => $this->configuration['title'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    if ($title = $this->configuration['title']) {
      if ($year = \Drupal::request()->query->get('year')) {
        $title = $year . ' ' . $title;
      }
      $build['title'] = [
        '#markup' => $title,
        '#prefix' => '<h1 class="headline2">',
        '#suffix' => '</h1>',
      ];
    }

    return $build;
  }

}
