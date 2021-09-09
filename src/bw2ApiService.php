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
      'portaluid' => $this->config->get('portaluid'),
      'objectuid_get' => $this->config->get('objectuid_get'),
      'objectuid_post' => $this->config->get('objectuid_post'),
      'newsletter' => $this->config->get('newsletter')
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getContacts() {
    if (empty($this->auth)) {
      throw new \Exception("bw2 API not authorized.");
    }

    $request_json = $this->getRequestJson('getUsers');
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

    //$this->logErrors($response);
    if ($response->getStatusCode() == '200' ) {
      \Drupal::logger('bw2_api')->notice('Users list retrieved from bw2');
      $data = json_decode($response->getBody(), true);
      return $data['Result'];
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

    $request_json = $this->getRequestJson('getCountries');
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
      return $data['Result'];
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

    $request_json = $this->getRequestJson('getLanguages');
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
      return $data['Result'];
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
    if ($user_id = $this->userExists($data->getEmail())){
      return $this->editContact($user_id, $data);
    }
    else{
      $request_json = $this->getRequestJson($data, 'createUser');
    }
   
    // $data['newsletter'] = !empty($data['preferences']) && in_array($this->auth['newsletter'], $data['preferences']) ? 1 : 0;
    $request_json = $this->getRequestJson($data, 'registerProfile');
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
      $responseData = json_decode($response->getBody(), true);
      \Drupal::logger('bw2_api')->notice($response->getBody());
      \Drupal::logger('bw2_api')->notice('User successfully created on bw2');
      $user->set('field_iq_group_bw2_id', $responseData['Result']['ItemID']);
      return TRUE;
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
    // $data['newsletter'] = !empty($data['preferences']) && in_array($this->auth['newsletter'], $data['preferences']) ? 1 : 0;


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
  protected function getRequestJson($data, $requestOperation, $contact_id = false) {
    if ($requestOperation == 'getUsers') {
      $requestArray = [
        'Data' => [
          'ItemVersion' => '0',
          'DataProviderCode' => 'GastData'
        ]
      ];
    }
    elseif ($requestOperation == 'getCountries') {
      $requestArray = [
        'Data' => [
          'ItemVersion' => '0',
          'DataProviderCode' => 'CountryData'
        ]
      ];
    }
    elseif ($requestOperation == 'getLanguages') {
      $requestArray = [
        'Data' => [
          'ItemVersion' => '0',
          'DataProviderCode' => 'LanguageData'
        ]
      ];
    }
    elseif ($requestOperation == 'createUser') {
      $bw2Data = $this->convertDataForBw2($data);
      $requestArray = [
        'Data' => [
          'itemWriterCode' => 'GastItemWriter',
          'ItemProperties' => $bw2Data
        ]
      ];
    }
    elseif ($requestOperation == 'updateUser') {
      $bw2Data = $this->convertDataForBw2($data);
      $requestArray = [
        'Data' => [
          'itemWriterCode' => 'GastItemWriter',
          'Item_ID' => $contact_id,
          'ItemProperties' => $bw2Data
        ]
      ];
    }
    $request_json = json_encode($requestArray, true);
    return $request_json;
  }

  /**
   * Helper function to convert the user data to the bw2 format.
   */
  public function convertDataForBw2($user){
    $langCode = $this->getLanguageCode($user->getPreferredLangcode());
    $countryCode = $this->getCountryCode(reset($user->get('field_iq_user_base_address')->getValue())['country_code']);
    $profile_data = [
      'Account_Active' => $user->status->value,
      // 'Account_Salutation' => reset($user->get('field_iq_user_base_address')->getValue())['given_name'],
      // 'Account_Drupal_ID' => $user->id(),
      'Account_FirstName' => reset($user->get('field_iq_user_base_address')->getValue())['given_name'],
      'Account_LastName' => reset($user->get('field_iq_user_base_address')->getValue())['family_name'],
      // 'Account_AddressLine1' => reset($user->get('field_iq_user_base_address')->getValue())['address_line1'],
      'Account_Street' => reset($user->get('field_iq_user_base_address')->getValue())['address_line1'],
      // 'Account_POBox' => reset($user->get('field_iq_user_base_address')->getValue())['street'],
      'Account_PostalCode' => reset($user->get('field_iq_user_base_address')->getValue())['postal_code'],
      'Account_City' => reset($user->get('field_iq_user_base_address')->getValue())['locality'],
      'Account_Country_Dimension_ID' => $countryCode,
      'Account_Email1' => $user->getEmail(),
      'Account_Language_Dimension_ID' => $langCode
    ];

    if ($user->hasField('field_iq_group_preferences') && !$user->get('field_iq_group_preferences')->isEmpty()) {
      $profile_data['Visitor_AllowEmail'] = array_filter(array_column($user->field_iq_group_preferences->getValue(), 'target_id'));
    }
    // if ($user->hasField('field_iq_group_bw2_id') && !empty($user->get('field_iq_group_bw2_id')->getValue())) {
    //   $this->bw2ApiService->editContact($user->field_iq_group_bw2_id->value, $profile_data);
    // } else {
    //   $bw2_id = $this->bw2ApiService->createContact($email, $profile_data);
    //   $user->set('field_iq_group_bw2_id', $bw2_id);
    // }
    return $profile_data;
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
       foreach($codes['DataList'] as $lang){
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
       foreach($codes['DataList'] as $country){
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
    foreach($codes['DataList'] as $user){
      if ($user['Account_Email1'] === $email ){
        return $user['Account_ID'];
      }
    }
    return false;
  }

}
