<?php

namespace Drupal\pmmi_sales_agent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PMMISalesAgentMailSettingsForm.
 *
 * @package Drupal\pmmi_sales_agent\Form
 */
class PMMISalesAgentMailSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pmmi_sales_agent.mail_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_sales_agent_mail_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('pmmi_sales_agent.mail_settings');
    $site_config = $this->config('system.site');

    // Default notifications address.
    $form['mail_notification_address'] = array(
      '#type' => 'email',
      '#title' => $this->t('Notification email address'),
      '#default_value' => $config->get('mail_notification_address'),
      '#description' => $this->t("The email address to be used for all notifications listed below. Leave empty to use the default system email address <em>(%site-email).</em>", array('%site-email' => $site_config->get('mail'))),
      '#maxlength' => 180,
    );

    $form['remind_period'] = [
      '#type' => 'number',
      '#title' => $this->t('Remind period'),
      '#default_value' => $config->get('remind_period'),
      '#description' => $this->t('The period (in seconds), which is used to notify internal PMMI admin that some companies are pending review. By default 7 days (604800).'),
      '#required' => TRUE,
    ];
    $form['submission_alert'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Submission alert message'),
      '#default_value' => $config->get('submission_alert'),
      '#description' => $this->t('Alert message to notify user that submission was submitted successfully.'),
    ];

    $form['email_settings_send'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Messages sent by internal PMMI admin:'),
    ];

    // Listing has been approval.
    $form['listing_approve'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Approval email'),
      '#group' => 'email_settings_send',
    ];
    $form['listing_approve']['la_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('listing_approve.subject'),
      '#required' => TRUE,
    ];
    $form['listing_approve']['la_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('listing_approve.body'),
      '#required' => TRUE,
    ];

    // Listing has been rejected.
    $form['listing_reject'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Rejection email'),
      '#group' => 'email_settings_send',
    ];
    $form['listing_reject']['lr_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('listing_reject.subject'),
      '#required' => TRUE,
    ];
    $form['listing_reject']['lr_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('listing_reject.body'),
      '#required' => TRUE,
    ];

    // Self-service update your listing.
    $form['ss_update'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Mass email'),
      '#group' => 'email_settings_send',
    ];
    $form['ss_update']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('ss_update.subject'),
      '#required' => TRUE,
    ];
    $form['ss_update']['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('ss_update.body'),
      '#required' => TRUE,
    ];

    // Self-service update your listing (reminder).
    $form['ss_update_reminder'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Mass email (reminder)'),
      '#group' => 'email_settings_send',
    ];
    $form['ss_update_reminder']['subject_reminder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('ss_update_reminder.subject'),
      '#required' => TRUE,
    ];
    $form['ss_update_reminder']['body_reminder'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('ss_update_reminder.body'),
      '#required' => TRUE,
    ];

    $form['one_time_update'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('One-time company update'),
      '#group' => 'email_settings_send',
    ];
    $form['one_time_update']['one_time_expiration'] = [
      '#type' => 'number',
      '#title' => $this->t('Link expiration time'),
      '#default_value' => $config->get('one_time_expiration'),
      '#required' => TRUE,
      '#min' => 1,
      '#field_suffix' => $this->t('sec'),
    ];
    $form['one_time_update']['one_time_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('one_time.subject'),
      '#required' => TRUE,
    ];
    $form['one_time_update']['one_time_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('one_time.body'),
      '#required' => TRUE,
    ];
    $form['one_time_update']['one_time_alert'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Message displayed after a one-time update link has been emailed.'),
      '#default_value' => $config->get('one_time_alert'),
    ];
    $form['one_time_update']['one_time_alert_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#default_value' => $config->get('one_time_alert_message'),
      '#states' => [
        'visible' => [
          ':input[name="one_time_alert"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['one_time_update']['one_time_wrong_mail_alert'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show message if there is no primary contact email.'),
      '#default_value' => $config->get('one_time_wrong_mail_alert'),
    ];
    $form['one_time_update']['one_time_wrong_mail_alert_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message displayed if there is no primary contact email.'),
      '#default_value' => $config->get('one_time_wrong_mail_alert_message'),
      '#states' => [
        'visible' => [
          ':input[name="one_time_wrong_mail_alert"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['email_settings_receive'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Messages received by internal PMMI admin:'),
    ];

    // New listing has been created (to internal PMMI admin).
    $form['listing_create'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Created email'),
      '#description' => $this->t('Wording of e-mail to internal PMMI admin that a new listing has been created'),
      '#group' => 'email_settings_receive',
    ];
    $form['listing_create']['lc_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('listing_create.subject'),
      '#required' => TRUE,
    ];
    $form['listing_create']['lc_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('listing_create.body'),
      '#required' => TRUE,
    ];

    // New listing is pending review (to internal PMMI admin).
    $form['listing_review'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Pending review email'),
      '#description' => $this->t('Wording of e-mail to internal PMMI admin that a listing is pending review'),
      '#group' => 'email_settings_receive',
    ];
    $form['listing_review']['lrw_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('listing_review.subject'),
      '#required' => TRUE,
    ];
    $form['listing_review']['lrw_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('listing_review.body'),
      '#required' => TRUE,
    ];

    // Add the token tree UI.
    $details = [
      'listing_reject',
      'listing_approve',
      'ss_update',
      'ss_update_reminder',
      'listing_create',
      'listing_review',
      'one_time_update',
    ];

    foreach ($details as $detail) {
      $form[$detail]['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['node'],
        '#show_restricted' => TRUE,
        '#show_nested' => FALSE,
        '#weight' => 90,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()->getEditable('pmmi_sales_agent.mail_settings')
      ->set('mail_notification_address', $form_state->getValue('mail_notification_address'))
      ->set('remind_period', $form_state->getValue('remind_period'))
      ->set('submission_alert', $form_state->getValue('submission_alert'))
      ->set('ss_update.subject', $form_state->getValue('subject'))
      ->set('ss_update.body', $form_state->getValue('body'))
      ->set('ss_update_reminder.subject', $form_state->getValue('subject_reminder'))
      ->set('ss_update_reminder.body', $form_state->getValue('body_reminder'))
      ->set('listing_approve.subject', $form_state->getValue('la_subject'))
      ->set('listing_approve.body', $form_state->getValue('la_body'))
      ->set('listing_reject.subject', $form_state->getValue('lr_subject'))
      ->set('listing_reject.body', $form_state->getValue('lr_body'))
      ->set('listing_create.subject', $form_state->getValue('lc_subject'))
      ->set('listing_create.body', $form_state->getValue('lc_body'))
      ->set('listing_review.subject', $form_state->getValue('lrw_subject'))
      ->set('listing_review.body', $form_state->getValue('lrw_body'))
      ->set('one_time_expiration', $form_state->getValue('one_time_expiration'))
      ->set('one_time.subject', $form_state->getValue('one_time_subject'))
      ->set('one_time.body', $form_state->getValue('one_time_body'))
      ->set('one_time_alert', $form_state->getValue('one_time_alert'))
      ->set('one_time_alert_message', $form_state->getValue('one_time_alert_message'))
      ->set('one_time_wrong_mail_alert', $form_state->getValue('one_time_wrong_mail_alert'))
      ->set('one_time_wrong_mail_alert_message', $form_state->getValue('one_time_wrong_mail_alert_message'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
