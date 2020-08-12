<?php

namespace Drupal\xcampaign_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class XCampaignApiService
 * @package Drupal\xcampaign_api
 */
class XCampaignApiService implements XCampaignApiServiceInterface {

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
   * XCampaignApiService constructor.
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
    $this->config = $config_factory->get('xcampaign_api.settings');
    $credentials = $this->getCredentials();

    $this->auth = [
      'userCode' => $credentials['username'],
      'clientCode' => $credentials['password'],
      'baseUrl' => $this->config->get('base_url'),
      'newsletter' => $this->config->get('newsletter')
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCredentials() {
    $credential_provider = $this->config->get('credential_provider');

    switch ($credential_provider) {
      case 'config':
        $credentials = $this->config->get('credentials');
        if (isset($credentials['config'])) {
          $username = $credentials['config']['username'];
          $password = $credentials['config']['password'];
        }
        break;

      case 'key':
        /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
        $storage = $this->entityTypeManager->getStorage('key');
        /** @var \Drupal\key\KeyInterface $username_key */
        if ($username_key = $storage->load($this->config['credentials']['key']['username'])) {
          $username = $username_key->getKeyValue();
        }
        /** @var \Drupal\key\KeyInterface $password_key */
        if ($password_key = $storage->load($this->config['credentials']['key']['password'])) {
          $password = $password_key->getKeyValue();
        }
        break;

      case 'multikey':
        /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
        $storage = \Drupal::entityTypeManager()->getStorage('key');
        /** @var \Drupal\key\KeyInterface $username_key */
        if ($user_password_key = $storage->load($this->config['credentials']['multikey']['user_password'])) {
          if ($values = $user_password_key->getKeyValues()) {
            $username = $values['username'];
            $password = $values['password'];
          }
        }
        break;
    }
    return [
      'username' => $username,
      'password' => $password
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createContact($email, $data) {
    if (empty($this->auth)) {
      throw new \Exception("XCampaign API not authorized.");
    }
    // Check if the user already exists.
    $request_json = $this->getRequestJson($data, 'getProfile');
    try {
      $getProfileResponse = \Drupal::httpClient()->post($this->auth['baseUrl'] . '/rest/getProfiles', [
        'headers' => [
          'Content-type' => 'application/json',
          'client' => $this->auth['clientCode'],
          'user' => $this->auth['userCode']
        ],
        'body' => $request_json,
      ]);
      if ($getProfileResponse->getStatusCode() == '200') {
        $getProfileJson = json_decode($getProfileResponse->getBody(), true);
        $this->editContact(reset(reset($getProfileJson['profiles'])['attributes'])['value'], $data);
        return reset(reset($getProfileJson['profiles'])['attributes'])['value'];
      }
    }
    catch (\Exception $exception) {

    }

    $data['newsletter'] = !empty($data['preferences']) && in_array($this->auth['newsletter'], $data['preferences']) ? 1 : 0;
    $request_json = $this->getRequestJson($data, 'registerProfile');
    // Create the http request to the xcampaign.
    $response = \Drupal::httpClient()->post($this->auth['baseUrl'] . '/rest/registerProfile', [
      'headers' => [
        'Content-type' => 'application/json',
        'client' => $this->auth['clientCode'],
        'user' => $this->auth['userCode']
      ],
      'body' => $request_json,
    ]);

    //$this->logErrors($response);
    if ($response->getStatusCode() == '200') {
      $request_json = $this->getRequestJson($data, 'getProfile');
      $getProfileResponse = \Drupal::httpClient()->post($this->auth['baseUrl'] . '/rest/getProfiles', [
        'headers' => [
          'Content-type' => 'application/json',
          'client' => $this->auth['clientCode'],
          'user' => $this->auth['userCode']
        ],
        'body' => $request_json,
      ]);
      $getProfileJson = json_decode($getProfileResponse->getBody(), true);
      \Drupal::logger('xcampaign_api')->notice('User successfully created on XCampaign');
      return reset(reset($getProfileJson['profiles'])['attributes'])['value'];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function editContact($contact_id, $data, $createIfNotExists = false) {
    if (empty($this->auth)) {
      throw new \Exception("XCampaign API not authorized.");
    }
    $data['newsletter'] = !empty($data['preferences']) && in_array($this->auth['newsletter'], $data['preferences']) ? 1 : 0;


    $request_json = $this->getRequestJson($data, 'updateProfile');
    \Drupal::logger('xcampaign_api')->notice($request_json);
    // Create the http request to the xcampaign.
    $response = \Drupal::httpClient()->post($this->auth['baseUrl'] . '/rest/updateProfiles', [
      'headers' => [
        'Content-type' => 'application/json',
        'client' => $this->auth['clientCode'],
        'user' => $this->auth['userCode']
      ],
      'body' => $request_json,
    ]);

    //$this->logErrors($response);
    if ($response->getStatusCode() == '200') {
      \Drupal::logger('xcampaign_api')->notice($response->getBody());
      \Drupal::logger('xcampaign_api')->notice('User successfully updated on XCampaign');
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteContact($contact_id) {
    $request_json = $this->getRequestJson(['contact_id' => $contact_id], 'delete');
    $deleteProfileResponse = \Drupal::httpClient()->post($this->auth['baseUrl'] . '/rest/deleteProfile', [
      'headers' => [
        'Content-type' => 'application/json',
        'client' => $this->auth['clientCode'],
        'user' => $this->auth['userCode']
      ],
      'body' => $request_json,
    ]);
    //$this->logErrors($deleteProfileResponse);
    if ($deleteProfileResponse->getStatusCode() == '200') {
      \Drupal::logger('xcampaign_api')->notice('Profile deleted');
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Helper function to construct the json requests.
   */
  protected function getRequestJson($data, $requestOperation) {
    if ($requestOperation == 'delete') {
      $profile_data = [
        "profile" => [
          "attribute" => [
            "alias" => "sys_recip_id",
            "value" => $data['contact_id']
          ]
        ]
      ];
      $request_json = json_encode($profile_data, true);
    }
    elseif ($requestOperation == 'getProfile') {
      $profile_data = [
        "profile" => [
          "attribute" => [
            "alias" => "email",
            "value" => $data['email']
          ]
        ]
      ];
      $request_json = json_encode($profile_data, true);
    }
    elseif ($requestOperation == 'updateProfile') {
      $profile_data = [
        "profiles" => [
          [
            "attributes" => [
              [
                "alias" => "sys_recip_id",
                "value" => $data['xcampaign_id']
              ],
              [
                "alias" => "email",
                "value" => $data['email']
              ],
              [
                "alias" => "Newsletter",
                "value" => $data['newsletter']
              ],
            ],
          ],
        ],
      ];
      if (!empty($data['first_name'])) {
        $profile_data['profiles'][0]['attributes'][] = [
          "alias" => "krit_151",
          "value" => $data['first_name']
        ];
      }
      if (!empty($data['last_name'])) {
        $profile_data['profiles'][0]['attributes'][] = [
          "alias" => "krit_150",
          "value" => $data['last_name']
        ];
      }
      if (!empty($data['user_id'])) {
        $profile_data['profiles'][0]['attributes'][] = [
          "alias" => "drupal_user_id",
          "value" => $data['user_id']
        ];
      }
      if (!empty($data['token'])) {
        $profile_data['profiles'][0]['attributes'][] = [
          "alias" => "drupal_user_token",
          "value" => $data['token']
        ];
      }
      if (!empty($data['address'])) {
        $profile_data['profiles'][0]['attributes'][] = [
          "alias" => "krit_152",
          "value" => $data['address']
        ];
      }
      if (!empty($data['city']) && !empty($data['postcode'])) {
        $profile_data['profiles'][0]['attributes'][] = [
          "alias" => "krit_153",
          "value" => $data['postcode'] . ', ' . $data['city']
        ];
      }
      $request_json = json_encode($profile_data, true);
    }
    elseif ($requestOperation == 'registerProfile') {
      $profile_data = [
        "customId" => 'GCBRegForm' . $data['user_id'],
        "profile" => [
          "importAsDoubleOptIn" => false,
          "attributes" => [
            [
              "alias" => "email",
              "value" => $data['email']
            ],
            [
              "alias" => "sys_access_ip_reg",
              "value" => $data['ip_address']
            ],
            [
              "alias" => "Newsletter",
              "value" => $data['newsletter']
            ],
          ],
        ],
      ];
      if (!empty($data['first_name'])) {
        $profile_data['profile']['attributes'][] = [
          "alias" => "krit_151",
          "value" => $data['first_name']
        ];
      }
      if (!empty($data['last_name'])) {
        $profile_data['profile']['attributes'][] = [
          "alias" => "krit_150",
          "value" => $data['last_name']
        ];
      }
      if (!empty($data['user_id'])) {
        $profile_data['profile']['attributes'][] = [
          "alias" => "drupal_user_id",
          "value" => $data['user_id']
        ];
      }
      if (!empty($data['token'])) {
        $profile_data['profile']['attributes'][] = [
          "alias" => "drupal_user_token",
          "value" => $data['token']
        ];
      }
      if (!empty($data['address'])) {
        $profile_data['profiles'][0]['attributes'][] = [
          "alias" => "krit_152",
          "value" => $data['address']
        ];
      }
      if (!empty($data['city']) && !empty($data['postcode'])) {
        $profile_data['profiles'][0]['attributes'][] = [
          "alias" => "krit_153",
          "value" => $data['postcode'] . ', ' . $data['city']
        ];
      }
      $request_json = json_encode($profile_data, true);
    }
    return $request_json;
  }

  /**
   * {@inheritDoc}
   */
  public function deleteFromBlacklist($email) {
    $profile_data = [
      "profile" => [
        "attribute" => [
          "alias" => "email",
          "value" => $email
        ]
      ]
    ];
    $request_json = json_encode($profile_data, true);
    try {


    $deleteFromBlacklistResponse = \Drupal::httpClient()->post($this->auth['baseUrl'] . '/rest/deleteFromBlacklist', [
      'headers' => [
        'Content-type' => 'application/json',
        'client' => $this->auth['clientCode'],
        'user' => $this->auth['userCode']
      ],
      'body' => $request_json,
    ]);
    } catch (\Exception $e) {}
    //$this->logErrors($deleteProfileResponse);
    if ($deleteFromBlacklistResponse->getStatusCode() == '200') {
      \Drupal::logger('xcampaign_api')->notice('Profile deleted');
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function updateBlacklist($email) {
    $profile_data = [
      "profile" => [
        "attribute" => [
          "alias" => "email",
          "value" => $email
        ]
      ]
    ];
    $request_json = json_encode($profile_data, true);
    try {
      $updateBlacklistResponse = \Drupal::httpClient()->post($this->auth['baseUrl'] . '/rest/updateBlacklist', [
        'headers' => [
          'Content-type' => 'application/json',
          'client' => $this->auth['clientCode'],
          'user' => $this->auth['userCode']
        ],
        'body' => $request_json,
      ]);
    } catch (\Exception $e) {}
    //$this->logErrors($deleteProfileResponse);
    if ($updateBlacklistResponse->getStatusCode() == '200') {
      \Drupal::logger('xcampaign_api')->notice('Profile deleted');
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Small helper function to log xcampaign api errors.
   *
   * @param \GuzzleHttp\Psr7\Response $response
   *
   */
  protected function logErrors($response) {
    // Log all errors.
    //\Drupal::logger('commerce_xcampaign')->error('XCampaign API Error: @message', ['@message' => $response->getBody()]);
  }

}
