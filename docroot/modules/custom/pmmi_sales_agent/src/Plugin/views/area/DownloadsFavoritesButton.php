<?php

namespace Drupal\pmmi_sales_agent\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\area\TokenizeAreaPluginBase;

/**
 * Downloads favorites button.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("downloads_favorites_button")
 */
class DownloadsFavoritesButton extends TokenizeAreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text'] = array('default' => '');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['text'] = array(
      '#title' => $this->t('Button text'),
      '#type' => 'textfield',
      '#default_value' => $this->options['text'],
      '#rows' => 6,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      $uid = \Drupal::currentUser()->id();
      $dq = \Drupal::service('pmmi_sales_agent.downloads_quota');

      if (!$dq->availableDownloadsNumber($uid)) {
        $reporting_settings = \Drupal::service('config.factory')
          ->getEditable('pmmi_sales_agent.reporting_settings');

        return [
          '#markup' => $reporting_settings->get('exceeded_message'),
          '#prefix' => '<div class="quota-exceeded-message">',
          '#suffix' => '</div>',
        ];
      }

      $url = Url::fromUri('internal:/sales-agent-directory/favorites.csv');
      $link_options = ['attributes' => ['class' => ['pmmi-download-favorites']]];
      $url->setOptions($link_options);

      return [
        '#markup' => Link::fromTextAndUrl($this->renderTextarea($this->options['text']), $url)->toString(),
      ];
    }

    return [];
  }

  /**
   * Render a text area with \Drupal\Component\Utility\Xss::filterAdmin().
   */
  public function renderTextarea($value) {
    if ($value) {
      return $this->sanitizeValue($this->tokenizeValue($value), 'xss_admin');
    }
  }
}
