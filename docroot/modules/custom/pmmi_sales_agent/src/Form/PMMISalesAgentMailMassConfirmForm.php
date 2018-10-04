<?php

namespace Drupal\pmmi_sales_agent\Form;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;

/**
 * Defines a confirmation form for sending mass email.
 */
class PMMISalesAgentMailMassConfirmForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_sales_agent_mail_mass_confirm_form';
  }

  /**
   * Return from from non object context;
   */
  public static function getFormIdStatic() {
    return 'pmmi_sales_agent_mail_mass_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to send a mass email?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('pmmi_sales_agent.mass_email');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Only do this if you are sure!');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Continue');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->getQuestion();
    $mm_config = \Drupal::service('config.factory')
      ->getEditable('pmmi_sales_agent.mail_mass_settings');
    $last_run = $mm_config->get('last_run');
    $form['#attributes']['class'][] = 'confirmation';
    $form['description'] = ['#markup' => $this->getDescription()];
    $form[$this->getFormName()] = ['#type' => 'hidden', '#value' => 1];
    $form['check_prev'] = [
      '#type' => 'checkbox',
      '#title' => t('Send undelivered emails from prev batch'),
    ];
    $form['process'] = [
      '#type' => 'item',
      '#markup' => $this->t('<strong>Will processed all undelivered emails where field_last_mass_email_sent doesn\'t match the last batch run (@date)</strong>', [
        '@date' => !empty($last_run) ? date('Y-m-d H:i:s', $last_run) : '-/-'
      ]),
      '#states' => [
        'visible' => [
          ':input[name="check_prev"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->getConfirmText(),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->getRequest());

    // By default, render the form using theme_confirm_form().
    if (!isset($form['#theme'])) {
      $form['#theme'] = 'confirm_form';
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operations = [];
    $mm_config = \Drupal::service('config.factory')
      ->getEditable('pmmi_sales_agent.mail_mass_settings');
    // Save info about last run.
    $values = $form_state->getValues();
    $last_run = FALSE;
    if (empty($values['check_prev']) && $values['check_prev'] == 0) {
      $last_run_object = \Drupal::time()->getRequestTime();
      $last_run = date('Y-m-d H:i:s', $last_run_object);
      $mm_config->set('last_run', $last_run_object)->save();
      // Get all companies, which should receive mass email.
      $nids = $this->massMailGetContacts($mm_config->get('filter'));
    }
    else {
      $last_run_object = $mm_config->get('last_run');
      $last_run = date('Y-m-d H:i:s', $last_run_object);
      // Get all unprocessed companies, where field_last_mass_email_sent doesn't match the last cron run
      $nids = $this->massMailGetContacts($mm_config->get('filter'), $last_run);
    }
    if ($nids) {
      // The sales agent mail settings.
      $mail_settings = \Drupal::config('pmmi_sales_agent.mail_settings');
      // Process 10 items per operation.
      foreach (array_chunk($nids, 10) as $chunk) {
        $operations[] = [
          [__CLASS__, 'process'],
          [$chunk, $mail_settings->get('ss_update.subject'), $mail_settings->get('ss_update.body'), $mail_settings->get('mail_notification_address'), $last_run]
        ];
      }
    }

    $batch_definition = [
      'operations' => $operations,
      'finished' => [__CLASS__, 'finish'],
    ];

    // Schedule the batch.
    batch_set($batch_definition);

    // Redirect to the previous page after batch will be finished.
    $url = \Drupal\Core\Url::fromRoute('pmmi_sales_agent.mass_email');
    $form_state->setRedirectUrl($url);
  }

  /**
   * Processes an email batch operation.
   *
   * @param array $nids
   *   The company IDs.
   * @param string $subject
   *   The mail subject.
   * @param string $body
   *   The mail body.
   * @param string $from
   *   The email address to be used as the 'from' address.
   * @param string|boolean $batch_last_day
   *   For internal usage when need to send undelivered emails since the last cron run
   * @param array|\ArrayAccess $context
   *   The context of the current batch, as defined in the @link batch Batch
   *   operations @endlink documentation.
   */
  public static function process($nids, $subject, $body, $from, $batch_last_day,  &$context) {
    $entity_manager = \Drupal::entityTypeManager();
    $mailManager = \Drupal::service('plugin.manager.mail');

    // Check if the results should be initialized.
    if (!isset($context['results']['processed'])) {
      // Initialize the results with data which is shared among the batch runs.
      $context['results']['all'] = 0;
      $context['results']['processed'] = 0;
    }

    $nodes = $entity_manager->getStorage('node')->loadMultiple($nids);
    foreach ($nodes as $node) {
      $context['results']['all']++;
      $to = $node->get('field_primary_contact_email')->getValue();
      if (!empty($to[0]['value']) && \Drupal::service('email.validator')->isValid($to[0]['value'])) {
        // Compose and send an email.
        $current_langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $params = ['subject' => $subject, 'body' => $body, 'from' => $from, 'node' => $node];
        try {
          $result = $mailManager->mail('pmmi_sales_agent', 'pmmi_sales_agent_mass', $to[0]['value'], $current_langcode, $params);
          if ($result['result'] == true) {
            $context['results']['processed']++;
            self::saveRemindMailsInfo($node->id(), $batch_last_day);
          }
          else {
            \Drupal::logger('pmmi_sales_agent')->error(t("Can't send an email to @address"), ['@address' => $to[0]['value']]);
          }
        } catch (Exception $e) {
          $context['errors'][] = [
            '@code' => $e->getCode(),
            '@exception' => $e->getMessage()
          ];
          _db_email_login(
            'exception',
            'error',
            $e
          );
        }
      }
      else {
        \Drupal::logger('pmmi_sales_agent')->notice(t("Mass mail wasn't sent to a company with @id id, because email address isn't specified or incorrect for this company.", ['@id' => $node->id()]));
      }
    }
  }

  /**
   * Finishes a batch.
   */
  public static function finish ($success, $results, $operations) {
    // Check if the batch job was successful.
    if ($results['all'] != $results['processed']) {
      _db_email_login(
        'PMMISalesAgentMailMassConfirmFormFinish',
        'error',
        $results
      );
    }
    else {
      _db_email_login(
        'PMMISalesAgentMailMassConfirmFormFinish',
        'info',
        $results
      );
    }
    if ($success) {
      // Display the number of items which were processed.
      drupal_set_message(t('Processed @processed companies from @companies.', ['@processed' => $results['processed'], '@companies' => $results['all']]));
      // Add warning message to notify that not all companies received a message.
      if ($results['processed'] != $results['all']) {
        drupal_set_message(t('The message was not sent to all companies. Check logs for detailed info.'), 'warning');
      }
    }
    else {
      // Notify user about batch job failure.
      drupal_set_message(t('An error occurred while trying send mass email. Check the logs for details.'), 'error');
    }
  }

  /**
   * Get companies which should receive remind mass email.
   *
   * @param string $filter
   *   The 'last update on' filter.
   * @param boolean|string $last_run
   *    Date of last batch run.
   * @return array The company ID's.
   * The company ID's.
   */
  protected function massMailGetContacts($filter, $last_run = false) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', PMMI_SALES_AGENT_CONTENT);
    if (!empty($last_run)) {
      $batch_group = $query->orConditionGroup()
        ->condition('field_last_mass_email_sent.value', $last_run, '<')
        ->condition('field_last_mass_email_sent', NULL, 'IS NULL');
      $query->condition($batch_group);
    }
    if ($filter != 'all') {
      $date = DateTimePlus::createFromTimestamp(strtotime($filter))->format(DATETIME_DATETIME_STORAGE_FORMAT);
      $group = $query->orConditionGroup()
        ->condition('field_last_updated_on.value', $date, '<=')
        ->condition('field_last_updated_on', NULL, 'IS NULL');

      $query->condition($group);
    }
    return $query->execute();
  }

  /**
   * Save information about future remind messages.
   *
   * @param int $nid
   *   The company ID.
   * @param string|boolean $batch_last_day
   *   For internal usage when need to send undelivered emails since the last cron run.
   */
  protected static function saveRemindMailsInfo($nid, $batch_last_day) {
    $entity = \Drupal\node\Entity\Node::load($nid);
    $now = (new \DateTime())->format('Y/m/d');
    if (!empty($batch_last_day)) {
      $now = $batch_last_day;
    }
    $entity->set('field_last_mass_email_sent', $now);
    $entity->save();
    $mm_config = \Drupal::service('config.factory')
      ->getEditable('pmmi_sales_agent.mail_mass_settings');

    // The 3 drip emails should be sent during specific period of time. So,
    // insert necessary count of records.
    $connection = Database::getConnection();
    for ($i = 1; $i <= 3; $i++) {
      $connection->insert('pmmi_sales_agent_mails')->fields([
        'nid' => $nid,
        'type' => PMMI_SALES_AGENT_MAIL_MASS_REMIND,
        'sending_date' => $mm_config->get('remind_period') * $i + REQUEST_TIME,
      ])->execute();
    }
  }
}
