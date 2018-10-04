<?php

namespace Drupal\pmmi_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Form\FormBuilder;

/**
 * Provides a 'PMMISearchBlock' block.
 *
 * @Block(
 *  id = "pmmi_search_block",
 *  admin_label = @Translation("PMMI Search block"),
 *  category = @Translation("PMMI Search")
 * )
 */
class PMMISearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Path\CurrentPathStack definition.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $pathCurrent;

  /**
   * Drupal\Core\Form\FormBuilder definition.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Path\CurrentPathStack $path_current
   *   The current path.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CurrentPathStack $path_current,
    FormBuilderInterface $form_builder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pathCurrent = $path_current;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.current'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'search_path' => '',
        'search_identifier' => '',
      ] + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $search_path = '';
    if (!empty($this->configuration['search_path'])) {
      $search_path = Url::fromUri($this->configuration['search_path'])
        ->toString();
    }
    $form['search_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Path'),
      '#description' => $this->t('Paths should start with /'),
      '#default_value' => $search_path,
      '#required' => TRUE,
      '#weight' => '1',
      '#element_validate' => [
        [
          get_called_class(),
          'validateUriElement',
        ],
      ],
    ];
    $form['search_identifier'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Identifier'),
      '#description' => $this->t('This will appear in the URL after the ? to identify this filter. Cannot be blank. Only letters, digits and the dot ("."), hyphen ("-"), underscore ("_"), and tilde ("~") characters are allowed.'),
      '#default_value' => $this->configuration['search_identifier'],
      '#required' => TRUE,
      '#weight' => '2',
      '#element_validate' => [
        [
          get_called_class(),
          'validateIdentifier',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['search_path'] = $form_state->getValue('search_path');
    $this->configuration['search_identifier'] = $form_state->getValue('search_identifier');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->formBuilder->getForm('Drupal\pmmi_search\Form\PMMISearchBlockForm', $this->getConfiguration());
  }

  /**
   * Form element validation handler for the 'uri' element.
   *
   * Disallows saving inaccessible or untrusted URLs.
   */
  public static function validateUriElement($element, FormStateInterface $form_state) {
    $string = $element['#value'];
    // @todo '<front>' is valid input for BC reasons, may be removed by
    //   https://www.drupal.org/node/2421941
    // - '<front>' -> '/'
    // - '<front>#foo' -> '/#foo'
    if (strpos($string, '<front>') === 0) {
      $string = '/' . substr($string, strlen('<front>'));
    }
    $uri = 'internal:' . $string;

    $form_state->setValueForElement($element, $uri);
    $search_path = Url::fromUri($uri);
    // URI , ensure the raw value begins with '/'.
    // @todo '<front>' is valid input for BC reasons, may be removed by
    //   https://www.drupal.org/node/2421941
    if (parse_url($uri, PHP_URL_SCHEME) === 'internal' && !in_array($element['#value'][0], ['/'], TRUE)
      && substr($element['#value'], 0, 7) !== '<front>'
    ) {
      $form_state->setError($element, t('Entered paths should start with /'));
      return;
    }
    elseif (!$search_path->isRouted()) {
      $form_state->setError($element, t('No route exist'));
      return;
    }
  }

  /**
   * Validates a filter identifier.
   *
   * @param array $element
   *   The identifier to check.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateIdentifier(array $element, FormStateInterface $form_state) {
    $error = '';
    $identifier = $element['#value'];
    if (empty($identifier)) {
      $error = t('The identifier is required.');
    }
    elseif ($identifier == 'value') {
      $error = t('This identifier is not allowed.');
    }
    elseif (preg_match('/[^a-zA-z0-9_~\.\-]/', $identifier)) {
      $error = t('This identifier has illegal characters.');
    }
    if (!empty($error) && !empty($identifier)) {
      $form_state->setError($element, $error);
      return;
    }
  }

}
