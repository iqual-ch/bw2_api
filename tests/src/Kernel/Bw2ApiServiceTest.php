<?php

namespace Drupal\Tests\bw2_api\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Test class for Bw2 API.
 *
 * @coversDefaultClass \Drupal\bw2_api\Bw2ApiService
 * @group bw2_api
 */
class Bw2ApiServiceTest extends KernelTestBase implements ServiceModifierInterface {

  private const BASE_URL = 'https://base_test.endpoint.url.tld';

  private const PORTALGUID = '8e0a3bdd-7357-7357-7357-944e48d3ad7d';

  private const OBJECTGUID_GET = '5350cd9b-7357-7357-7357-899c73574d4a';

  private const OBJECTGUID_POST = '735708aa-7357-7357-7357-fb673576cf22';

  private const PASSWORD = 'testpass';

  private const CONTACTS_RESPONSE = '{"MessageCode":"0","MessageDescription":"SUCCESS","Result":"{\"MaxItemVersion\":2139851,\"DataList\":[{\"Account_ID\":-2144271219,\"Account_Salutation\":\"\",\"Account_Active\":true,\"Account_LastName\":\"Bern Test ARC4\",\"Account_FirstName\":\"\",\"Account_AddressLine1\":\"OfficeTest ARC4\",\"Account_Street\":\"\",\"Account_POBox\":\"\",\"Account_PostalCode\":\"\",\"Account_City\":\"\",\"Account_Email1\":\"empty@empty.com\",\"Account_Country_Dimension_ID\":-2147483512,\"Account_Country_Dimension_Code\":\"CH\",\"Account_Country_Dimension_Name\":\"Schweiz\",\"Account_Language_Dimension_ID\":-2147301090,\"Account_Language_Dimension_Code\":\"D\",\"Account_Language_Dimension_Name\":\"German\",\"Account_Gender_Dimension_ID\":0,\"Account_Gender_Dimension_Code\":\"\",\"Account_Gender_Dimension_Name\":\"\",\"Visitor_AllowEmail\":false,\"Account_Birthday\":\"2003-09-01T00:00:00Z\"},{\"Account_ID\":-2144263922,\"Account_Salutation\":\"\",\"Account_Active\":true,\"Account_LastName\":\"Sabato\"}],\"MessageCode\":\"0\",\"MessageDescription\":\"SUCCESS\"}"}';

  private const COUNTRY_INFORMATION_RESPONSE = '{"MessageCode":"0","MessageDescription":"SUCCESS","Result":"{\"MaxItemVersion\":2139851,\"DataList\":[{\"Dimension_ID\":-2147483512,\"Dimension_Code\":\"CH\",\"Dimension_Name\":\"Schweiz\",\"Dimension_Active\":true},{\"Dimension_ID\":-2147483511,\"Dimension_Code\":\"DE\",\"Dimension_Name\":\"\",\"Dimension_Active\":true}],\"MessageCode\":\"0\",\"MessageDescription\":\"SUCCESS\"}"}';

  private const LANGUAGE_INFORMATION_RESPONSE = '{"MessageCode":"0","MessageDescription":"SUCCESS","Result":"{\"MaxItemVersion\":2139851,\"DataList\":[{\"Dimension_ID\":-2147301090,\"Dimension_Code\":\"D\",\"Dimension_Active\":true,\"Account_Language_Dimension_Name\":\"German\"},{\"Dimension_ID\":-2147301089,\"Dimension_Code\":\"E\",\"Dimension_Active\":true,\"Account_Language_Dimension_Name\":\"English\"},{\"Dimension_ID\":-2147301088,\"Dimension_Code\":\"F\",\"Dimension_Active\":true,\"Account_Language_Dimension_Name\":\"French\"},{\"Dimension_ID\":-2147301087,\"Dimension_Code\":\"I\",\"Dimension_Active\":true,\"Account_Language_Dimension_Name\":\"Italian\"},{\"Dimension_ID\":-2147301086,\"Dimension_Code\":\"S\",\"Dimension_Active\":true,\"Account_Language_Dimension_Name\":\"Spanish\"}],\"MessageCode\":\"0\",\"MessageDescription\":\"SUCCESS\"}"}';

  private const CREATE_USER_RESPONSE = '{"MessageCode":"0","MessageDescription":"SUCCESS","Result":"{\"MessageCode\":\"0\",\"MessageDescription\":\"SUCCESS\",\"ItemGuid\":\"a5224620-918d-4ad8-8de8-98d60e374bd9\",\"ItemID\":-2144227164}"}';

  private const GET_CONTACTS_RESPONSE = '{"MessageCode":"0","MessageDescription":"SUCCESS","Result":"{\"MaxItemVersion\":2139857,\"DataList\":[{\"Account_ID\":-2144271219,\"Account_Salutation\":\"\",\"Account_Active\":true,\"Account_LastName\":\"Bern Test ARC4\",\"Account_FirstName\":\"\",\"Account_AddressLine1\":\"OfficeTest ARC4\",\"Account_Street\":\"\",\"Account_POBox\":\"\",\"Account_PostalCode\":\"\",\"Account_City\":\"\",\"Account_Email1\":\"empty@empty.com\",\"Account_Country_Dimension_ID\":-2147483512,\"Account_Country_Dimension_Code\":\"CH\",\"Account_Country_Dimension_Name\":\"Schweiz\",\"Account_Language_Dimension_ID\":-2147301090,\"Account_Language_Dimension_Code\":\"D\",\"Account_Language_Dimension_Name\":\"German\",\"Account_Gender_Dimension_ID\":0,\"Account_Gender_Dimension_Code\":\"\",\"Account_Gender_Dimension_Name\":\"\",\"Visitor_AllowEmail\":false,\"Account_Birthday\":\"2003-09-01T00:00:00Z\"}]}"}';

  private const GET_CONTACTS_EXISTING_USER_RESPONSE = '{"MessageCode":"0","MessageDescription":"SUCCESS","Result":"{\"MaxItemVersion\":2139857,\"DataList\":[{\"Account_ID\":-2144271219,\"Account_Salutation\":\"\",\"Account_Active\":true,\"Account_LastName\":\"Bern Test ARC4\",\"Account_FirstName\":\"\",\"Account_AddressLine1\":\"OfficeTest ARC4\",\"Account_Street\":\"\",\"Account_POBox\":\"\",\"Account_PostalCode\":\"\",\"Account_City\":\"\",\"Account_Email1\":\"unit.tester@example.ch\",\"Account_Country_Dimension_ID\":-2147483512,\"Account_Country_Dimension_Code\":\"CH\",\"Account_Country_Dimension_Name\":\"Schweiz\",\"Account_Language_Dimension_ID\":-2147301090,\"Account_Language_Dimension_Code\":\"D\",\"Account_Language_Dimension_Name\":\"German\",\"Account_Gender_Dimension_ID\":0,\"Account_Gender_Dimension_Code\":\"\",\"Account_Gender_Dimension_Name\":\"\",\"Visitor_AllowEmail\":false,\"Account_Birthday\":\"2003-09-01T00:00:00Z\"}]}"}';

  private const EDIT_USER_RESPONSE = '{"MessageCode":"0","MessageDescription":"SUCCESS","Result":"{\"MessageCode\":\"0\",\"MessageDescription\":\"SUCCESS\",\"ItemGuid\":\"6b80034a-4e5d-47d7-ae4a-6055e6d242bd\",\"ItemID\":-2144268961}"}';

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
   * @var \Drupal\Tests\bw2_api\Kernel\TestBw2ApiService
   */
  protected $bw2Api;

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $service_definition = $container->getDefinition('bw2_api');
    $service_definition->setClass(TestBw2ApiService::class);
  }

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installConfig([
      'bw2_api',
    ]);
    $this->configFactory = $this->container->get('config.factory');
    $config = $this->configFactory->getEditable('bw2_api.settings');
    $config->set('base_url', self::BASE_URL);
    $config->set('portalguid', self::PORTALGUID);
    $config->set('objectguid_get', self::OBJECTGUID_GET);
    $config->set('objectguid_post', self::OBJECTGUID_POST);
    $config->set('password', self::PASSWORD);
    $config->save();
    $this->bw2Api = $this->container->get('bw2_api');
  }

  /**
   * Sets up the client.
   *
   * @param GuzzleHttp\Psr7\Response[] $responses
   *   The mocked responses array.
   */
  protected function setUpClient($responses) {
    $mock = new MockHandler($responses);
    $handler_stack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handler_stack]);
    $this->bw2Api->setClient($client);
  }

  /**
   * Tests getting the credentials from config.
   */
  public function testGetCredentials() {
    $config = $this->bw2Api->getCredentials();
    $this->assertEquals($config['portalguid'], self::PORTALGUID);
    $this->assertEquals($config['objectguid_get'], self::OBJECTGUID_GET);
    $this->assertEquals($config['objectguid_post'], self::OBJECTGUID_POST);
    $this->assertEquals($config['password'], self::PASSWORD);
  }

  /**
   * Tests getContacts status code non 200.
   */
  public function testGetContactsNon200StatusCode() {
    $responses = [
      new Response(301, [], ''),
    ];
    $this->setUpClient($responses);
    $result = $this->bw2Api->getContacts();
    $this->assertFalse($result);
  }

  /**
   * Tests getting the contacts from api.
   */
  public function testGetContacts() {
    $responses = [
      new Response(200, [], self::CONTACTS_RESPONSE),
    ];
    $this->setUpClient($responses);
    $result = $this->bw2Api->getContacts();
    $this->assertEquals($result['MessageCode'], '0');
    $this->assertEquals($result['MessageDescription'], 'SUCCESS');
    $this->assertIsArray($result['DataList']);
    $this->assertEquals($result['DataList'][1]['Account_ID'], '-2144263922');
  }

  /**
   * Tests getCountryInformation status code non 200.
   */
  public function testGetCountryInformationNon200StatusCode() {
    $responses = [
      new Response(301, [], ''),
    ];
    $this->setUpClient($responses);
    $result = $this->bw2Api->getCountryInformation();
    $this->assertFalse($result);
  }

  /**
   * Tests getting the countries from api.
   */
  public function testGetCountryInformation() {
    $responses = [
      new Response(200, [], self::COUNTRY_INFORMATION_RESPONSE),
    ];
    $this->setUpClient($responses);
    $results = $this->bw2Api->getCountryInformation();
    $this->assertEquals($results['MessageCode'], '0');
    $this->assertEquals($results['MessageDescription'], 'SUCCESS');
    $this->assertIsArray($results['DataList']);
    $this->assertEquals($results['DataList'][0]['Dimension_Code'], 'CH');
  }

  /**
   * Tests getLanguageInformation status code non 200.
   */
  public function testGetLanguageInformationNon200StatusCode() {
    $responses = [
      new Response(301, [], ''),
    ];
    $this->setUpClient($responses);
    $result = $this->bw2Api->getLanguageInformation();
    $this->assertFalse($result);
  }

  /**
   * Tests getting the languages from api.
   */
  public function testGetLanguageInformation() {
    $responses = [
      new Response(200, [], self::LANGUAGE_INFORMATION_RESPONSE),
    ];
    $this->setUpClient($responses);
    $result = $this->bw2Api->getLanguageInformation();
    $this->assertEquals($result['MessageCode'], '0');
    $this->assertEquals($result['MessageDescription'], 'SUCCESS');
    $this->assertIsArray($result['DataList']);
    $this->assertEquals($result['DataList'][2]['Account_Language_Dimension_Name'], 'French');
  }

  /**
   * Tests creating user from data.
   */
  public function testCreateUser() {
    $responses = [
      new Response(200, [], self::GET_CONTACTS_RESPONSE),
      new Response(200, [], self::CREATE_USER_RESPONSE),
    ];
    $this->setUpClient($responses);
    $data = $this->getNewUserData();
    $item_id = $this->bw2Api->createContact($data);
    $this->assertIsInt($item_id);
    $this->assertEquals($item_id, '-2144227164');
  }

  /**
   * Tests editing user from data.
   */
  public function testEditUser() {
    $responses = [
      new Response(200, [], self::GET_CONTACTS_EXISTING_USER_RESPONSE),
      new Response(200, [], self::EDIT_USER_RESPONSE),
    ];
    $this->setUpClient($responses);
    $data = $this->getExistingUserData();
    $result = $this->bw2Api->createContact($data);
    $this->assertEquals($result, '-2144271219');
  }

  /**
   * Provide sample user data as array in correct format for bw2.
   */
  public function getNewUserData() {
    return [
      'Account_Active' => TRUE,
      'Account_Salutation' => 'Herr',
      'Account_FirstName' => 'Unit Tester',
      'Account_LastName' => 'Example',
      'Account_AddressLine1' => 'address_line1',
      'Account_Street' => 'address_line2',
      'Account_POBox' => 'POBox',
      'Account_PostalCode' => '3007',
      'Account_City' => 'Bern',
      'Account_Country_Dimension_ID' => -2_147_483_512,
      'Account_Email1' => 'unit.tester_' . random_int(0, mt_getrandmax()) . '@example.ch',
      'Account_Language_Dimension_ID' => -2_147_301_090,
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
      'Account_LastName' => 'Example',
      'Account_AddressLine1' => 'address_line1',
      'Account_Street' => 'address_line2',
      'Account_POBox' => 'POBox',
      'Account_PostalCode' => '3007',
      'Account_City' => 'Bern',
      'Account_Country_Dimension_ID' => -2_147_483_512,
      'Account_Email1' => 'unit.tester@example.ch',
      'Account_Language_Dimension_ID' => -2_147_301_090,
      'Visitor_AllowEmail' => FALSE,
      'Account_Birthday' => '2000-01-01',
    ];
  }

}
