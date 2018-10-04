<?php

namespace Drupal\pmmi_sales_agent\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SADUserStatTypeForm.
 *
 * @package Drupal\pmmi_sales_agent\Form
 */
class SADUserStatTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $sad_user_stat_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $sad_user_stat_type->label(),
      '#description' => $this->t("Label for the Sales agent user stat type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $sad_user_stat_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\pmmi_sales_agent\Entity\SADUserStatType::load',
      ],
      '#disabled' => !$sad_user_stat_type->isNew(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $sad_user_stat_type = $this->entity;
    $status = $sad_user_stat_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Sales agent user stat type.', [
          '%label' => $sad_user_stat_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Sales agent user stat type.', [
          '%label' => $sad_user_stat_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($sad_user_stat_type->toUrl('collection'));
  }

}
