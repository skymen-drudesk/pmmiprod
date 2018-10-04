<?php

namespace Drupal\pmmi_sales_agent\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * A handler to provide proper displays for dates.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("created_from_unixtime")
 */
class CreatedFromUnixtime extends FieldPluginBase {
  /**
   * {@inheritdoc}
   */
  public function query($use_groupby = FALSE) {
    $this->ensureMyTable();

    $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
    $formula = "FROM_UNIXTIME($this->tableAlias.$this->realField, '%M %Y')";
    $this->field_alias = $this->query->addField(NULL, $formula, "{$this->tableAlias}_{$this->realField}", $params);

    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->sanitizeValue($value);
  }
}
