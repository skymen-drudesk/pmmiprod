<?php

namespace Drupal\pmmi_search\Plugin\Block;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\VocabularyStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Path\CurrentPathStack;

/**
 * Provides a 'PMMISearchLibraryBlock' block.
 *
 * @Block(
 *  id = "pmmi_search_library_block",
 *  admin_label = @Translation("PMMI Search Library Block"),
 * )
 */
class PMMISearchLibraryBlock extends PMMISearchBlock implements ContainerFactoryPluginInterface {

  /**
   * The vocabulary storage.
   *
   * @var \Drupal\taxonomy\VocabularyStorageInterface
   */
  protected $vocabularyStorage;

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
   * @param \Drupal\taxonomy\VocabularyStorageInterface $vocabulary_storage
   *   The vocabulary storage.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CurrentPathStack $path_current,
    FormBuilderInterface $form_builder,
    VocabularyStorageInterface $vocabulary_storage
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $path_current, $form_builder);
    $this->vocabularyStorage = $vocabulary_storage;
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
      $container->get('form_builder'),
      $container->get('entity_type.manager')->getStorage('taxonomy_vocabulary')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'vid' => '',
        'term_identifier' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $vocabularies = $this->vocabularyStorage->loadMultiple();
    $options = array();
    foreach ($vocabularies as $voc) {
      $options[$voc->id()] = $voc->label();
    }
    $search_path = '';
    if (!empty($this->configuration['search_path'])) {
      $search_path = Url::fromUri($this->configuration['search_path'])
        ->toString();
    }
    $form['search_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Path'),
      '#description' => $this->t('Start typing the title of a piece of content to select it. You can also enter an internal path such as %add-node or an external URL such as %url. Enter %front to link to the front page.', array(
        '%front' => '<front>',
        '%add-node' => '/node/add',
        '%url' => 'http://example.com'
      )),
      '#default_value' => $search_path,
      '#required' => TRUE,
      '#maxlength' => 2048,
      '#weight' => '1',
      '#element_validate' => array(
        array(
          get_called_class(),
          'validateUriElement',
        ),
      ),
    ];
    $form['vid'] = [
      '#type' => 'radios',
      '#title' => t('Select vocabulary for filter'),
      '#weight' => '3',
      '#options' => $options,
      '#default_value' => $this->configuration['vid'],
    ];
    $form['term_identifier'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Taxonomy Term Identifier'),
      '#description' => $this->t('This will appear in the URL after the ? to identify this filter. Cannot be blank. Only letters, digits and the dot ("."), hyphen ("-"), underscore ("_"), and tilde ("~") characters are allowed.'),
      '#default_value' => $this->configuration['term_identifier'],
      '#required' => TRUE,
      '#weight' => '4',
      '#element_validate' => array(
        array(
          get_called_class(),
          'validateIdentifier',
        ),
      ),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['vid'] = $form_state->getValue('vid');
    $this->configuration['term_identifier'] = $form_state->getValue('term_identifier');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->formBuilder->getForm('Drupal\pmmi_search\Form\PMMISearchLibraryBlockForm', $this->getConfiguration());;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $vid = $this->configuration['vid'];
    /** @var \Drupal\image\ImageStyleInterface $style */
    if ($vid && $vocab = $this->vocabularyStorage->load($vid)) {
      // If this block uses a valid Taxonomy Vocabulary, add
      // the vocabulary configuration entity as dependency of this block.
      $dependencies[$vocab->getConfigDependencyKey()][] = $vocab->getConfigDependencyName();
    }
    return $dependencies;
  }

  /**
   * Form element validation handler for the 'uri' element.
   *
   * Disallows saving inaccessible or untrusted URLs.
   */
  public static function validateUriElement($element, FormStateInterface $form_state) {
    $uri = static::getUserEnteredStringAsUri($element['#value']);
    $form_state->setValueForElement($element, $uri);

    // URI , ensure the raw value begins with '/'.
    // @todo '<front>' is valid input for BC reasons, may be removed by
    //   https://www.drupal.org/node/2421941
    if (parse_url($uri, PHP_URL_SCHEME) === 'internal' && !in_array($element['#value'][0], ['/'], TRUE)
      && substr($element['#value'], 0, 7) !== '<front>'
    ) {
      $form_state->setError($element, t('Entered paths should start with /'));
      return;
    }
    $search_path = Url::fromUri($uri);
    if (!$search_path->isRouted() && !$search_path->isExternal()) {
      $form_state->setError($element, t('No route exist'));
      return;
    }
  }

  /**
   * Gets the user-entered string as a URI.
   *
   * The following two forms of input are mapped to URIs:
   * - entity autocomplete ("label (entity id)") strings: to 'entity:' URIs;
   * - strings without a detectable scheme: to 'internal:' URIs.
   *
   * This method is the inverse of ::getUriAsDisplayableString().
   *
   * @param string $string
   *   The user-entered string.
   *
   * @return string
   *   The URI, if a non-empty $uri was passed.
   *
   * @see static::getUriAsDisplayableString()
   */
  protected static function getUserEnteredStringAsUri($string) {
    // By default, assume the entered string is an URI.
    $uri = $string;

    // Detect a schemeless string, map to 'internal:' URI.
    if (!empty($string) && parse_url($string, PHP_URL_SCHEME) === NULL) {
      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      // - '<front>' -> '/'
      // - '<front>#foo' -> '/#foo'
      if (strpos($string, '<front>') === 0) {
        $string = '/' . substr($string, strlen('<front>'));
      }
      $uri = 'internal:' . $string;
    }

    return $uri;
  }

}
