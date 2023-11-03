<?php

namespace Drupal\Tests\bw2_api\Kernel;

use Drupal\bw2_api\Bw2ApiService;
use GuzzleHttp\Client;

/**
 * The test variant of the Bw2ApiService.
 *
 * Used to set the client to a mocked one.
 *
 * @package Drupal\Bw2_Api
 */
class TestBw2ApiService extends Bw2ApiService {

  /**
   * Set the httpClient.
   *
   * @param GuzzleHttp\Client $client
   *   The mocked httpClient.
   */
  public function setClient(Client $client) {
    $this->client = $client;
  }

}