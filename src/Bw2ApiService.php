<?php

namespace Drupal\bw2_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * API Client to get and post data to bW2.
 *
 * @package Drupal\bw2_api
 */
class Bw2ApiService implements Bw2ApiServiceInterface {

  /**
   * The client factory to create the client with the configuration.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The immutable entity clone settings configuration entity.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The Logger channel Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * An array with credentials.
   *
   * @var array
   */
  protected $auth;

  /**
   * Bw2ApiService constructor.
   *
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   The Http Client factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   *   The logger channel factory.
   */
  public function __construct(
    ClientFactory $http_client_factory,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    RequestStack $request_stack,
    LoggerChannelFactory $loggerChannelFactory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->request = $request_stack->getCurrentRequest();
    $this->config = $config_factory->get('bw2_api.settings');
    $this->client = $http_client_factory->fromOptions([
      'base_uri' => $this->getConfig('base_url'),
    ]);
    $this->logger = $loggerChannelFactory->get('bw2_api');

    $this->auth = [
      'portalguid' => $this->config->get('portalguid'),
      'objectguid_get' => $this->config->get('objectguid_get'),
      'objectguid_post' => $this->config->get('objectguid_post'),
      'password' => $this->config->get('password'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCredentials() {
    return $this->auth;
  }

  /**
   * Prepares an array of headers for GET requests.
   */
  public function getGetHeaders() {
    return [
      'Content-type' => 'application/json',
      'requesttype' => 'string',
      'objectguid' => $this->getCredentials()['objectguid_get'],
      'portalguid' => $this->getCredentials()['portalguid'],
    ];
  }

  /**
   * Prepares an array of headers for POST requests.
   */
  public function getPostHeaders() {
    return [
      'Content-type' => 'application/json',
      'requesttype' => 'string',
      'objectguid' => $this->getCredentials()['objectguid_post'],
      'portalguid' => $this->getCredentials()['portalguid'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getContacts($max_item_version = FALSE) {
    if (empty($this->getCredentials())) {
      throw new \Exception("bw2 API not authorized.");
    }

    $request_json = $this->getRequestJson(NULL, 'getUsers', $max_item_version);
    // Create the http request to the bw2.
    $response = $this->client->get('', [
      'headers' => $this->getGetHeaders(),
      'body' => $request_json,
    ]);

    if ($response->getStatusCode() == '200') {
      $this->logger->notice('Users list retrieved from bw2');
      $data = json_decode($response->getBody(), TRUE);
      $result = json_decode($data['Result'], TRUE);
      return $result;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryInformation() {
    if (empty($this->getCredentials())) {
      throw new \Exception("bw2 API not authorized.");
    }

    $request_json = $this->getRequestJson(NULL, 'getCountries');
    // Create the http request to the bw2.
    $response = $this->client->get('', [
      'headers' => $this->getGetHeaders(),
      'body' => $request_json,
    ]);

    if ($response->getStatusCode() == '200') {
      $data = json_decode($response->getBody(), TRUE);
      return json_decode($data['Result'], TRUE);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageInformation() {
    if (empty($this->getCredentials())) {
      throw new \Exception("bw2 API not authorized.");
    }

    $request_json = $this->getRequestJson(NULL, 'getLanguages');
    // Create the http request to the bw2.
    $response = $this->client->get('', [
      'headers' => $this->getGetHeaders(),
      'body' => $request_json,
    ]);

    if ($response->getStatusCode() == '200') {
      $data = json_decode($response->getBody(), TRUE);
      return json_decode($data['Result'], TRUE);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function createContact($data) {
    if (empty($this->getCredentials())) {
      throw new \Exception("bw2 API not authorized.");
    }
    /*
     * If the user already exists in the CRM
     * we update it instead with the correct AccountID.
     */
    if ($user_id = $this->userExists($data['Account_Email1'])) {
      return $this->editContact($user_id, $data, TRUE);
    }
    else {
      $request_json = $this->getRequestJson($data, 'createUser');
    }

    // Create the http request to the bw2.
    $response = $this->client->get('', [
      'headers' => $this->getPostHeaders(),
      'body' => $request_json,
    ]);

    if ($response->getStatusCode() == '200') {
      $responseData = json_decode($response->getBody(), TRUE);
      if ($responseData['MessageDescription'] === "SUCCESS") {
        $this->logger->notice('User successfully created on bw2');
        $result = json_decode($responseData['Result'], TRUE);
        return $result['ItemID'];
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function editContact($contact_id, $data, $createIfNotExists = FALSE) {
    if (empty($this->getCredentials())) {
      throw new \Exception("bw2 API not authorized.");
    }

    $request_json = $this->getRequestJson($data, 'updateUser', $contact_id);
    // Create the http request to the bw2.
    $response = $this->client->get('', [
      'headers' => $this->getPostHeaders(),
      'body' => $request_json,
    ]);

    if ($response->getStatusCode() == '200') {
      $responseData = json_decode($response->getBody(), TRUE);
      if ($responseData['MessageDescription'] === "SUCCESS") {
        $this->logger->notice('User successfully updated on bw2');
        return ($createIfNotExists) ? $contact_id : TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Helper function to construct the json requests.
   */
  protected function getRequestJson($data, $requestOperation, $extra_param = FALSE) {
    if ($requestOperation == 'getUsers') {
      $requestArray = [
        'Data' => [
          'ItemVersion' => ($extra_param) ? $extra_param : '0',
          'DataProviderCode' => 'GastData',
          'Password' => $this->getCredentials()['password'],
        ],
      ];
    }
    elseif ($requestOperation == 'getCountries') {
      $requestArray = [
        'Data' => [
          'ItemVersion' => ($extra_param) ? $extra_param : '0',
          'DataProviderCode' => 'CountryData',
          'Password' => $this->getCredentials()['password'],
        ],
      ];
    }
    elseif ($requestOperation == 'getLanguages') {
      $requestArray = [
        'Data' => [
          'ItemVersion' => ($extra_param) ? $extra_param : '0',
          'DataProviderCode' => 'LanguageData',
          'Password' => $this->getCredentials()['password'],
        ],
      ];
    }
    elseif ($requestOperation == 'createUser') {
      $requestArray = [
        'Data' => [
          'itemWriterCode' => 'GastItemWriter',
          'ItemProperties' => $data,
          'Password' => $this->getCredentials()['password'],
        ],
      ];
    }
    elseif ($requestOperation == 'updateUser') {
      $requestArray = [
        'Data' => [
          'itemWriterCode' => 'GastItemWriter',
          'Item_ID' => $extra_param,
          'ItemProperties' => $data,
          'Password' => $this->getCredentials()['password'],
        ],
      ];
    }
    $request_json = json_encode($requestArray, TRUE);
    return $request_json;
  }

  /**
   * Helper function to convert the user language to the correct bw2 ID.
   */
  public function getLanguageCode($langCode) {
    $codes = $this->getLanguageInformation();
    $dimension_code = NULL;
    switch ($langCode) {
      case 'de':
        $dimension_code = "D";
        break;

      case 'fr':
        $dimension_code = "F";
        break;

      case 'en':
        $dimension_code = "E";
        break;

      case 'it':
        $dimension_code = "I";
        break;

      case 'es':
        $dimension_code = "S";
        break;

    }
    if ($dimension_code) {
      foreach ($codes['DataList'] as $lang) {
        if ($lang['Dimension_Code'] === $dimension_code) {
          $code = $lang['Dimension_ID'];
          break;

        }
      }
    }
    return $code;
  }

  /**
   * Helper function to convert the user country to the correct bw2 ID.
   */
  public function getCountryCode($countryCode) {
    $codes = $this->getCountryInformation();
    $dimension_code = $countryCode;
    if ($dimension_code) {
      foreach ($codes['DataList'] as $country) {
        if ($country['Dimension_Code'] === $dimension_code) {
          $code = $country['Dimension_ID'];
          break;

        }
      }
    }
    return $code;
  }

  /**
   * Helper function to check if user exist in bw2.
   *
   * We use the current_item_version to retrieve
   * only the newly created users.
   */
  public function userExists($email) {
    $users = $this->getContacts($this->config->get('current_item_version'));
    foreach ($users['DataList'] as $user) {
      if ($user['Account_Email1'] === $email) {
        return $user['Account_ID'];
      }
    }
    return FALSE;
  }

}
