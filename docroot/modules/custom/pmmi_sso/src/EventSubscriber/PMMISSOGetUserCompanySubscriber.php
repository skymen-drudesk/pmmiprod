<?php

namespace Drupal\pmmi_sso\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\pmmi_sso\Event\PMMISSOPreLoginEvent;
use Drupal\pmmi_sso\Event\PMMISSOPreRegisterEvent;
use Drupal\pmmi_sso\Exception\PMMISSOLoginException;
use Drupal\pmmi_sso\Exception\PMMISSOServiceException;
use Drupal\pmmi_sso\Parsers\PMMISSOXmlParser;
use Drupal\pmmi_sso\Service\PMMISSOHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a PMMISSOGetUserCompanySubscriber.
 */
class PMMISSOGetUserCompanySubscriber implements EventSubscriberInterface {


  /**
   * Stores the Guzzle HTTP client used when validating service tickets.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;


  /**
   * The PMMI SSO token Storage.
   *
   * @var \Drupal\pmmi_sso\PMMIPersonifyCompanyStorageInterface
   */
  protected $companyStorage;

  /**
   * Stores PMMISSO helper.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSOHelper
   */
  protected $ssoHelper;

  /**
   * PMMISSOAutoAssignRoleSubscriber constructor.
   *
   * @param Client $http_client
   *   The HTTP Client library.
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param PMMISSOHelper $sso_helper
   *   The PMMI SSO Helper service.
   */
  public function __construct(Client $http_client, EntityTypeManagerInterface $entityTypeManager, PMMISSOHelper $sso_helper) {
    $this->httpClient = $http_client;
    $this->companyStorage = $entityTypeManager->getStorage('pmmi_personify_company');
    $this->ssoHelper = $sso_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PMMISSOHelper::EVENT_PRE_LOGIN][] = ['getUserCompanies', 100];
    return $events;
  }

  /**
   * Get user related company.
   *
   * @param PMMISSOPreLoginEvent $event
   *   The event object.
   *
   * @throws PMMISSOLoginException
   *   Thrown if there was a problem with login.
   * @throws PMMISSOServiceException
   *   Thrown if there was a problem with request.
   */
  public function getUserCompanies(PMMISSOPreLoginEvent $event) {
    $account = $event->getAccount();
    $related_companies = [];
    $date = new \DateTime();
    $event_time = $account->getLastLoginTime() + $this->ssoHelper->getPceDurationTime();
    // Get related company list for the new user.
    if ($event_time < $date->getTimestamp()) {
      $user_id = $event->getSsoPropertyBag()->getUserId();
      $query = [
        '$filter' => 'MasterCustomerId eq \'' . $user_id . '\' and ' .
        'RelationshipCode eq \'Employee\' and RelationshipType eq' .
        ' \'Employment\' and (EndDate ge datetime\'' . $date->format('Y-m-d') .
        '\' or EndDate eq null)',
      ];
      $request_options = $this->ssoHelper->buildDataServiceQuery(
        'CusRelationships',
        $query
      );
      $request_options['method'] = 'GET';
      $response = $this->handleRequest($request_options);
      if ($response instanceof RequestException) {
        $this->ssoHelper->log("Error with request to check Data Service related user companies.");
        return;
      }
      elseif ($json_data = json_decode($response)) {
        $data = $json_data->d;
        if (!empty($data)) {
          foreach ($data as $relationship) {
            if (is_object($relationship) && $relationship->MasterCustomerId == $user_id) {
              $related_companies[] = $relationship->RelatedMasterCustomerId;
            }
            else {
              $this->ssoHelper->log('User does not have related Personify Companies.');
            }
          }
        }
      }
      else {
        $this->ssoHelper->log('Wrong response from Data Service.');
      }
    }
    // Check and get Company Information.
    if (!empty($related_companies)) {
      // Load already existing companies.
      $existing_companies = $this->companyStorage->getExistCompanyByPersonifyId($related_companies);
      // Array with Personify IDs for companies that need data.
      $need_info_companies = array_diff_key(array_flip($related_companies), array_flip($existing_companies));
      if (!empty($need_info_companies)) {
        $first = TRUE;
        $query = [];
        foreach ($need_info_companies as $company_id => $value) {
          $query['$filter'] = $first ? "MasterCustomerId eq '$company_id'" : $query['$filter'] . " or MasterCustomerId eq '$company_id'";
          $first = FALSE;
        }
        $query['$select'] = 'MasterCustomerId, LabelName, CustomerClassCode';
        $request_options = $this->ssoHelper->buildDataServiceQuery(
          'CustomerInfos',
          $query
        );
        $request_options['method'] = 'GET';
        $response = $this->handleRequest($request_options);
        if ($response instanceof RequestException) {
          $this->ssoHelper->log("Error with request to get Data Service Companies.");
          return;
        }
        elseif ($json_data = json_decode($response)) {
          $data = $json_data->d;
          if (!empty($data)) {
            foreach ($data as $company) {
              if (is_object($company)) {
                $company_entity = $this->companyStorage->create([
                  'personify_id' => $company->MasterCustomerId,
                  'name' => $company->LabelName,
                  'code' => $company->CustomerClassCode,
                ]);
                $company_entity->save();
                $existing_companies[$company_entity->id()] = $company->MasterCustomerId;
              }
              else {
                $this->ssoHelper->log('Response from the Data Service does not have a company object.');
              }
            }
          }
        }
        else {
          $this->ssoHelper->log('User does not have related Personify Companies.');
        }
      }
      $event->setCompanies(array_keys($existing_companies));
      $event->setUpdateCompanyFlag(TRUE);
    }
    else {
      $this->ssoHelper->log('User does not have related Personify Companies.');
    }
  }

  /**
   * Attempt to handle request to PMMI Personify Services.
   *
   * @param array $request_param
   *   Parameters of the request.
   *
   * @throws RequestException
   *   Thrown if there was a problem with request.
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
