<?php

namespace Drupal\Tests\xcampaign_api\Kernel;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Render\Markup;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\xcampaign_api\XCampaignApiService
 * @group xcampaign_api
 */
class XCampaignApiServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'xcampaign_api',
  ];

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The xcampaign_api.
   *
   * @var \Drupal\xcampaign_api\XCampaignApiService
   */
  protected $xcampaignApi;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig([
      'xcampaign_api',
    ]);
    $this->configFactory = $this->container->get('config.factory');
    $config = $this->configFactory->getEditable('xcampaign_api.settings');
    $config->set('credential_provider', 'config');
    $config->set('credentials', [
      'config' => [
        'username' => 'Test',
        'password' => 'Password',
      ],
    ]);
    $config->save();
    $this->xcampaignApi = $this->container->get('xcampaign_api');
  }

  /**
   * Tests getting the credentials from config.
   */
  public function testGetCredentials() {
    $config = $this->xcampaignApi->getCredentials();
    $this->assertTrue($config['username'] == 'Test');
    $this->assertTrue($config['password'] == 'Password');
  }

}
