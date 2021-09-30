<?php

namespace Drupal\Tests\bw2_api\Kernel;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Render\Markup;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\bw2_api\bw2ApiService
 * @group bw2_api
 */
class bw2ApiServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bw2_api',
  ];

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The bw2_api.
   *
   * @var \Drupal\bw2_api\bw2ApiService
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
    $config->set('portalguid', '123');
    $config->set('objectguid_get', '456');
    $config->set('objectguid_post', '789');
    $config->save();
    $this->bw2Api = $this->container->get('bw2_api');
  }

  /**
   * Tests getting the credentials from config.
   */
  public function testGetCredentials() {
    $config = $this->bw2Api->getCredentials();
    $this->assertTrue($config['portalguid'] == '123');
    $this->assertTrue($config['objectguid_get'] == '456');
    $this->assertTrue($config['objectguid_post'] == '789');
  }

}