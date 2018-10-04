<?php

namespace Drupal\pmmi_training_provider\Entity;

use Drupal\pmmi_sales_agent\Entity\SADUserStatType;

/**
 * Defines the Training provider report type entity.
 *
 * @ConfigEntityType(
 *   id = "tpd_report_type",
 *   label = @Translation("Training provider report type"),
 *   handlers = {
 *     "list_builder" = "Drupal\pmmi_training_provider\TPDReportTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\pmmi_training_provider\Form\TPDReportTypeForm",
 *       "edit" = "Drupal\pmmi_training_provider\Form\TPDReportTypeForm",
 *       "delete" = "Drupal\pmmi_sales_agent\Form\SADUserStatTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\pmmi_sales_agent\SADUserStatTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "tpd_report_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "tpd_report",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/tpd_report_type/{tpd_report_type}",
 *     "add-form" = "/admin/structure/tpd_report_type/add",
 *     "edit-form" = "/admin/structure/tpd_report_type/{tpd_report_type}/edit",
 *     "delete-form" = "/admin/structure/tpd_report_type/{tpd_report_type}/delete",
 *     "collection" = "/admin/structure/tpd_report_type"
 *   }
 * )
 */
class TPDReportType extends SADUserStatType {
}
