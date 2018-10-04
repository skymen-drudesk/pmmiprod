<?php

namespace Drupal\pmmi_psdata\Plugin\QueueWorker;

/**
 * Updates a Personify company data.
 *
 * @QueueWorker(
 *   id = "pmmi_psdata_company_real",
 *   title = @Translation("Update Company Staff Data (Real Time)"),
 * )
 */
class PMMICompanyRealQueue extends PMMICompanyQueue {

}
