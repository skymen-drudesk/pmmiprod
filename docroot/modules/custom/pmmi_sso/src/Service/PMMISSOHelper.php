<?php

namespace Drupal\pmmi_sso\Service;

use DateTime;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class PMMISSOHelper.
 */
class PMMISSOHelper {

  /**
   * Provider name.
   *
   * @var string
   */
  const PROVIDER = 'pmmi_sso';

  /**
   * A String representation for the SSO service.
   *
   * @var string
   */
  const SSO = 'sso';

  /**
   * A string representation for the SSO service.
   *
   * @var string
   */
  const IMS = 'ims';

  /**
   * A string representation for the Data Service.
   *
   * @var string
   */
  const DATA = 'data';

  /**
   * Gateway config: never check preemptively to see if the user is logged in.
   *
   * @var int
   */
  const CHECK_NEVER = -2;

  /**
   * Gateway config: check once per session to see if the user is logged in.
   *
   * @var int
   */
  const CHECK_ONCE = -1;

  /**
   * Gateway config: check on every page load to see if the user is logged in.
   *
   * @var int
   */
  const CHECK_ALWAYS = 0;

  /**
   * Token config: TokenTTL check disabled.
   *
   * @var int
   */
  const TOKEN_DISABLED = 0;

  /**
   * Token config: Frequency mode to check if token is valid..
   *
   * @var int
   */
  const TOKEN_TTL = 1;

  /**
   * Logs out if the user's token has expired.
   *
   * @var int
   */
  const TOKEN_ACTION_LOGOUT = 0;

  /**
   * Redirect to the SSO Login page if the user's token has expired.
   *
   * @var int
   */
  const TOKEN_ACTION_FORCE_LOGIN = 1;

  /**
   * Event type identifier for the PMMISSOPreUserLoadEvent.
   *
   * @var string
   */
  const EVENT_PRE_USER_LOAD = 'pmmi_sso.pre_user_load';

  /**
   * Event type identifier for the PMMISSOPreRegisterEvent.
   *
   * @var string
   */
  const EVENT_PRE_REGISTER = 'pmmi_sso.pre_register';

  /**
   * Event type identifier for the PMMISSOPreLoginEvent.
   *
   * @var string
   */
  const EVENT_PRE_LOGIN = 'pmmi_sso.pre_login';

  /**
   * Event type identifier for pre redirect events.
   *
   * @var string
   */
  const EVENT_PRE_REDIRECT = 'pmmi_sso.pre_redirect';

  /**
   * Event type identifier for the PMMISSOPreUserLoadEvent.
   *
   * @var string
   */
  const EVENT_DATA_SERVICE_LOAD = 'pmmi_sso.data_service_load';

  /**
   * Stores database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * Stores settings object for the Personify Company Entity.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $companySettings;

  /**
   * Stores URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Stores logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $loggerChannel;

  /**
   * Used to get session data.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Used to encode/decode Token from Personify SSO Service.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSOCrypt
   */
  protected $crypt;

  /**
   * Constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param UrlGeneratorInterface $url_generator
   *   The URL generator.
   * @param Connection $database_connection
   *   The database service.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param SessionInterface $session
   *   The session handler.
   * @param PMMISSOCrypt $crypt
   *   The crypt handler.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    UrlGeneratorInterface $url_generator,
    Connection $database_connection,
    LoggerChannelFactoryInterface $logger_factory,
    SessionInterface $session,
    PMMISSOCrypt $crypt
  ) {
    $this->urlGenerator = $url_generator;
    $this->connection = $database_connection;
    $this->session = $session;
    $this->settings = $config_factory->get('pmmi_sso.settings');
    $this->companySettings = $config_factory->get('pmmi_sso.company.settings');
    $this->loggerChannel = $logger_factory->get(PMMISSOHelper::PROVIDER);
    $this->crypt = $crypt;
  }

  /**
   * Return the validation options used to validate the provided token.
   *
   * @param string $raw_token
   *   The token to validate.
   *
   * @return array
   *   The options for building validation Request.
   */
  public function getServerValidateOptions($raw_token, $internal) {
    $token_data = $this->crypt->decrypt($raw_token);
    $options['decrypt'] = FALSE;
    $token = '';
    if (is_string($token_data) || is_numeric($token_data)) {
      $options['decrypt'] = TRUE;
      if ($internal) {
        $token_search = \Drupal::entityTypeManager()
          ->getStorage('pmmi_sso_token')
          ->loadByProperties(['uid' => $token_data]);
        /** @var \Drupal\pmmi_sso\Entity\PMMISSOToken $token_entity */
        if ($token_entity = reset($token_search)) {
          $token = $token_entity->getToken();
        }
      }
      else {
        $data = explode('|', $token_data);
        $token = $data[1];
      }
    }
    $query = $this->buildSsoServiceQuery(
      'SSOCustomerTokenIsValid',
      ['vu', 'vp'],
      ['customerToken' => $token]
    );
    return $options + $query;
  }

  /**
   * Return the query array for building SSO Service Request.
   *
   * @param string $path
   *   The service path.
   * @param array $options
   *   The array of options to add to the query.
   * @param array $parameter
   *   The service parameter to add to the query.
   * @param bool $ims
   *   The boolean flag indicates is query for IM Service.
   *
   * @return array
   *   The options for building SSO Service's Request.
   */
  public function buildSsoServiceQuery($path, array $options, array $parameter, $ims = FALSE) {
    $service_uri = $ims ? $this->getImsUri() : $this->getServiceUri();
    $service_url = $service_uri . '/' . $path;
    $query = [];
    $result = [];
    foreach ($options as $option) {
      switch ($option) {
        case 'vi':
          $query['vendorIdentifier'] = $this->getVi();
          break;

        case 'vu':
          $query['vendorUsername'] = $ims ? $this->getImsVu() : $this->getVu();
          break;

        case 'vp':
          $query['vendorPassword'] = $ims ? $this->getImsVp() : $this->getVp();
          break;

        case 'vib':
          $query['vendorBlock'] = $this->getVib();
          break;

      }
    }
    $result['uri'] = $service_url;
    $result['params'] = $query + $parameter;
    return $result;
  }

  /**
   * Return the query array for building Personify Data Service Request.
   *
   * @param string $collection
   *   The service collection argument.
   * @param array $query
   *   The service query array to build request query.
   * @param string $accept_header
   *   Content-Type header value.
   *
   * @return array
   *   The options for building Data Service Request.
   */
  public function buildDataServiceQuery($collection, array $query, $accept_header = 'application/json') {
    $result = [];
    $service_url = $this->settings->get('data_service.endpoint') . '/' . $collection;
    if (!empty($query)) {
      $query = UrlHelper::buildQuery($query);
      $service_url .= '?' . $query;
    }
    $auth_header = [
      'headers' => [
        'Accept' => $accept_header,
      ],
      'auth' => [
        $this->settings->get('data_service.username'),
        $this->settings->get('data_service.password'),
      ],
    ];
    $result['uri'] = $service_url;
    $result['params'] = $auth_header;
    return $result;
  }

  /**
   * Get the login URI to the PMMI SSO server.
   *
   * @return string
   *   The base URI.
   */
  public function getServerLoginUri() {
    $url = $this->settings->get('login_uri');
    return $url;
  }

  /**
   * Get the Service URI to the PMMI SSO server.
   *
   * @return string
   *   The service URI.
   */
  public function getServiceUri() {
    $url = $this->settings->get('service_uri');
    return $url;
  }

  /**
   * Get the IMS URI to the PMMI SSO server.
   *
   * @return string
   *   The service URI.
   */
  public function getImsUri() {
    $url = $this->settings->get('ims_uri');
    return $url;
  }

  /**
   * Construct the Login URL to the PMMI SSO server.
   *
   * @param string $return_uri
   *   The string with query parameters.
   *
   * @return string
   *   The login URL as string.
   */
  public function generateLoginUrl($return_uri) {
    // Encode URI where we need to redirect user after user login to the SSO
    // Service.
    $uri_encoded = base64_encode($return_uri);

    $now = DateTime::createFromFormat('U.u', microtime(TRUE));
    $timestamp = $now->format('YmdHisv');
    // Generate final redirect string to encode in Token.
    $return_parameters = ['ue' => $uri_encoded];
    $return_uri_sso = $this->urlGenerator->generate('pmmi_sso.service', $return_parameters, TRUE);

    // Fill string to encode in the Token.
    $string = $timestamp . '|' . $return_uri_sso;
    $token = $this->crypt->encrypt($string);

    // Generate final login uri.
    $url = Url::fromUri($this->getServerLoginUri());
    $url->setAbsolute(TRUE);
    $url->setOption('query', ['vi' => $this->getVi(), 'vt' => $token]);

    return $url->toString();
  }

  /**
   * Return the service URL with query string.
   *
   * @param array $service_params
   *   An array of query string parameters to append to the service URL.
   *
   * @return string
   *   The fully constructed service URL to use for PMMI SSO server.
   */
  public function generateSsoServiceUrl(array $service_params = []) {
    if (isset($service_params['returnto'])) {
      if (!empty($service_params['returnto'])) {
        $service_params['ue'] = base64_encode($service_params['returnto']);
      }
      unset($service_params['returnto']);
    }
    $service_params['cti'] = $this->crypt->encrypt($service_params['cti']);
    return $this->urlGenerator->generate('pmmi_sso.service', $service_params, FALSE);
  }

  /**
   * Get allowed Drupal user role array.
   *
   * @return array
   *   The allowed Drupal user role array.
   */
  public function getRoleMapping() {
    $map = $this->settings->get('user_accounts.role_mapping');
    $roles = [];
    if (!empty($map)) {
      foreach ($map as $role_id => $role) {
        $roles[$role_id] = $role['drupal_role_label'];
      }
    }
    return $roles;
  }

  /**
   * Get the IMS allowed roles or Data Service allowed Committee IDs.
   *
   * @param string $service
   *   A service ID.
   *
   * @return array
   *   The IMS Roles or CommitteeMasterCustomer IDs.
   */
  public function getAllowedRoles($service) {
    $map = $this->settings->get('user_accounts.role_mapping');
    $roles = [];
    if (!empty($map)) {
      foreach ($map as $role_id => $role) {
        if ($service == PMMISSOHelper::IMS && $role['service'] == PMMISSOHelper::IMS) {
          $roles[] = $role['sso_role'];
        }
        elseif ($service == PMMISSOHelper::DATA && $role['service'] == PMMISSOHelper::DATA) {
          $roles[] = $role['committee_id'];
        }
      }
    }
    return $roles;
  }

  /**
   * Get the Drupal user role id.
   *
   * Get the Drupal allowed user role array based on IMS allowed roles or
   * allowed Data Service Committee IDs.
   *
   * @param string $service
   *   A service ID.
   * @param array $roles_param
   *   An array of parameters to filter Roles Mapping.
   *
   * @return array
   *   The Drupal Roles ID formated as array. Example: ['pmmi_member', 'staff'].
   */
  public function filterAllowedRoles($service, array $roles_param) {
    $map = $this->settings->get('user_accounts.role_mapping');
    $roles = [];
    if (!empty($map)) {
      foreach ($map as $role_id => $role) {
        if (
          $service == PMMISSOHelper::IMS &&
          $role['service'] == PMMISSOHelper::IMS &&
          in_array(strtolower($role['sso_role']), $roles_param)
        ) {
          $roles[] = $role_id;
        }
        elseif (
          $service == PMMISSOHelper::DATA &&
          $role['service'] == PMMISSOHelper::DATA &&
          in_array($role['committee_id'], $roles_param)
        ) {
          $roles[] = $role_id;
        }
      }
    }
    return $roles;
  }

  /**
   * Get the Vendor Identifier to the PMMI SSO server.
   *
   * @return string
   *   The Vendor Identifier.
   */
  public function getVi() {
    return $this->settings->get('vi');
  }

  /**
   * Get the Vendor username to the PMMI SSO server.
   *
   * @return string
   *   The Vendor username.
   */
  public function getVu() {
    return $this->settings->get('vu');
  }

  /**
   * Get the Vendor password (HEX) to the PMMI SSO server.
   *
   * @return string
   *   The Vendor password (HEX).
   */
  public function getVp() {
    return $this->settings->get('vp');
  }

  /**
   * Get the Vendor initilization block (HEX) to the PMMI SSO server.
   *
   * @return string
   *   The Vendor initilization block (HEX).
   */
  public function getVib() {
    return $this->settings->get('vib');
  }

  /**
   * Get the Vendor username to the PMMI IMS server.
   *
   * @return string
   *   The IMS vendor username.
   */
  public function getImsVu() {
    return $this->settings->get('ims_vu');
  }

  /**
   * Get the Vendor password (HEX) to the PMMI IMS server.
   *
   * @return string
   *   The IMS vendor password (HEX).
   */
  public function getImsVp() {
    return $this->settings->get('ims_vp');
  }

  /**
   * Get the time duration setting for an update Personify company entity.
   *
   * @return int
   *   The time duration.
   */
  public function getPceDurationTime() {
    return $this->companySettings->get('time_duration');
  }

  /**
   * Get the Token check frequency mode.
   *
   * @return int
   *   The token frequency mode.
   */
  public function getTokenFrequency() {
    return $this->settings->get('gateway.token_frequency');
  }

  /**
   * Get action for the failed Token validation result.
   *
   * @return int
   *   The saved action setting.
   */
  public function getTokenAction() {
    return $this->settings->get('gateway.token_action');
  }

  /**
   * Get the frequency mode for the Gateway feature.
   *
   * @return int
   *   Frequency mode of the gateway feature.
   */
  public function getGatewayFrequency() {
    return $this->settings->get('gateway.check_frequency');
  }

  /**
   * Get the saved paths for the gateway & token feature.
   *
   * @return string
   *   The paths.
   */
  public function getGatewayPaths() {
    return $this->settings->get('gateway.paths');
  }

  /**
   * Log information to the logger.
   *
   * Only log supplied information to the logger if module is configured to do
   * so, otherwise do nothing.
   *
   * @param string $message
   *   The message to log.
   */
  public function log($message) {
    if ($this->settings->get('advanced.debug_log') == TRUE) {
      $this->loggerChannel->log(RfcLogLevel::DEBUG, $message);
    }
  }

  /**
   * Encapsulate UrlHelper::isExternal.
   *
   * @param string $url
   *   The url to evaluate.
   *
   * @return bool
   *   Whether or not the url points to an external location.
   *
   * @codeCoverageIgnore
   */
  protected function isExternal($url) {
    return UrlHelper::isExternal($url);
  }

  /**
   * The amount of time to allow a connection to the PMMI SSO server to take.
   *
   * @return int
   *   The timeout in seconds.
   */
  public function getConnectionTimeout() {
    return $this->settings->get('advanced.connection_timeout');
  }

}
