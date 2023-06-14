<?php

namespace Drupal\bw2_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class Bw2ApiService.
 * 
 * @package Drupal\bw2_api
 */
class Bw2ApiService implements Bw2ApiServiceInterface {

  /**
   * The entity type manager.
   * 
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   *   
   */
  protected $entityTypeManager;

  /**
   * The immutable entity clone settings configuration entity.
   * 
   * @var \Drupal\Core\Config\ImmutableConfig
   *   
   */
  protected $config;

  /**
   * The current request.
   * 
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * An array with credentials.
   * 
   * @var array
   *   
   */
  protected $auth;

  /**
   * Bw2ApiService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    RequestStack $request_stack
 ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->request = $request_stack->getCurrentRequest();
    $this->config = $config_factory->get('bw2_api.settings');

    $this->auth = [
      'baseUrl' => $this->config->get('base_url'),
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
    $response = \Drupal::httpClient()->get($this->getCredentials()['baseUrl'], [
      'headers' => $this->getGetHeaders(),
      'body' => $request_json,
    ]);

    if ($response->getStatusCode() == '200') {
      \Drupal::logger('bw2_api')->notice('Users list retrieved from bw2');
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
    $response = \Drupal::httpClient()->get($this->getCredentials()['baseUrl'], [
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
    $response = \Drupal::httpClient()->get($this->getCredentials()['baseUrl'], [
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
    $response = \Drupal::httpClient()->post($this->getCredentials()['baseUrl'], [
      'headers' => $this->getPostHeaders(),
      'body' => $request_json,
    ]);

    if ($response->getStatusCode() == '200') {
      $responseData = json_decode($response->getBody(), TRUE);
      if ($responseData['MessageDescription'] === "SUCCESS") {
        \Drupal::logger('bw2_api')->notice('User successfully created on bw2');
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
    $response = \Drupal::httpClient()->post($this->getCredentials()['baseUrl'], [
      'headers' => $this->getPostHeaders(),
      'body' => $request_json,
    ]);

    if ($response->getStatusCode() == '200') {
      $responseData = json_decode($response->getBody(), TRUE);
      if ($responseData['MessageDescription'] === "SUCCESS") {
        \Drupal::logger('bw2_api')->notice('User successfully updated on bw2');
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
       foreach ($codes['DataList'] as $key => $lang) {
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
       foreach ($codes['DataList'] as $key => $country) {
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
   * We use the current_item_version to retrieve 
   * only the newly created users.
   */
  public function userExists($email) {
    $users = $this->getContacts($this->config->get('current_item_version'));
    foreach ($users['DataList'] as $key => $user) {
      if ($user['Account_Email1'] === $email) {
        return $user['Account_ID'];
      }
    }
    return FALSE;
  }

}
