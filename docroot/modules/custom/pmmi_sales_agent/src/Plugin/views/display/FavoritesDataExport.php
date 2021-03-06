<?php

namespace Drupal\pmmi_sales_agent\Plugin\views\display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\rest\Plugin\views\display\RestExport;
use Drupal\views\Views;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Provides specific data export display plugin for user favorites.
 *
 * This overrides the REST Export display to make labeling clearer on the admin
 * UI, and to allow attaching of these to other displays.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "favorites_data_export",
 *   title = @Translation("Favorites data export"),
 *   help = @Translation("Export the view results to a file. Can handle very large result sets."),
 *   uses_route = TRUE,
 *   admin = @Translation("Favorites data export"),
 *   returns_response = TRUE
 * )
 */
class FavoritesDataExport extends RestExport {

  /**
   * {@inheritdoc}
   */
  public static function buildResponse($view_id, $display_id, array $args = []) {
    // Load the View we're working with and set it's display ID so we can get
    // the exposed input.
    $view = Views::getView($view_id);
    $view->setDisplay($display_id);
    $view->setArguments($args);
    return self::buildBatch($view);
  }

  /**
   * Builds batch export response.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to export.
   *
   * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to the batching page.
   */
  protected static function buildBatch(ViewExecutable $view) {
    // Get total number of items.
    $view->get_total_rows = TRUE;
    $export_limit = $view->getDisplay()->getOption('export_limit');

    $view->build();
    $view->get_total_rows = TRUE;
    // Don't load and instantiate so many entities.
    $view->query->setLimit(1);
    $view->execute();
    $total_rows = $view->total_rows;

    // Get available number of downloads.
    $uid = \Drupal::currentUser()->id();
    $available_downloads = \Drupal::service('pmmi_sales_agent.downloads_quota')
      ->availableDownloadsNumber($uid);

    // Set the total to number of available downloads, if the number of
    // available records less than total number.
    if ($total_rows > $available_downloads) {
      $total_rows = $available_downloads;
    }

    // If export limit is set and the number of rows is greater than the
    // limit, then set the total to limit.
    if ($export_limit && $export_limit < $total_rows) {
      $total_rows = $export_limit;
    }

    $batch_definition = [
      'operations' => [
        [
          [static::class, 'processBatch'],
          [
            $view->id(),
            $view->current_display,
            $view->args,
            $view->getExposedInput(),
            $total_rows,
          ],
        ],
      ],
      'title' => t('Exporting data...'),
      'progressive' => TRUE,
      'progress_message' => '@percentage% complete. Time elapsed: @elapsed',
      'finished' => [static::class, 'finishBatch'],
      'type' => 'favorites',
    ];
    batch_set($batch_definition);

    // Fall back to favourites page.
    $favourites_url = Url::fromUserInput('/sales-agent-directory/favorites')->toString();
    return batch_process($favourites_url);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['displays'] = ['default' => []];

    // Set the default style plugin, and default to fields.
    $options['style']['contains']['type']['default'] = 'data_export';
    $options['row']['contains']['type']['default'] = 'data_field';

    // We don't want to use pager as it doesn't make any sense. But it cannot
    // just be removed from a view as it is core functionality. These values
    // will be controlled by custom configuration.
    $options['pager']['contains'] = [
      'type' => ['default' => 'none'],
      'options' => ['default' => ['offset' => 0]],
    ];

    $options['export_batch_size'] = ['default' => '1000'];
    $options['export_limit'] = ['default' => '0'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    // Doesn't make sense to have a pager for data export so remove it.
    unset($categories["pager"]);

    // Add a view configuration category for data export settings in the
    // second column.
    $categories['export_settings'] = [
      'title' => $this->t('Export settings'),
      'column' => 'second',
      'build' => [
        '#weight' => 50,
      ],
    ];
    $options['export_batch_size'] = [
      'category' => 'export_settings',
      'title' => $this->t('export_batch_size'),
      'desc' => $this->t('The maximum amount of rows to export.'),
    ];
    $options['export_batch_size']['value'] = $this->options['export_batch_size'];

    $options['export_limit'] = [
      'category' => 'export_settings',
      'title' => $this->t('Limit'),
      'desc' => $this->t('The maximum amount of rows to export.'),
    ];

    $limit = $this->getOption('export_limit');
    if ($limit) {
      $options['export_limit']['value'] = $this->t('@nr rows', ['@nr' => $limit]);
    }
    else {
      $options['export_limit']['value'] = $this->t('no limit');
    }

    $displays = array_filter($this->getOption('displays'));
    if (count($displays) > 1) {
      $attach_to = $this->t('Multiple displays');
    }
    elseif (count($displays) == 1) {
      $display = array_shift($displays);
      $displays = $this->view->storage->get('display');
      if (!empty($displays[$display])) {
        $attach_to = $displays[$display]['display_title'];
      }
    }

    if (!isset($attach_to)) {
      $attach_to = $this->t('None');
    }

    $options['displays'] = array(
      'category' => 'path',
      'title' => $this->t('Attach to'),
      'value' => $attach_to,
    );

    // Add filename to the summary if set.
    if ($this->getOption('filename')) {
      $options['path']['value'] .= $this->t(' (@filename)', ['@filename' => $this->getOption('filename')]);
    }

    // Display the selected format from the style plugin if available.
    $style_options = $this->getOption('style')['options'];
    if (!empty($style_options['formats'])) {
      $options['style']['value'] .= $this->t(' (@export_format)', ['@export_format' => reset($style_options['formats'])]);
    }
  }
  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Remove the 'serializer' option to avoid confusion.
    switch ($form_state->get('section')) {
      case 'style':
        unset($form['style']['type']['#options']['serializer']);
        break;

      case 'export_batch_size':
        $form['export_batch_size'] = [
          '#type' => 'number',
          '#title' => $this->t('Batch size'),
          '#description' => $this->t("The number of rows to process under a request."),
          '#default_value' => $this->options['export_batch_size'],
          '#required' => TRUE,
        ];
        break;

      case 'export_limit':
        $form['export_limit'] = [
          '#type' => 'number',
          '#title' => $this->t('Limit'),
          '#description' => $this->t("The maximum amount of rows to export. 0 means unlimited."),
          '#default_value' => $this->options['export_limit'],
          '#min' => 0,
          '#required' => TRUE,
        ];

        break;

      case 'path':
        $form['filename'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Filename'),
          '#default_value' => $this->options['filename'],
          '#description' => $this->t('The filename that will be suggested to the browser for downloading purposes. You may include replacement patterns from the list below.'),
        ];

        $form['automatic_download'] = [
          '#type' => 'checkbox',
          '#title' => $this->t("Download instantly"),
          '#description' => $this->t("Check this if you want to download the file instantly after being created. Otherwise you will be redirected to front-page containing the download link."),
          '#default_value' => $this->options['automatic_download'],
        ];

        // Support tokens.
        $this->globalTokenForm($form, $form_state);
        break;

      case 'displays':
        $form['#title'] .= $this->t('Attach to');
        $displays = [];
        foreach ($this->view->storage->get('display') as $display_id => $display) {
          if ($this->view->displayHandlers->has($display_id) && $this->view->displayHandlers->get($display_id)->acceptAttachments()) {
            $displays[$display_id] = $display['display_title'];
          }
        }
        $form['displays'] = [
          '#title' => $this->t('Displays'),
          '#type' => 'checkboxes',
          '#description' => $this->t('The data export icon will be available only to the selected displays.'),
          '#options' => array_map('\Drupal\Component\Utility\Html::escape', $displays),
          '#default_value' => $this->getOption('displays'),
        ];
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function attachTo(ViewExecutable $clone, $display_id, array &$build) {
    $displays = $this->getOption('displays');
    if (empty($displays[$display_id])) {
      return;
    }

    // Defer to the feed style; it may put in meta information, and/or
    // attach a feed icon.
    $clone->setArguments($this->view->args);
    $clone->setDisplay($this->display['id']);
    $clone->buildTitle();
    if ($plugin = $clone->display_handler->getPlugin('style')) {
      $plugin->attachTo($build, $display_id, $clone->getUrl(), $clone->getTitle());
      foreach ($clone->feedIcons as $feed_icon) {
        $this->view->feedIcons[] = $feed_icon;
      }
    }

    // Clean up.
    $clone->destroy();
    unset($clone);
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    switch ($section) {
      case 'displays':
        $this->setOption($section, $form_state->getValue($section));
        break;

      case 'export_batch_size':
        $batch_size = $form_state->getValue('export_batch_size');
        $this->setOption('export_batch_size', $batch_size > 1 ? $batch_size : 1);
        break;

      case 'export_limit':
        $limit = $form_state->getValue('export_limit');
        $this->setOption('export_limit', $limit > 0 ? $limit : 0);

        // Set the limit option on the pager as-well. This is used for the
        // standard rendering.
        $this->setOption('pager', [
          'type' => 'some',
          'options' => [
            'items_per_page' => $limit,
            'offset' => 0,
          ],
        ]);
        break;

      case 'path':
        $this->setOption('filename', $form_state->getValue('filename'));
        $this->setOption('automatic_download', $form_state->getValue('automatic_download'));
        break;
    }
  }

  /**
   * Implements callback_batch_operation() - perform processing on each batch.
   *
   * Writes rendered data export View rows to an output file that will be
   * returned by callback_batch_finished() (i.e. finishBatch) when we're done.
   *
   * @param string $view_id
   *   ID of the view.
   * @param string $display_id
   *   ID of the view display.
   * @param array $args
   *   Views arguments.
   * @param array $exposed_input
   *   Exposed input.
   * @param mixed $context
   *   Batch context information.
   */
  public static function processBatch($view_id, $display_id, array $args, array $exposed_input, $total_rows, &$context) {
    // Load the View we're working with and set it's display ID so we get the
    // content we expect.
    $view = Views::getView($view_id);
    $view->setDisplay($display_id);
    $view->setArguments($args);
    $view->setExposedInput($exposed_input);

    if (isset($context['sandbox']['progress'])) {
      $view->setOffset($context['sandbox']['progress']);
    }

    $display_handler = $view->display_handler;

    // Build the View so the query parameters and offset get applied.
    // This is necessary for the total to be calculated accurately and the call
    // to $view->render() to return the items we expect to process in the
    // current batch (i.e. not the same set of N, where N is the number of
    // items per page, over and over).
    $view->build();

    // First time through - create an output file to write to, set our
    // current item to zero and our total number of items we'll be processing.
    if (empty($context['sandbox'])) {
      // Initialize progress counter, which will keep track of how many items
      // we've processed.
      $context['sandbox']['progress'] = 0;

      // Initialize file we'll write our output results to.
      // This file will be written to with each batch iteration until all
      // batches have been processed.
      // This is a private file because some use cases will want to restrict
      // access to the file. The View display's permissions will govern access
      // to the file.
      $filename =  \Drupal::token()->replace($view->getDisplay()->options['filename'], array('view' => $view));
      $destination = 'private://' . $filename;
      $file = file_save_data('', $destination, FILE_EXISTS_REPLACE);
      if (!$file) {
        // Failed to create the file, abort the batch.
        unset($context['sandbox']);
        $context['success'] = FALSE;
        return;
      }

      $file->setTemporary();
      $file->save();
      // Create sandbox variable from filename that can be referenced
      // throughout the batch processing.
      $context['sandbox']['vde_file'] = $file->getFileUri();
    }

    // Render the current batch of rows - these will then be appended to the
    // output file we write to each batch iteration.
    // Make sure that if limit is set the last batch will output the remaining
    // amount of rows and not more.
    $items_this_batch = $display_handler->getOption('export_batch_size');
    if ($total_rows && $context['sandbox']['progress'] + $items_this_batch > $total_rows) {
      $items_this_batch = $total_rows - $context['sandbox']['progress'];
    }

    // Set the limit directly on the query.
    $view->query->setLimit((int) $items_this_batch);
    $rendered_rows = $view->render();
    $string = (string) $rendered_rows['#markup'];

    // Workaround for CSV headers, remove the first line.
    if ($context['sandbox']['progress'] != 0 && reset($view->getStyle()->options['formats']) == 'csv') {
      $string = preg_replace('/^[^\n]+/', '', $string);
    }

    // Write rendered rows to output file.
    if (file_put_contents($context['sandbox']['vde_file'], $string, FILE_APPEND) === FALSE) {
      // Write to output file failed - log in logger and in ResponseText on
      // batch execution page user will end up on if write to file fails.
      $message = \Drupal::service('config.factory')
        ->getEditable('pmmi_sales_agent.reporting_settings')
        ->get('write_failed_message');

      $rendered_message = Markup::create($message);
      \Drupal::logger('views_data_export')->error($rendered_message);
      throw new ServiceUnavailableHttpException(NULL, $rendered_message);
    };

    // Update the progress of our batch export operation (i.e. number of
    // items we've processed). Note can exceed the number of total rows we're
    // processing, but that's considered in the if/else to determine when we're
    // finished below.
    $context['sandbox']['progress'] += $items_this_batch;

    // If our progress is less than the total number of items we expect to
    // process, we updated the "finished" variable to show the user how much
    // progress we've made via the progress bar.
    if ($context['sandbox']['progress'] < $total_rows) {
      $context['finished'] = $context['sandbox']['progress'] / $total_rows;
    }
    else {
      // We're finished processing, set progress bar to 100%.
      $context['finished'] = 1;
      // Store URI of export file in results array because it can be accessed
      // in our callback_batch_finished (finishBatch) callback. Better to do
      // this than use a SESSION variable. Also, we're not returning any
      // results so the $context['results'] array is unused.
      $context['results'] = [
        'vde_file' => $context['sandbox']['vde_file'],
        'automatic_download' => $view->display_handler->options['automatic_download'],
        'total' => $total_rows,
      ];
    }
  }

  /**
   * Implements callback for batch finish.
   *
   * @param bool $success
   *    Indicates whether we hit a fatal PHP error.
   * @param array $results
   *    Contains batch results.
   * @param array $operations
   *    If $success is FALSE, contains the operations that remained unprocessed.
   *
   * @return RedirectResponse
   *    Where to redirect when batching ended.
   */
  public static function finishBatch($success, array $results, array $operations) {
    $config = \Drupal::service('config.factory')
      ->getEditable('pmmi_sales_agent.reporting_settings');

    // Set Drupal status message to let the user know the results of the export.
    // The 'success' parameter means no fatal PHP errors were detected.
    // All other error management should be handled using 'results'.
    if ($success && isset($results['vde_file']) && file_exists($results['vde_file'])) {
      $uid = \Drupal::currentUser()->id();

      // Create new user stat as user has used download favorites feature.
      \Drupal::entityTypeManager()->getStorage('sad_user_stat')
        ->create([
          'uid' => $uid,
          'type' => 'records_download',
          'field_records_number' => $results['total'],
        ])->save();

      // Check the permissions of the file to grant access and allow
      // modules to hook into permissions via hook_file_download().
      $headers = \Drupal::moduleHandler()->invokeAll('file_download', [$results['vde_file']]);
      // Require at least one module granting access and none denying access.
      if (!empty($headers) && !in_array(-1, $headers)) {

        // Create a web server accessible URL for the private file.
        // Permissions for accessing this URL will be inherited from the View
        // display's configuration.
        $url = file_create_url($results['vde_file']);
        $_SESSION['favorites_csv_download_file'] = $url;

        $message = str_replace('[:download_url]', $url, $config->get('success_message'));
        $rendered_message = Markup::create($message);
        drupal_set_message($rendered_message);
      }
    }
    else {
      $rendered_message = Markup::create($config->get('failed_message'));
      \Drupal::logger('views_data_export')->error($rendered_message);
      drupal_set_message($rendered_message, 'error');
    }
  }
}
