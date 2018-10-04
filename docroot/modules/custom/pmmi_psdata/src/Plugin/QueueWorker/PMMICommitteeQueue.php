<?php

namespace Drupal\pmmi_psdata\Plugin\QueueWorker;

/**
 * Updates Personify Committee data.
 *
 * @QueueWorker(
 *   id = "pmmi_psdata_committee",
 *   title = @Translation("Update Committee pages"),
 *   cron = {"time" = 60}
 * )
 */
class PMMICommitteeQueue extends PMMIBaseDataQueue {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    $cid = $this->provider . ':' . $item->type . '_' . $item->id;
    $data = $this->getCommitteeData($item->id);
    if ($data) {
      $this->dataCollector->invalidateTags([$cid]);
      $expiration_time = $item->expiration > 0 ? REQUEST_TIME + $item->expiration : $item->expiration;
      $this->cache->set($cid, $data, $expiration_time);
    }
  }

  /**
   * Get Committee Data.
   *
   * @param string $id
   *   The ID of requested committee.
   *
   * @return array
   *   The array of data.
   */
  protected function getCommitteeData($id) {
    $data = NULL;
    $date = new \DateTime();
    $query = [
      '$filter' => "CommitteeMasterCustomer eq '$id' and " .
        "ParticipationStatusCodeString eq 'ACTIVE' and EndDate ge datetime'" .
        $date->format('Y-m-d') . "'",
    ];
    $request_options = $this->requestHelper->buildGetRequest('CommitteeMembers', $query);
    $members = [];
    if ($committee_data = $this->requestHelper->handleRequest($request_options)) {
      foreach ($committee_data as $customer) {
        $last_first_name = $customer->CommitteeMemberLastFirstName;
        $member_id = $customer->MemberMasterCustomer;
        $position = $customer->PositionCodeDescriptionDisplay;
        $data[$position][$last_first_name] = [
          'label_name' => $customer->CommitteeMemberLabelName,
          'end_date' => $this->requestHelper->formatDate($customer->EndDate, 'Y'),
          'member_id' => $member_id,
          'company_id' => $customer->RepresentingMasterCustomer,
          'company_name' => $customer->RepresentingLabelName,
        ];
        $members[$member_id] = [
          'position' => $position,
          'label_name' => $last_first_name,
        ];
      }
      $job_title_requests = $this->separateRequest(array_keys($members), 'job_title');
      if ($job_title_data = $this->requestHelper->handleAsyncRequests($job_title_requests)) {
        foreach ($job_title_data as $member_data) {
          $position = $members[$member_data->MasterCustomerId]['position'];
          $label_name = $members[$member_data->MasterCustomerId]['label_name'];
          $data[$position][$label_name]['job_title'] = $member_data->JobTitle;
        }
      }
      $this->sort($data);
    }
    else {
      $this->requestHelper->ssoHelper->log("Error with request to get Data Service Committee Members.");
    }
    return $data;
  }

}
