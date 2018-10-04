<?php

namespace Drupal\pmmi_sales_agent\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Sales agent user stat type entity.
 *
 * @ConfigEntityType(
 *   id = "sad_user_stat_type",
 *   label = @Translation("Sales agent user stat type"),
 *   handlers = {
 *     "list_builder" = "Drupal\pmmi_sales_agent\SADUserStatTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\pmmi_sales_agent\Form\SADUserStatTypeForm",
 *       "edit" = "Drupal\pmmi_sales_agent\Form\SADUserStatTypeForm",
 *       "delete" = "Drupal\pmmi_sales_agent\Form\SADUserStatTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\pmmi_sales_agent\SADUserStatTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "sad_user_stat_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "sad_user_stat",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/sad_user_stat_type/{sad_user_stat_type}",
 *     "add-form" = "/admin/structure/sad_user_stat_type/add",
 *     "edit-form" = "/admin/structure/sad_user_stat_type/{sad_user_stat_type}/edit",
 *     "delete-form" = "/admin/structure/sad_user_stat_type/{sad_user_stat_type}/delete",
 *     "collection" = "/admin/structure/sad_user_stat_type"
 *   }
 * )
 */
class SADUserStatType extends ConfigEntityBundleBase implements SADUserStatTypeInterface {

  /**
   * The Sales agent user stat type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Sales agent user stat type label.
   *
   * @var string
   */
  protected $label;

}
