<?php

namespace Drupal\pmmi_reports\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a 'OtherReports' block.
 *
 * @Block(
 *  id = "other_reports",
 *  admin_label = @Translation("Other reports links block"),
 * )
 */
class OtherReports extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'links' => [],
    ] + parent::defaultConfiguration();

 }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $configs = $this->configuration;
    if ($form_state instanceof FormState && $form_state->getValue('links_count')) {
      $count = $form_state->getValue('links_count');
    }
    else {
      $count = count($configs['links']) ?: 1;
    }
    $form['#tree'] = TRUE;
    $form['links'] = [
      '#type' => 'fieldset',
      '#prefix' => '<div id="names-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    for ($i = 0; $i < $count; $i++) {
      $form['links'][$i] = [
        '#type' => 'fieldset',
      ];
      $form['links'][$i]['url'] = [
        '#type' => 'textfield',
        '#title' => t('Url'),
        '#default_value' => $configs['links'][$i]['url'] ?? '',
      ];
      $form['links'][$i]['link_text'] = [
        '#type' => 'textfield',
        '#title' => t('Link text'),
        '#default_value' => $configs['links'][$i]['link_text'] ?? '',
      ];
    }
    $form['ajax_actions']['add_link'] = [
      '#type' => 'submit',
      '#value' => t('Add one more'),
      '#submit' => [[$this, 'addOne']],
      '#ajax' => [
        'callback' => [$this, 'addmoreCallback'],
        'wrapper' => 'names-fieldset-wrapper',
      ],
    ];
    $form['ajax_actions']['remove_link'] = [
      '#type' => 'submit',
      '#value' => t('Remove one'),
      '#submit' => [[$this, 'removeOne']],
      '#ajax' => [
        'callback' => [$this, 'addmoreCallback'],
        'wrapper' => 'names-fieldset-wrapper',
      ],
    ];
    $form_state->setCached(FALSE);
    return $form;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    $form_settings = ($form['#form_id'] == 'panels_edit_block_form') ? $form['settings'] : $form;
    return $form_settings['links'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $form_settings = ($form['#form_id'] == 'panels_edit_block_form') ? $form['settings'] : $form;
    $count = $form_state->get('links_count') ?? count(Element::children($form_settings['links']));
    $form_state->setValue('links_count', ++$count);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove-one" button.
   *
   * Decrements the max counter and causes a rebuild.
   */
  public function removeOne(array &$form, FormStateInterface $form_state) {
    $form_settings = ($form['#form_id'] == 'panels_edit_block_form') ? $form['settings'] : $form;
    $count = $form_state->get('links_count') ?? count(Element::children($form_settings['links']));
    $form_state->setValue('links_count', --$count);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $form_settings = ($form['#form_id'] == 'panels_edit_block_form') ? $form['settings'] : $form;
    $links = $form_state->getValue('links');
    foreach ($links as $key => $link) {
      if (empty($link['url']) && !empty($link['link_text'])) {
        $form_state->setError($form_settings['links'][$key]['url'], $this->t('Url is required.'));
      }
      elseif (!empty($link['url']) && empty($link['link_text'])) {
        $form_state->setError($form_settings['links'][$key]['link_text'], $this->t('Link text is required.'));
      }
      elseif (empty($link['url'])) {
        unset($links[$key]);
      }
    }
    $form_state->setValue('links', $links);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $links = $form_state->getValue('links');
    $this->configuration['links'] = $links;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    if ($links = $this->configuration['links']) {
      $items = [];
      foreach ($links as $link) {
        $url = Url::fromUserInput($link['url']);
        $item = Link::fromTextAndUrl($link['link_text'], $url)->toRenderable();
        $item['#wrapper_attributes'] = ['class' => 'linked-list-item'];
        $items[] = $item;
      }

      $build['links'] = [
        '#attributes' => ['class' => 'linked-list'],
        '#theme' => 'item_list',
        '#items' => $items,
      ];

    }

    return $build;
  }

}
