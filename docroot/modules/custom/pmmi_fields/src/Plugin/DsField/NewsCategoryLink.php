<?php

namespace Drupal\pmmi_fields\Plugin\DsField;

use Drupal\ds\Plugin\DsField\Node\NodeLink;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin that renders the 'news category read more' link of a node.
 *
 * @DsField(
 *   id = "news_category_link",
 *   title = @Translation("News category link"),
 *   entity_type = "node",
 *   provider = "node",
 *   ui_limit = {
 *     "article|*",
 *   }
 * )
 */
class NewsCategoryLink extends NodeLink {


  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    unset($form['link text']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['link text'] = 'View press release';

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    // Initialize output.
    $output = '';

    // Basic string.
    $entity_render_key = $this->entityRenderKey();
    if (!empty($entity_render_key) && isset($this->entity()->{$entity_render_key})) {
      $reference = $this->entity()->get($entity_render_key)->first();
      $entity_reference = $reference->get('entity');
      $entity_adapter = $entity_reference->getTarget();
      $referenced_entity = $entity_adapter->getValue();
      if (!empty($referenced_entity)) {
        $output = $this->t('View @term', [
          '@term' => $referenced_entity->name->value,
        ]);
      }
    }
    elseif (!empty($config['link text'])) {
      $output = $this->t($config['link text']);
    }

    if (empty($output)) {
      return array();
    }

    // Link.
    if (!empty($config['link'])) {
      /* @var $entity EntityInterface */
      $entity = $this->entity();
      $url_info = $entity->toUrl();
      if (!empty($config['link class'])) {
        $url_info->setOption('attributes', array('class' => explode(' ', $config['link class'])));
      }
      $output = \Drupal::l($output, $url_info);
    }
    else {
      $output = Html::escape($output);
    }

    // Wrapper and class.
    if (!empty($config['wrapper'])) {
      $wrapper = Html::escape($config['wrapper']);
      $class = (!empty($config['class'])) ? ' class="' . Html::escape($config['class']) . '"' : '';
      $output = '<' . $wrapper . $class . '>' . $output . '</' . $wrapper . '>';
    }

    return array(
      '#markup' => $output,
    );
  }

  /**
   * Returns the entity render key for this field.
   */
  protected function entityRenderKey() {
    return 'field_news_category';
  }

}
