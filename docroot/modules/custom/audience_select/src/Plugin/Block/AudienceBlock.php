<?php

namespace Drupal\audience_select\Plugin\Block;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\audience_select\Service\AudienceManager;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

/**
 * Provides a 'AudienceBlock' block.
 *
 * @Block(
 *  id = "audience_block",
 *  admin_label = @Translation("Audience block"),
 * )
 */
class AudienceBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The audience manager service.
   *
   * @var \Drupal\audience_select\Service\AudienceManager
   */
  protected $AudienceManager;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\audience_select\Service\AudienceManager $audience_manager
   *   The audience manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AudienceManager $audience_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->AudienceManager = $audience_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('audience_select.audience_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'audience_id' => '',
        'image_style' => 'block_style_1',
        'audience_overrides' => [],
      ] + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $overrides = $this->configuration['audience_overrides'];
    $styleselect = [];
    foreach (ResponsiveImageStyle::loadMultiple() as $style) {
      $styleselect[$style->id()] = $style->label();
    }
    $form['audience_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Audience'),
      '#description' => $this->t('Select Auidience'),
      '#default_value' => $this->configuration['audience_id'],
      '#options' => $this->AudienceManager->getOptionsList(),
      '#required' => TRUE,
      '#weight' => '1',
    ];
    $form['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Audience Responsive Image style'),
      '#description' => $this->t('Select Auidience Image style'),
      '#default_value' => $this->configuration['image_style'],
      '#options' => $styleselect,
      '#required' => TRUE,
      '#weight' => '2',
    ];
    // Overrides defaults.
    $form['overrides'] = [
      '#type' => 'details',
      '#title' => $this->t('Overrides defaults'),
      '#open' => !empty($overrides),
      '#tree' => TRUE,
      '#weight' => '2',
    ];
    $form['overrides']['audience_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Audience Title Override'),
      '#description' => $this->t('Override default Audience Title'),
      '#default_value' => array_key_exists('audience_title', $overrides) ? $overrides['audience_title'] : NULL,
      '#maxlength' => 48,
      '#size' => 48,
      '#weight' => '0',
    ];
    if (array_key_exists('audience_redirect_url', $overrides)) {
      $default_url = $this->AudienceManager->getUriAsDisplayableString($overrides['audience_redirect_url']);
    }
    else {
      $default_url = NULL;
    }
    $form['overrides']['audience_redirect_url'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Audience Redirect Url Override'),
      '#description' => $this->t('Referenced to node. Manually entered paths should start with /, ? or #.'),
      '#default_value' => $default_url,
      '#attributes' => [
        'data-autocomplete-first-character-blacklist' => '/#?',
      ],
      '#element_validate' => [
        [
          get_called_class(),
          'validateUriElement',
        ],
      ],
      '#process_default_value' => FALSE,
      '#maxlength' => 200,
      '#size' => 48,
      '#weight' => '1',
    ];
    $form['overrides']['audience_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Audience Image Override'),
      '#description' => $this->t('Override default background image for Audience'),
      '#default_value' => array_key_exists('audience_image', $overrides) ? $overrides['audience_image'] : NULL,
      '#upload_location' => 'public://audience/image/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
      ],
      '#weight' => '2',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $style_id = $this->configuration['image_style'];
    /** @var \Drupal\image\ImageStyleInterface $style */
    if ($style_id && $style = ResponsiveImageStyle::load($style_id)) {
      // If this formatter uses a valid image style to display the image, add
      // the image style configuration entity as dependency of this formatter.
      $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['audience_id'] = $form_state->getValue('audience_id');
    $this->configuration['image_style'] = $form_state->getValue('image_style');
    $overrides = $form_state->getValue('overrides');
    $result_overrides = array_filter($overrides);
    if (!empty($result_overrides)) {
      $this->setConfigurationValue('audience_overrides', $result_overrides);
      if (array_key_exists('audience_image', $result_overrides)) {
        $image = File::load($result_overrides['audience_image'][0]);
        if ($image->isTemporary()) {
          $image->setPermanent();
          $image->save();
          /** @var \Drupal\file\FileUsage\DatabaseFileUsageBackend $file_usage */
          $file_usage = \Drupal::service('file.usage');
          $file_usage->add($image, 'audience_select', 'user', 1);
        }
      }
    }
    else {
      $this->setConfigurationValue('audience_overrides', []);
    }
  }

  /**
   * Form element validation handler for the 'uri' element.
   *
   * Disallows saving inaccessible or untrusted URLs.
   */
  public static function validateUriElement($element, FormStateInterface $form_state, $form) {
    LinkWidget::validateUriElement($element, $form_state, $form);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $audience_id = $this->configuration['audience_id'];
    /** @var \Drupal\image\Entity\ImageStyle $image_style */
    $image_style = ResponsiveImageStyle::load($this->configuration['image_style'])
      ->id();
    $audience = AudienceManager::load($audience_id);
    $overrides = $this->configuration['audience_overrides'];
    if (!empty($overrides)) {
      foreach ($overrides as $key => $value) {
        $audience[$key] = $value;
      }
    }
    $image_uri = '';
    if (array_key_exists('audience_image', $audience)) {
      if (!empty($audience['audience_image'])) {
        $image = File::load($audience['audience_image'][0]);
        if (!empty($image)) {
          $image_uri = $image->getFileUri();
        }
      }
    }
    $options = [
      'query' => ['audience' => $audience_id],
    ];
    $request = \Drupal::request();
    if ($request->query->has('dest') && !UrlHelper::isExternal($request->query->get('dest'))) {
      $options['query']['destination'] = $request->query->get('dest');
    }
    $url = Url::fromUri($audience['audience_redirect_url'], $options);
    $build['#theme'] = 'audience_select_block';
    $build['#audience_title'] = $audience['audience_title'];
    $build['#audience_image'] = [
      '#type' => 'responsive_image',
      '#uri' => $image_uri,
      '#responsive_image_style_id' => $image_style,
    ];
    $build['#audience_redirect_url'] = $url;
    // Disable cache for this block.
    $build['#cache']['max-age'] = 0;
    return $build;
  }

}
