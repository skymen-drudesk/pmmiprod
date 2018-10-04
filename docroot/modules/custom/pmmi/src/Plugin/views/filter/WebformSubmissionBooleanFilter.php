<?php

namespace Drupal\pmmi\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Boolean filter based on value of a webform submission.
 *
 * @ViewsFilter("webform_submission_boolean_filter")
 */
class WebformSubmissionBooleanFilter extends BooleanOperator {}
