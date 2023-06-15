<?php

namespace Drupal\Tests\bw2_api\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test class for Bw2 API.
 *
 * @coversDefaultClass \Drupal\bw2_api\Bw2ApiService
 * @group bw2_api
 */
class Bw2ApiServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bw2_api',
  ];

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The bw2_api.
   *
   * @var \Drupal\bw2_api\Bw2ApiService
   */
  protected $bw2Api;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig([
      'bw2_api',
    ]);
    $this->configFactory = $this->container->get('config.factory');
    $config = $this->configFactory->getEditable('bw2_api.settings');
    $config->set('base_url', 'https://tickets.businesswideweb.net/httpHandler/CustomTicket.ashx');
    $config->set('portalguid', '8e0a3bdd-4364-4ebd-b430-944e48d3ad7d');
    $config->set('objectguid_get', '5350cd9b-bc91-4a84-ac26-899c81754d4a');
    $config->set('objectguid_post', '833e08aa-1a98-4538-a05b-fb62b526cf22');
    $config->set('password', 'Adp4VHck');
    $config->save();
    $this->bw2Api = $this->container->get('bw2_api');
  }

  /**
   * Tests getting the credentials from config.
   */
  public function testGetCredentials() {
    $config = $this->bw2Api->getCredentials();
    $this->assertTrue($config['baseUrl'] == 'https://tickets.businesswideweb.net/httpHandler/CustomTicket.ashx');
    $this->assertTrue($config['portalguid'] == '8e0a3bdd-4364-4ebd-b430-944e48d3ad7d');
    $this->assertTrue($config['objectguid_get'] == '5350cd9b-bc91-4a84-ac26-899c81754d4a');
    $this->assertTrue($config['objectguid_post'] == '833e08aa-1a98-4538-a05b-fb62b526cf22');
    $this->assertTrue($config['password'] == 'Adp4VHck');
  }

  /**
   * Tests getting the contacts from api.
   */
  public function testGetContacts() {
    $results = $this->bw2Api->getContacts();
    $this->assertTrue($results['MessageCode'] == '0');
    $this->assertTrue($results['MessageDescription'] == 'SUCCESS');
    $this->assertTrue(is_array($results['DataList']));
  }

  /**
   * Tests getting the countries from api.
   */
  public function testGetCountryInformation() {
    $results = $this->bw2Api->getCountryInformation();
    $this->assertTrue($results['MessageCode'] == '0');
    $this->assertTrue($results['MessageDescription'] == 'SUCCESS');
    $this->assertTrue(is_array($results['DataList']));
  }

  /**
   * Tests getting the languages from api.
   */
  public function testGetLanguageInformation() {
    $results = $this->bw2Api->getLanguageInformation();
    $this->assertTrue($results['MessageCode'] == '0');
    $this->assertTrue($results['MessageDescription'] == 'SUCCESS');
    $this->assertTrue(is_array($results['DataList']));
  }

  /**
   * Tests creating user from data.
   */
  public function testCreateUser() {
    $data = $this->getNewUserData();
    $item_id = $this->bw2Api->createContact($data);
    $this->assertTrue(is_int($item_id));
  }

  /**
   * Tests editing user from data.
   */
  public function testEditUser() {
    $data = $this->getExistingUserData();
    $results = $this->bw2Api->createContact($data);
    $this->assertTrue($results);
  }

  /**
   * Provide sample user data as array in correct format for bw2.
   */
  public function getNewUserData() {
    return [
      'Account_Active' => TRUE,
      'Account_Salutation' => 'Herr',
      'Account_FirstName' => 'Unit Tester',
      'Account_LastName' => 'Drupal',
      'Account_AddressLine1' => 'address_line1',
      'Account_Street' => 'address_line2',
      'Account_POBox' => 'POBox',
      'Account_PostalCode' => '3007',
      'Account_City' => 'Bern',
      'Account_Country_Dimension_ID' => -2147483512,
      'Account_Email1' => 'unit.tester_' . rand() . '@drupal.ch',
      'Account_Language_Dimension_ID' => -2147301090,
      'Visitor_AllowEmail' => FALSE,
      'Account_Birthday' => '2000-01-01',
    ];
  }

  /**
   * Provide sample user data as array in correct format for bw2.
   */
  public function getExistingUserData() {
    return [
      'Account_Active' => TRUE,
      'Account_Salutation' => 'Herr',
      'Account_FirstName' => 'Unit Tester',
      'Account_LastName' => 'Drupal',
      'Account_AddressLine1' => 'address_line1',
      'Account_Street' => 'address_line2',
      'Account_POBox' => 'POBox',
      'Account_PostalCode' => '3007',
      'Account_City' => 'Bern',
      'Account_Country_Dimension_ID' => -2147483512,
      'Account_Email1' => 'unit.tester@drupal.ch',
      'Account_Language_Dimension_ID' => -2147301090,
      'Visitor_AllowEmail' => FALSE,
      'Account_Birthday' => '2000-01-01',
    ];
  }

}
