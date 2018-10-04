<?php

/**
 * @file
 * Contains \Drupal\pmmi_forms\Plugin\Block\EntityForm.
 */

namespace Drupal\pmmi_forms\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a block to show a form of specific entity.
 *
 * @Block(
 *   id = "entity_form",
 *   category = @Translation("Forms"),
 *   deriver = "Drupal\pmmi_forms\Plugin\Deriver\EntityFormDeriver",
 * )
 */
class EntityForm extends BlockBase implements ContainerFactoryPluginInterface {

  protected $entityManager;

  protected $entityFormBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager, EntityFormBuilderInterface $entity_form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var $entity \Drupal\Core\Entity\EntityInterface */
    $entity = $this->getContextValue('entity');

    if ($entity->getEntityTypeId() == 'node_type') {
      $entity = $this->entityManager->getStorage('node')->create(array(
        'type' => $entity->id(),
      ));
    }
    elseif ($entity->getEntityTypeId() == 'node') {
      // Use the latest node revision if 'content_moderation' is used.
      if (\Drupal::service('module_handler')->moduleExists('content_moderation')) {
        $moderation_info = \Drupal::service('content_moderation.moderation_information');
        $last_edit = $moderation_info->getLatestRevision('node', $entity->id());
        if ($entity->getRevisionId() != $last_edit->getRevisionId() && pmmi_sales_agent_is_admin()) {
          $forms = $this->compareForms($entity, $last_edit);
        }
        else {
          $entity = $last_edit;
        }
      }
    }

    $form = $forms ?? $this->entityFormBuilder->getForm($entity);
    return $form;
  }

  /**
   * Prepare edit forms for comparing.
   *
   * @param object $current_entity
   *    Current entity.
   * @param object $last_edit_entity
   *    Last entity revision.
   *
   * @return array
   *    Return renderable array for forms.
   */
  private function compareForms($current_entity, $last_edit_entity) {
    $prev_revision_form = $this->entityFormBuilder->getForm($current_entity);
    unset($prev_revision_form['actions']);
    $forms['#prefix'] = '<div class="compare-forms">';
    $forms['#suffix'] = '</div>';
    $forms['message'] = [
      '#theme' => 'status_messages',
      '#message_list' => [
        'warning' => [
          $this->t('Updates by company user should be approved or rejected.'),
        ],
      ],
    ];
    $forms['forms']['#prefix'] = '<div class="compare-forms-inner">';
    $forms['forms']['#suffix'] = '</div>';
    $forms['forms']['prev_form'] = $prev_revision_form;
    $forms['forms']['prev_form']['#prefix'] = '<div class="previous-revision-form">';
    $forms['forms']['prev_form']['#prefix'] .= '<h4>' . $this->t('Previous revision') . '</h4>';
    $forms['forms']['prev_form']['#suffix'] = '</div>';
    $forms['forms']['current_form'] = $this->entityFormBuilder->getForm($last_edit_entity);
    $forms['forms']['current_form']['#prefix'] = '<div class="current-revision-form">';
    $forms['forms']['current_form']['#prefix'] .= '<h4>' . $this->t('Pending approval') . '</h4>';
    $forms['forms']['current_form']['#suffix'] = '</div>';
    return $forms;
  }

}
