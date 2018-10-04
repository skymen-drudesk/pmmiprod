<?php

namespace Drupal\pmmi_sales_agent\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\pmmi_sales_agent\SADDownloadsQuotaInterface;

/**
 * Defines the SAD Downloads Quota entity.
 *
 * @ConfigEntityType(
 *   id = "sad_downloads_quota",
 *   label = @Translation("Downloads quota"),
 *   handlers = {
 *     "list_builder" = "Drupal\pmmi_sales_agent\SADDownloadsQuotaListBuilder",
 *     "form" = {
 *       "default" = "Drupal\pmmi_sales_agent\Form\SADDownloadsQuotaEditForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "pmmi sales agent administration",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   config_export = {
 *     "id",
 *     "quota",
 *   },
 *   links = {
 *     "collection" = "/admin/config/sad/report-settings/downloads-quota",
 *     "edit-form" = "/admin/config/sad/report-settings/downloads-quota/{sad_downloads_quota}",
 *     "delete-form" = "/admin/config/sad/report-settings/downloads-quota/{sad_downloads_quota}/delete"
 *   }
 * )
 */
class SADDownloadsQuota extends ConfigEntityBase implements SADDownloadsQuotaInterface {

  /**
   * The Downloads quota ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The quota value.
   *
   * @var string
   */
  protected $quota;

  /**
   * {@inheritdoc}
   */
  public function getQuota() {
    return $this->quota;
  }

  /**
   * {@inheritdoc}
   */
  public function setQuota($quota) {
    $this->quota = $quota;
    return $this;
  }

  /**
   * Get user which is related to quota entity.
   */
  public function getUser() {
    $id = $this->id();
    return $id ? \Drupal\user\Entity\User::load($id) : NULL;
  }
}
