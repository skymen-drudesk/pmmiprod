<?php

namespace Drupal\pmmi_sso\EventSubscriber;

use Drupal\pmmi_sso\Event\PMMISSOPreRegisterEvent;
use Drupal\pmmi_sso\Exception\PMMISSOLoginException;
use Drupal\pmmi_sso\Exception\PMMISSOServiceException;
use Drupal\pmmi_sso\Parsers\PMMISSOXmlParser;
use Drupal\pmmi_sso\Service\PMMISSOHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a PMMISSOGetUserDataSubscriber.
 */
class PMMISSOGetUserDataSubscriber implements EventSubscriberInterface {


  /**
   * Stores the Guzzle HTTP client used when validating service tickets.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Stores PMMISSO helper.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSOHelper
   */
  protected $ssoHelper;

  /**
   * Stores PMMISSOXML parser.
   *
   * @var \Drupal\pmmi_sso\Parsers\PMMISSOXmlParser
   */
  protected $parser;

  /**
   * PMMISSOAutoAssignRoleSubscriber constructor.
   *
   * @param Client $http_client
   *   The HTTP Client library.
   * @param PMMISSOHelper $sso_helper
   *   The PMMI SSO Helper service.
   * @param PMMISSOXmlParser $parser
   *   The PMMI SSO XML parser service.
   */
  public function __construct(
    Client $http_client,
    PMMISSOHelper $sso_helper,
    PMMISSOXmlParser $parser
  ) {
    $this->httpClient = $http_client;
    $this->ssoHelper = $sso_helper;
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PMMISSOHelper::EVENT_PRE_REGISTER][] = ['getSsoData', 100];
    $events[PMMISSOHelper::EVENT_PRE_REGISTER][] = ['getServiceData', 99];
    $events[PMMISSOHelper::EVENT_PRE_REGISTER][] = ['getImsRole', 98];
    $events[PMMISSOHelper::EVENT_PRE_REGISTER][] = ['checkDataServiceRole', 97];

    return $events;
  }

  /**
   * The entry point for our subscriber.
   *
   * Get User Data (SSO Service) that just registered via PMMI SSO.
   *
   * @param PMMISSOPreRegisterEvent $event
   *   The event object.
   */
  public function getSsoData(PMMISSOPreRegisterEvent $event) {
    $raw_user_id = $event->getSsoPropertyBag()->getRawUserId();
    $request_options = $this->ssoHelper->buildSsoServiceQuery(
      'SSOCustomerGet',
      ['vu', 'vp'],
      ['TIMSSCustomerId' => $raw_user_id]
    );
    $request_options['method'] = 'POST';
    $response = $this->handleRequest($request_options);
    if ($response instanceof RequestException) {
      $event->setAllowAutomaticRegistration(FALSE);
      $this->ssoHelper->log("Error with request to get User Data: " . $response->getMessage());
      return;
    }
    $this->parser->setData($response);
    // Check if user exists and is active.
    if ($this->parser->validateBool('//m:UserExists') && !$this->parser->validateBool('//m:DisableAccountFlag')) {
      // Parse and set user name.
      if ($username = $this->parser->getSingleValue('//m:UserName')) {
        $event->setDrupalUsername($username);
        $event->setPropertyValue('init', $username);
      }
      else {
        $event->setAllowAutomaticRegistration(FALSE);
        $this->ssoHelper->log('User name does not exist or is disabled.');
        return;
      }
      // Parse and set user email.
      if ($email = $this->parser->getSingleValue('//m:Email')) {
        $event->setPropertyValue('mail', $email);
      }
    }
    else {
      $event->setAllowAutomaticRegistration(FALSE);
      $this->ssoHelper->log('User name does not exist or is disabled.');
      return;
    }
  }

  /**
   * Get User Data (Data Service) that just registered via PMMI SSO.
   *
   * @param PMMISSOPreRegisterEvent $event
   *   The event object.
   *
   * @throws PMMISSOLoginException
   *   Thrown if there was a problem with login.
   * @throws PMMISSOServiceException
   *   Thrown if there was a problem with request.
   */
  public function getServiceData(PMMISSOPreRegisterEvent $event) {
    $user_id = $event->getSsoPropertyBag()->getUserId();
    $request_options = $this->ssoHelper->buildDataServiceQuery(
      'CustomerInfos',
      ['$filter' => "MasterCustomerId eq '$user_id'"]
    );
    $request_options['method'] = 'GET';
    $response = $this->handleRequest($request_options);
    if ($response instanceof RequestException) {
      $event->setAllowAutomaticRegistration(FALSE);
      $this->ssoHelper->log("Error with request to get User Data: " . $response->getMessage());
      return;
    }
    // Check if user data exists.
    if ($json_data = json_decode($response)) {
      $data = $json_data->d[0];
      // Parse and set user LabelName.
      if ($label_name = $data->LabelName) {
        $event->setPropertyValue('name', $label_name);
      }
      else {
        $event->setAllowAutomaticRegistration(FALSE);
        $this->ssoHelper->log('User name does not exist or is disabled.');
        return;
      }
      // Parse and set user FirstName.
      if ($first_name = $data->FirstName) {
        $event->setPropertyValue('field_first_name', $first_name);
      }
      // Parse and set user LastName.
      if ($last_name = $data->LastName) {
        $event->setPropertyValue('field_last_name', $last_name);
      }
    }
    else {
      $event->setAllowAutomaticRegistration(FALSE);
      $this->ssoHelper->log('Invalid response from SSO Service.');
      return;
    }
  }

  /**
   * Get user roles (IM Service) that just registered via PMMI SSO.
   *
   * @param PMMISSOPreRegisterEvent $event
   *   The event object.
   *
   * @throws PMMISSOLoginException
   *   Thrown if there was a problem with login.
   * @throws PMMISSOServiceException
   *   Thrown if there was a problem with request.
   */
  public function getImsRole(PMMISSOPreRegisterEvent $event) {
    // Get the IMS roles that are allowed to register.
    $ims_role_mapping = array_map('strtolower', $this->ssoHelper->getAllowedRoles(PMMISSOHelper::IMS));
    if (empty($ims_role_mapping)) {
      return;
    }
    $raw_user_id = $event->getSsoPropertyBag()->getRawUserId();
    $request_options = $this->ssoHelper->buildSsoServiceQuery(
      'IMSCustomerRoleGetByTimssCustomerId',
      ['vu', 'vp'],
      ['TIMSSCustomerId' => $raw_user_id],
      TRUE
    );
    $request_options['method'] = 'POST';
    $response = $this->handleRequest($request_options);
    if ($response instanceof RequestException) {
      $this->ssoHelper->log("Error with request to get IMS User Roles: " . $response->getMessage());
      return;
    }
    $this->parser->setData($response);
    // Check if user has IMS Role.
    if ($this->parser->getNodeList('//m:CustomerRoles')->length > 0) {
      // Parse existing user Roles.
      $roles = array_map('strtolower', $this->parser->getMultipleValues('//m:CustomerRoles/m:Role/m:Value'));
      $exist_roles = array_intersect($ims_role_mapping, $roles);
      if (count($exist_roles) > 0) {
        $user_roles = $this->ssoHelper->filterAllowedRoles(PMMISSOHelper::IMS, $exist_roles);
        $event->setDrupalRoles($user_roles);
      }
      else {
        $this->ssoHelper->log('User does not have allowed IMS Role.');
      }
    }
  }

  /**
   * Check if the user is on the committee.
   *
   * @param PMMISSOPreRegisterEvent $event
   *   The event object.
   *
   * @throws PMMISSOLoginException
   *   Thrown if there was a problem with login.
   * @throws PMMISSOServiceException
   *   Thrown if there was a problem with request.
   */
  public function checkDataServiceRole(PMMISSOPreRegisterEvent $event) {
    // Get the Data Service committee_id that are allowed to register.
    $role_id_mapping = $this->ssoHelper->getAllowedRoles(PMMISSOHelper::DATA);
    if (empty($role_id_mapping) && empty($event->getDrupalRoles())) {
      $event->setAllowAutomaticRegistration(FALSE);
      $this->ssoHelper->log("User does not have any allowed roles.");
      return;
    }
    $user_id = $event->getSsoPropertyBag()->getUserId();
    foreach ($role_id_mapping as $committee_id) {
      $query = [
        '$filter' => 'MemberMasterCustomer eq \'' . $user_id . '\' and ' .
        'CommitteeMasterCustomer eq \'' . $committee_id . '\' and ' .
        'ParticipationStatusCodeString eq \'ACTIVE\'',
      ];
      $request_options = $this->ssoHelper->buildDataServiceQuery(
        'CommitteeMembers',
        $query
      );
      $request_options['method'] = 'GET';
      $response = $this->handleRequest($request_options);
      if ($response instanceof RequestException) {
        $this->ssoHelper->log("Error with request to check Data Service User role: " . $response->getMessage());
        return;
      }
      elseif ($json_data = json_decode($response)) {
        $data = $json_data->d;
        if (!empty($data) && $data[0]->CommitteeMasterCustomer == $committee_id) {
          $roles[] = $committee_id;
        }
        else {
          $this->ssoHelper->log('User does not have allowed Data Service Role.');
        }
      }
      else {
        $this->ssoHelper->log('Wrong response from Data Service.');
      }
    }
    if (!empty($roles)) {
      $roles = $this->ssoHelper->filterAllowedRoles(PMMISSOHelper::DATA, $roles);
      $event->setDrupalRoles($roles);
    }
    $roles_to_register = $event->getDrupalRoles();
    if (!empty($roles_to_register)) {
      $event->setPropertyValue('roles', $roles_to_register);
    }
    else {
      $event->setAllowAutomaticRegistration(FALSE);
      $this->ssoHelper->log('User does not have any allowed roles.');
      return;
    }
  }

  /**
   * Attempt to handle request to PMMI Personify Services.
   *
   * @param array $request_param
   *   Parameters of the request.
   *
   * @return string|RequestException
   *   The response data from PMMI Personify Services.
   */
  protected function handleRequest(array $request_param) {
    $method = $request_param['method'];
    $uri = $request_param['uri'];
    if ($method == 'POST') {
      $options = ['form_params' => $request_param['params']];
    }
    else {
      $options = $request_param['params'];
    }
    $options['timeout'] = 30;

    try {
      $response = $this->httpClient->request($method, $uri, $options);
      $response_data = $response->getBody()->getContents();
      $this->ssoHelper->log("User Data received from PMMI Personify server: " . htmlspecialchars($response_data));
    }
    catch (RequestException $e) {
      return $e;
    }
    return $response_data;
  }

}
