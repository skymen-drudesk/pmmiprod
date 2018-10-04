<?php

namespace Drupal\pmmi_search\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Xss;


/**
 * Builds the PMMI search form for the search block.
 */
class PMMISearchBlockForm extends FormBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new SearchBlockForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RendererInterface $renderer) {
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_search_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $data = NULL) {
    if (empty($data)) {
      $form['message'] = [
        '#markup' => $this->t('PMMI Search block is currently disabled'),
      ];
      return $form;
    }
    $current_request = \Drupal::request();
    $keywords = $current_request->query->get($data['search_identifier']);

    $form_state->setTemporaryValue('data', $data);
    $form['keys'] = [
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#default_value' => $keywords,
      '#name' => $data['search_identifier'],
      '#attributes' => ['title' => $this->t('Enter the keywords you wish to search for.')],
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      // Prevent op from showing up in the query string.
      '#name' => '',
    ];

    $this->renderer->addCacheableDependency($form, $data);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form submits to the search page, so processing happens there.
    $query = [];
    $data = $form_state->getTemporaryValue('data');
    $identifier = $data['search_identifier'];
    $url = Url::fromUri($data['search_path']);
    // Saved default path query param.
    $default_query = $url->getOption('query');
    $query[$identifier] = Xss::filter(trim($form_state->getUserInput()[$identifier]));
    if (!empty($default_query) && is_array($default_query)) {
      if (array_key_exists($identifier, $default_query)) {
        unset($default_query[$identifier]);
      }
      $query += $default_query;
    }
    $url->setOption('query', $query);
    $form_state->setRedirectUrl($url);
  }

}
