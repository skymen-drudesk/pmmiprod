<?php

namespace Drupal\audience_select\Form;

use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Defines a confirmation form for deleting a Audience.
 */
class AudienceDeleteForm extends ConfirmFormBase {
  use ConfigFormBaseTrait;

  /**
   * The Audience ID.
   *
   * @var string
   */
  protected $audienceId;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['audience_select.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %audience_id?', ['%audience_id' => $this->audienceId]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('audience_select.audience_settings_form');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'audience_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $audience_id = NULL) {
    $this->audienceId = $audience_id;

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('audience_select.settings');
    $deleted_audience = $config->get('map.' . $this->audienceId);
    $config->clear('map.' . $this->audienceId);
    $config->save();
    if (!empty($deleted_audience['audience_image'])) {
      $image = File::load($deleted_audience['audience_image'][0]);
      if (!empty($image)) {
        /** @var \Drupal\file\FileUsage\DatabaseFileUsageBackend $file_usage */
        $file_usage = \Drupal::service('file.usage');
        $usages = $file_usage->listUsage($image);
        if (count($usages) == 1 && array_key_exists('audience_select', $usages)) {
          $image->delete();
        }
      }
    }
    $args = [
      '%audience_id' => $this->audienceId,
    ];

    $this->logger('Audience')
      ->notice('The %audience_id has been deleted.', $args);

    drupal_set_message($this->t('The %audience_id has been deleted.', $args));

    $form_state->setRedirect('audience_select.audience_settings_form');
  }

}
