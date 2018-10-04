<?php

namespace Drupal\pmmi_search\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagService;
use Drupal\pmmi_search\Ajax\PageReloadCommand;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PMMIClearFavorites extends FormBase {

  /**
   * @var \Drupal\flag\FlagService
   */
  protected $flag;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $pathCurrent;

  /**
   * Constructs a PMMIClearFavorites object.
   *
   * @param \Drupal\flag\FlagService $flag
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\Core\Path\CurrentPathStack $path_current
   */
  public function __construct(
    FlagService $flag,
    AccountInterface $account,
    CurrentPathStack $path_current
  ) {
    $this->flag = $flag;
    $this->account = $account;
    $this->pathCurrent = $path_current;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flag'),
      $container->get('current_user'),
      $container->get('path.current')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_clear_favorites_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Do you really want to clear full list of favorites?'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear'),
      '#ajax' => [
        'callback' => [$this, 'submitForm'],
        'event' => 'click'
      ],
    ];

    $form['#attached']['library'][] = 'pmmi_search/pmmi_search.ajax';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new PageReloadCommand());

    // Get all flagged entities by current user.
    $view = Views::getView('my_favorites_companies');
    $view->setDisplay('block_1');
    $view->build();
    $view->preExecute();
    $view->execute();

    // Get flag object.
    $flag = $this->flag->getFlagById('favorites_content');

    // Remove all flags by current user, flag object and flagged entities.
    if (count($view->result) > 0) {
      foreach($view->result as $result_row) {
        $this->flag->unflag($flag, $result_row->_entity, $this->account);
      }
    }

    return $response;
  }

}
