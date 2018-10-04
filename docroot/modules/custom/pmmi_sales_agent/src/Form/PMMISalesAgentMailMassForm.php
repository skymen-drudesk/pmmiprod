<?php

namespace Drupal\pmmi_sales_agent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PMMISalesAgentMailMassForm.
 *
 * @package Drupal\pmmi_sales_agent\Form
 */
class PMMISalesAgentMailMassForm extends ConfigFormBase {

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
    return 'pmmi_sales_agent_mail_mass_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('pmmi_sales_agent.mail_mass_settings');
    $last_run = $config->get('last_run');

    // PMMI Mass Email Settings.
    $form['mail_mass_settings'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Mass email settings'),
      '#description' => $this->t('The last time mass email was run: <strong>@date</strong>', [
        '@date' => !empty($last_run) ? date('Y-m-d H:i:s', $last_run) : '-/-'
      ]),
    ];
    $form['mail_mass_settings']['filter'] = [
      '#type' => 'radios',
      '#title' => $this->t('Send an email to:'),
      '#default_value' => $config->get('filter'),
      '#options' => [
        'all' => $this->t('All companies'),
        '- 6 months' => $this->t("Who haven't updated in 6 months"),
      ],
    ];
    $form['mail_mass_settings']['remind_period'] = [
      '#type' => 'number',
      '#title' => $this->t('Remind period'),
      '#default_value' => $config->get('remind_period'),
      '#description' => $this->t('The period (in seconds), which is used to send 3 drip emails, if no response. By default 5 days (432000).'),
      '#required' => TRUE,
    ];

    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings only'),
    ];
    $form['save_send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings and send mass email'),
    ];

    // Hide default submit button.
    $form['actions']['submit']['#access'] = FALSE;

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
    parent::submitForm($form, $form_state);

    // Save config changes.
    $this->configFactory()->getEditable('pmmi_sales_agent.mail_mass_settings')
      ->set('filter', $form_state->getValue('filter'))
      ->set('remind_period', $form_state->getValue('remind_period'))
      ->save();

    $triggering = $form_state->getTriggeringElement();
    if ($triggering['#id'] == 'edit-save-send') {
      // Redirect to the confirmation form.
      $url = \Drupal\Core\Url::fromRoute('pmmi_sales_agent.mass_email_confirm');
      $form_state->setRedirectUrl($url);
    }
  }
}
