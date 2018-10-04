<?php

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Alter search_page views query for changing Exposed filter.
 *
 * Implements hook_views_query_alter().
 */
function pmmi_forms_views_query_alter(ViewExecutable $view, QueryPluginBase $query) {
  if ($view->id() == 'search_page' && $view->current_display == 'block_1') {
    $where = $query->getWhere();
    $group_id = max(array_keys($where));

    if (isset($group_id) && !empty($where[$group_id]['conditions'])
    && $where[$group_id]['conditions'][0][0] === 'type') {
      $query->setWhereGroup('OR', $group_id);
    }
  }

  if ($view->id() === 'membership_application_submissions' && $view->current_display == 'block_all') {
    if (!$query_params = \Drupal::request()->query->all()) {
      // There are no available filters.
      return;
    }
    if (isset($query_params['date'])) {
      $date_formatter = \Drupal::service('date.formatter');
      $dates = '';
      if ($query_params['date'] != 0) {
        $dates = explode('_', $query_params['date']);
      }
      // Define table.
      $definition = [
        'table' => 'webform_submission_data',
        'field' => 'sid',
        'left_table' => 'webform_submission',
        'left_field' => 'sid',
        'extra' => [
          [
            'field' => 'name',
            'value' => 'submission_date_is_belongs_to',
          ],
        ],
        'extraOperator' => 'AND',
      ];

      // Join table and field with `Submission date is belong to` data.
      $join = Drupal::service('plugin.manager.views.join')
        ->createInstance('standard', $definition);
      $relation = $query->addRelationship('webform_submission_date_is_belongs_to', $join, 'webform_submission');
      $query->addTable('webform_submission_data', $relation, $join, 'webform_submission_date_is_belongs_to');
      $query->addField('webform_submission_date_is_belongs_to', 'value', 'submission_date_is_belongs_to');

      // Get where group ID.
      $where = $query->where;
      $group_id = max(array_keys($where));
      // Get `filtered by` field.
      if ($query_params['date'] != 0) {
        $field = $query->getDateFormat('webform_submission_date_is_belongs_to.value', 'Y-m-d', TRUE);
        // Change format of seasons start and end dates.
        $started = $date_formatter->format($dates[0], 'custom', 'Y-m-d', NULL);
        $ended = $date_formatter->format($dates[1], 'custom', 'Y-m-d', NULL);

        // Create expression.
        $start = $query->getDateFormat("'" . $started  . "'", 'Y-m-d', TRUE);
        $end = $query->getDateFormat("'" . $ended . "'", 'Y-m-d', TRUE);
        $query->addWhereExpression($group_id, "$field BETWEEN $start AND $end");
      }
      elseif ($query_params['date'] == 0) {
        $query->addWhereExpression($group_id, "webform_submission_date_is_belongs_to.value=''");
      }
    }
  }
}
