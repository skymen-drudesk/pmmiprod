<?php

namespace Drupal\pmmi_psdata\Plugin\QueueWorker;

/**
 * Updates a staff data.
 *
 * @QueueWorker(
 *   id = "pmmi_psdata_staff",
 *   title = @Translation("Update Staff Data"),
 *   cron = {"time" = 60}
 * )
 */
class PMMIStaffQueue extends PMMIBaseDataQueue {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    $cid = $this->dataCollector->buildCid($item, 'staff');
    $data = $this->getStaffData($item);
    if ($data) {
      $this->dataCollector->invalidateTags([$cid]);
      $expiration_time = REQUEST_TIME + $item->data['staff']['expiration'];
      $this->cache->set($cid, $data, $expiration_time);
    }
  }

  /**
   * Get Company Staff Data.
   *
   * @param object $item
   *   An object representing the current block settings.
   *
   * @return array
   *   The array of data.
   */
  protected function getStaffData($item) {
    $data = NULL;
    $method = $item->data['company']['method'];
    $request_options = $this->buildStaffRequest($item, $method);
    if ($staff_data = $this->requestHelper->handleRequest($request_options)) {
      foreach ($staff_data as $customer) {
        $label_name = '';
        $first_name = '';
        $last_name = '';
        if ($method == 'code') {
          $member_id = $customer->MasterCustomerId;
          $member_sub_id = $customer->SubCustomerId;
          $label_name = $customer->LabelName;
          $first_name = $customer->FirstName;
          $last_name = $customer->LastName;
        }
        else {
          $member_id = $customer->RelatedMasterCustomerId;
          $member_sub_id = $customer->RelatedSubCustomerId;
        }
        $data[$member_id] = [
          'id' => $member_id,
          'sub_id' => $member_sub_id,
          'label_name' => $label_name,
          'first_name' => $first_name,
          'last_name' => $last_name,
        ];
      }
      if ($method != 'code') {
        $members_requests = $this->separateRequest(array_keys($data), 'info');
        if ($members_data = $this->requestHelper->handleAsyncRequests($members_requests)) {
          foreach ($members_data as $member) {
            $data[$member->MasterCustomerId]['label_name'] = $member->LabelName;
            $data[$member->MasterCustomerId]['first_name'] = $member->FirstName;
            $data[$member->MasterCustomerId]['last_name'] = $member->LastName;
          }
        }
      }
      $address_requests = $this->separateRequest(array_keys($data), 'address');
      if ($address_data = $this->requestHelper->handleAsyncRequests($address_requests)) {
        foreach ($address_data as $address) {
          $data[$address->MasterCustomerId]['country'] = $address->CountryCode;
          $data[$address->MasterCustomerId]['job_title'] = $address->JobTitle;
        }
      }
      $company_sec_comm = array_map('strtoupper', $item->data['company']['comm_empl']);
      $staff_sec_comm = array_map('strtoupper', $item->data['staff']['comm_empl']);
      $communications = array_unique(array_merge($company_sec_comm, $staff_sec_comm));
      $comm_requests = $this->separateRequest(
        array_keys($data),
        'communication',
        $communications
      );
      if ($comm_data = $this->requestHelper->handleAsyncRequests($comm_requests)) {
        foreach ($comm_data as $comm) {
          $type = strtolower($comm->CommTypeCodeString);
          $data[$comm->MasterCustomerId]['comm'][$type] = $comm->FormattedPhoneAddress;
        }
      }
    }
    else {
      $this->requestHelper->ssoHelper->log("Error with request to get Data Service information about Employees.");
    }
    return $data;
  }

  /**
   * Build Company Staff request.
   *
   * @param object $item
   *   An object representing the current block settings.
   * @param string $method
   *   Setting - Type of method.
   *
   * @return array
   *   The request options array.
   */
  private function buildStaffRequest($item, $method) {
    $company_id = $item->data['company']['id'];
    $company_sub_id = $item->data['company']['sub_id'];
    switch ($method) {
      // By CustomerClassCode.
      case 'code':
        // /CustomerInfos?$filter=CustomerClassCode eq 'STAFF' .
        $path = 'CustomerInfos';
        $filter = $this->requestHelper->addFilter(
          'eq',
          'CustomerClassCode',
          [$item->data['company']['method_data']],
          TRUE
        );
        $query = [
          '$filter' => $filter,
          '$select' => 'MasterCustomerId,SubCustomerId,FirstName,LastName,LabelName',
        ];
        break;

      // By CustomerInfos\Relationship.
      case 'ci_rel':
        // /CustomerInfos(MasterCustomerId='02010445',SubCustomerId=0)
        // /Relationships?$filter=ActiveFlag  eq true and FullTimeFlag eq true .
        $path = "CustomerInfos(MasterCustomerId='$company_id',SubCustomerId=$company_sub_id)/Relationships";
        $filter = $this->requestHelper->addFilter('eq', 'ActiveFlag', ['true'], TRUE, TRUE);
        $filter .= $this->requestHelper->addFilter('eq', 'FullTimeFlag', ['true'], FALSE, TRUE);
        $query = [
          '$filter' => $filter,
        ];
        break;
    }
    return $this->requestHelper->buildGetRequest($path, $query);
  }

}
