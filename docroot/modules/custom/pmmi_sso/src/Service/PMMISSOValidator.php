<?php

namespace Drupal\pmmi_sso\Service;

use Drupal\pmmi_sso\Exception\PMMISSOValidateException;
use Drupal\pmmi_sso\Parsers\PMMISSOXmlParser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\pmmi_sso\PMMISSOPropertyBag;

/**
 * Class PMMISSOValidator.
 */
class PMMISSOValidator {

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
   * Constructor.
   *
   * @param Client $http_client
   *   The HTTP Client library.
   * @param PMMISSOHelper $sso_helper
   *   The PMMI SSO Helper service.
   * @param PMMISSOXmlParser $parser
   *   The PMMI SSO XML parser service.
   */
  public function __construct(Client $http_client, PMMISSOHelper $sso_helper, PMMISSOXmlParser $parser) {
    $this->httpClient = $http_client;
    $this->ssoHelper = $sso_helper;
    $this->parser = $parser;
  }

  /**
   * Validate the service token parameter present in the request.
   *
   * This method will return the username of the user if valid, and raise an
   * exception if the ticket is not found or not valid.
   *
   * @param string $token
   *   The PMMI SSO authentication ticket to validate.
   * @param bool $internal
   *   Represent if token is internal use.
   *
   * @return PMMISSOPropertyBag
   *   Contains user info from the PMMI SSO server.
   *
   * @throws PMMISSOValidateException
   *   Thrown if there was a problem making the validation request or
   *   if there was a local configuration issue.
   */
  public function validateToken($token, $internal) {
    $options = $this->ssoHelper->getServerValidateOptions($token, $internal);
    if ($options['decrypt']) {
      if ($internal) {
        $this->ssoHelper->log('Attempting to validate service token using DB and service.');
      }
      else {
        $this->ssoHelper->log('Attempting to validate service token using URL: ' . $options['uri']);
      }
      try {
        $options += [
          'form_params' => $options['params'],
          'timeout' => 30,
        ];

        $response = $this->httpClient->request('POST', $options['uri'], $options);
        $response_data = $response->getBody()->getContents();
        $this->ssoHelper->log("Validation response received from PMMI SSO server: " . htmlspecialchars($response_data));
      }
      catch (RequestException $e) {
        throw new PMMISSOValidateException("Error with request to validate token: " . $e->getMessage());
      }
      if ($internal) {
        return $this->validate($response_data, TRUE);
      }
      else {
        return $this->validate($response_data);
      }
    }
    else {
      throw new PMMISSOValidateException('Token not decrypted!!!');
    }
  }

  /**
   * Validation of a service ticket of the PMMI SSO protocol.
   *
   * @param string $data
   *   The raw validation response data from PMMI SSO server.
   * @param bool $simple
   *   The simple validation process.
   *
   * @return \Drupal\pmmi_sso\PMMISSOPropertyBag
   *   Contains user info from the PMMI SSO server.
   *
   * @throws \Drupal\pmmi_sso\Exception\PMMISSOValidateException
   *   Thrown if there was a problem parsing the validation data.
   */
  private function validate($data, $simple = FALSE) {
    $parser = $this->parser;
    $parser->setData($data);
    if ($parser->validateBool('//m:Valid')) {
      if ($token = $parser->getSingleValue('//m:NewCustomerToken')) {
        if ($simple) {
          $property_bag = new PMMISSOPropertyBag();
          $property_bag->setToken($token);
        }
        else {
          $property_bag = $this->getPropertyBag($token);
        }
      }
      else {
        throw new PMMISSOValidateException('XML from PMMI SSO server is not valid. No new token exists.');
      }
    }
    else {
      throw new PMMISSOValidateException("Token from PMMI SSO server is not valid.");
    }

    return $property_bag;
  }

  /**
   * Build & send request for UserID and return PropertyBag.
   *
   * @param string $token
   *   The token of the PMMI SSO user.
   *
   * @return PMMISSOPropertyBag
   *   Contains user info from the PMMI SSO server.
   *
   * @throws PMMISSOValidateException
   *   Thrown if there was a problem parsing the validation data.
   */
  private function getPropertyBag($token) {
    $query_options = $this->ssoHelper->buildSsoServiceQuery(
      'TIMSSCustomerIdentifierGet',
      ['vu', 'vp'],
      ['customerToken' => $token]
    );

    try {
      $options = [
        'form_params' => $query_options['params'],
        'timeout' => 30
      ];

      $response = $this->httpClient->request('POST', $query_options['uri'], $options);
      $response_data = $response->getBody()->getContents();
      $this->ssoHelper->log("User ID received from PMMI SSO server: " . htmlspecialchars($response_data));
    }
    catch (RequestException $e) {
      throw new PMMISSOValidateException("Error with request to get User ID: " . $e->getMessage());
    }
    $parser = $this->parser;
    $parser->setData($response_data);
    $xml = $parser->getNodeList('//m:CustomerIdentifier');
    // There should only be one success element, grab it and extract
    // MasterCustomerId and SubCustomerId.
    if ($xml->length == 0) {
      throw new PMMISSOValidateException("Response with User ID XML from PMMI SSO server is not valid.");
    }
    $data = explode('|', $xml->item(0)->nodeValue);
    $this->ssoHelper->log("Extracted user_id: $data[0]");
    $property_bag = new PMMISSOPropertyBag($data[0]);
    $property_bag->setToken($token);
    $property_bag->setSubCustomerId($data[1]);

    return $property_bag;
  }

}
