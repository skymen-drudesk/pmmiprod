<?php

namespace Drupal\pmmi\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Text' Block for panels.
 *
 * @Block(
 *   id = "panels_text_block",
 *   admin_label = @Translation("Panels Text block"),
 * )
 */
class PanelsTextBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'block_title' => '',
      'block_text' => ['value' => ''],
    ] + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['block_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Block title to display'),
      '#required' => TRUE,
      '#maxlength' => 80,
      '#size' => 80,
      '#default_value' => $this->configuration['block_title'],
    ];
    $form['block_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Text'),
      '#format' => 'full_html',
      '#default_value' => $this->configuration['block_text']['value'],
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $fields = ['block_title', 'block_text'];
    foreach ($fields as $field) {
      $this->configuration[$field] = $form_state->getValue($field);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block_config = $this->getConfiguration();
    $build['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#attributes' => ['class' => ['headline2']],
      '#value' => $block_config['block_title'],
    ];
    $build['text'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['class' => ['text']],
      '#value' => $block_config['block_text']['value'],
    ];

    return $build;
  }

}
