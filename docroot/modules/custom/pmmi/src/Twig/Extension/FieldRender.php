<?php

namespace Drupal\pmmi\Twig\Extension;


/**
 * A class providing Drupal Twig extensions.
 *
 * This provides a Twig extension that help to render fields in templates.
 *
 * @see \Drupal\Core\CoreServiceProvider
 */
class FieldRender extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('field_render', [$this, 'renderField'], [
        'is_safe' => array('html'),
        'needs_environment' => TRUE,
        'needs_context' => TRUE,
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'field_render';
  }

  /**
   * {@inheritdoc}
   */
  public function renderField(\Twig_Environment $env, array $context, array $args = []) {
    $output = '';
    if (!empty($args)) {
      $args = $args + array(NULL, NULL, NULL, NULL);
      list($field, $class, $tag, $id) = $args;
      if (!empty($field)) {
        $rendered = render($field);
        if (!empty(trim($rendered))) {
          $class = !empty($class) ? 'class="' . $class . '"' : '';
          $id = !empty($id) ? 'id="' . $id . '"' : '';
          $tag = !isset($tag) ? 'div' : $tag;
          if (!empty($tag)) {
            $output = "<{$tag} {$class} {$id}>" . $rendered . "</{$tag}>";
          }
          else {
            $output = $rendered;
          }
        }
      }
    }
    return $output;
  }

}
