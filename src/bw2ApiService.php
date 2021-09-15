<?php

namespace Drupal\bw2_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class bw2ApiService
 * @package Drupal\bw2_api
 */
class bw2ApiService implements bw2ApiServiceInterface {

  /**
   * @var EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   *   The immutable entity clone settings configuration entity.
   */
  protected $config;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * @var array
   *   Array with credentials.
   */
  protected $auth;

  /**
   * bw2ApiService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param RequestStack $request_stack
   *   The current request stack.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->request = $request_stack->getCurrentRequest();
    $this->config = $config_factory->get('bw2_api.settings');

    $this->auth = [
      'baseUrl' => $this->config->get('base_url'),
      'portalguid' => $this->config->get('portalguid'),
      'objectguid_get' => $this->config->get('objectguid_get'),
      'objectguid_post' => $this->config->get('objectguid_post'),
      'current_item_version' => $this->config->get('current_item_version')
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getContacts($max_item_version = false) {
    if (empty($this->auth)) {
      throw new \Exception("bw2 API not authorized.");
    }

    $request_json = $this->getRequestJson(null, 'getUsers', $max_item_version);
    \Drupal::logger('bw2_api')->notice($request_json);
    // Create the http request to the bw2.
    $response = \Drupal::httpClient()->get($this->auth['baseUrl'], [
      'headers' => [
        'Content-type' => 'application/json',
        'requesttype' => 'string',
        'objectguid' => $this->auth['objectguid_get'],
        'portalguid' => $this->auth['portalguid']
      ],
      'body' => $request_json,
    ]);

    if ($response->getStatusCode() == '200' ) {
      \Drupal::logger('bw2_api')->notice('Users list retrieved from bw2');
      $data = json_decode($response->getBody(), true);
      return json_decode($data['Result'], true);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryInformation() {
    if (empty($this->auth)) {
      throw new \Exception("bw2 API not authorized.");
    }

    $request_json = $this->getRequestJson(null, 'getCountries');
    \Drupal::logger('bw2_api')->notice($request_json);
    // Create the http request to the bw2.
    $response = \Drupal::httpClient()->get($this->auth['baseUrl'], [
      'headers' => [
        'Content-type' => 'application/json',
        'requesttype' => 'string',
        'objectguid' => $this->auth['objectguid_get'],
        'portalguid' => $this->auth['portalguid']
      ],
      'body' => $request_json,
    ]);

    if ($response->getStatusCode() == '200' ) {
      \Drupal::logger('bw2_api')->notice('Countries list retrieved from bw2');
      $data = json_decode($response->getBody(), true);
      return json_decode($data['Result'], true);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageInformation() {
    if (empty($this->auth)) {
      throw new \Exception("bw2 API not authorized.");
    }

    $request_json = $this->getRequestJson(null, 'getLanguages');
    \Drupal::logger('bw2_api')->notice($request_json);
    // Create the http request to the bw2.
    $response = \Drupal::httpClient()->get($this->auth['baseUrl'], [
      'headers' => [
        'Content-type' => 'application/json',
        'requesttype' => 'string',
        'objectguid' => $this->auth['objectguid_get'],
        'portalguid' => $this->auth['portalguid']
      ],
      'body' => $request_json,
    ]);

    if ($response->getStatusCode() == '200' ) {
      \Drupal::logger('bw2_api')->notice('Countries list retrieved from bw2');
      $data = json_decode($response->getBody(), true);
      return json_decode($data['Result'], true);
    }
    return FALSE;
  }


  /**
   * {@inheritdoc}
   */
  public function createContact($data) {
    if (empty($this->auth)) {
      throw new \Exception("bw2 API not authorized.");
    }
    // if the user already exists we update it instead.
    if ($user_id = $this->userExists($data['Account_Email1'])){
      return $this->editContact($user_id, $data);
    }
    else{
      $request_json = $this->getRequestJson($data, 'createUser');
    }
   
    // Create the http request to the bw2.
    $response = \Drupal::httpClient()->post($this->auth['baseUrl'], [
      'headers' => [
        'Content-type' => 'application/json',
        'requesttype' => 'string',
        'objectguid' => $this->auth['objectguid_post'],
        'portalguid' => $this->auth['portalguid']
      ],
      'body' => $request_json,
    ]);

    if ($response->getStatusCode() == '200') {
      \Drupal::logger('bw2_api')->notice($response->getBody());
      \Drupal::logger('bw2_api')->notice('User successfully created on bw2');
      $responseData = json_decode($response->getBody(), true);
      $result = json_decode($responseData['Result'], true);
      return $result['ItemID'];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function editContact($contact_id, $data, $createIfNotExists = false) {
    if (empty($this->auth)) {
      throw new \Exception("bw2 API not authorized.");
    }

    $request_json = $this->getRequestJson($data, 'updateUser', $contact_id);
    \Drupal::logger('bw2_api')->notice($request_json);
    // Create the http request to the bw2.
    $response = \Drupal::httpClient()->post($this->auth['baseUrl'], [
      'headers' => [
        'Content-type' => 'application/json',
        'requesttype' => 'string',
        'objectguid' => $this->auth['objectguid_post'],
        'portalguid' => $this->auth['portalguid']
      ],
      'body' => $request_json,
    ]);

    if ($response->getStatusCode() == '200') {
      \Drupal::logger('bw2_api')->notice($response->getBody());
      \Drupal::logger('bw2_api')->notice('User successfully updated on bw2');
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Helper function to construct the json requests.
   */
  protected function getRequestJson($data, $requestOperation, $extra_param = false) {
    if ($requestOperation == 'getUsers') {
      $requestArray = [
        'Data' => [
          'ItemVersion' => ($extra_param) ? $extra_param : '0',
          'DataProviderCode' => 'GastData'
        ]
      ];
    }
    elseif ($requestOperation == 'getCountries') {
      $requestArray = [
        'Data' => [
          'ItemVersion' => ($extra_param) ? $extra_param : '0',
          'DataProviderCode' => 'CountryData'
        ]
      ];
    }
    elseif ($requestOperation == 'getLanguages') {
      $requestArray = [
        'Data' => [
          'ItemVersion' => ($extra_param) ? $extra_param : '0',
          'DataProviderCode' => 'LanguageData'
        ]
      ];
    }
    elseif ($requestOperation == 'createUser') {
      $requestArray = [
        'Data' => [
          'itemWriterCode' => 'GastItemWriter',
          'ItemProperties' => $data
        ]
      ];
    }
    elseif ($requestOperation == 'updateUser') {
      $requestArray = [
        'Data' => [
          'itemWriterCode' => 'GastItemWriter',
          'Item_ID' => $extra_param,
          'ItemProperties' => $data
        ]
      ];
    }
    $request_json = json_encode($requestArray, true);
    return $request_json;
  }

  
  /**
   * Helper function to convert the user language to the correct bw2 ID.
   */
  public function getLanguageCode($langCode){
    $codes = $this->getLanguageInformation();
    $dimension_code = null;
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
    if ($dimension_code){
       foreach($codes['DataList'] as $key => $lang){
        if ($lang['Dimension_Code'] === $dimension_code ){
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
  public function getCountryCode($countryCode){
    $codes = $this->getCountryInformation();
    $dimension_code = $countryCode;
    if ($dimension_code){
       foreach($codes['DataList'] as $key => $country){
        if ($country['Dimension_Code'] === $dimension_code ){
          $code = $country['Dimension_ID'];
          break;
        }
      }
    }
    return $code;
  }

  /**
   * Helper function to check if user exist in bw2.
   */
  public function userExists($email){
    $users = $this->getContacts();
    foreach($users['DataList'] as $key => $user){
      if ($user['Account_Email1'] === $email ){
        return $user['Account_ID'];
      }
    }
    return false;
  }

}
