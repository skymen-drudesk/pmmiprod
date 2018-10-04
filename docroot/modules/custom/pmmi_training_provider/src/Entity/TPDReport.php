<?php

namespace Drupal\pmmi_training_provider\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\pmmi_sales_agent\Entity\SADUserStat;

/**
 * Defines the training provider report entity.
 *
 * @ingroup pmmi_training_provider
 *
 * @ContentEntityType(
 *   id = "tpd_report",
 *   label = @Translation("Training provider report"),
 *   bundle_label = @Translation("Training provider report type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\pmmi_sales_agent\SADUserStatListBuilder",
 *     "views_data" = "Drupal\pmmi_sales_agent\Entity\SADUserStatViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\pmmi_training_provider\Form\TPDReportForm",
 *       "add" = "Drupal\pmmi_training_provider\Form\TPDReportForm",
 *       "edit" = "Drupal\pmmi_training_provider\Form\TPDReportForm",
 *       "delete" = "Drupal\pmmi_sales_agent\Form\SADUserStatDeleteForm",
 *     },
 *     "access" = "Drupal\pmmi_training_provider\TPDReportAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\pmmi_training_provider\TPDReportHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "tpd_report",
 *   admin_permission = "administer training provider report entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/tpd_report/{tpd_report}",
 *     "add-page" = "/admin/structure/tpd_report/add",
 *     "add-form" = "/admin/structure/tpd_report/add/{tpd_report_type}",
 *     "edit-form" = "/admin/structure/tpd_report/{tpd_report}/edit",
 *     "delete-form" = "/admin/structure/tpd_report/{tpd_report}/delete",
 *     "collection" = "/admin/structure/tpd_report",
 *   },
 *   bundle_entity_type = "tpd_report_type",
 *   field_ui_base_route = "entity.tpd_report_type.edit_form"
 * )
 */
class TPDReport extends SADUserStat {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id']->setDescription(t('The user ID of author of the Training provider report entity.'));

    return $fields;
  }

}
