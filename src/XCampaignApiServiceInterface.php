<?php

namespace Drupal\xcampaign_api;

/**
 * Interface XCampaignApiServiceInterface
 *
 * @package Drupal\xcampaign_api
 */
interface XCampaignApiServiceInterface {

  /**
   * Gets username/password for xcampaign api call.
   *
   * @return array
   *   Array with 'username' and 'password' as keys.
   */
  public function getCredentials();

  /**
   * @param string $email
   * @param array $data
   *
   * @return mixed
   */
  public function createContact($email, $data);

  /**
   * @param integer $contact_id
   * @param array $data
   * @param bool $createIfNotExists
   *
   * @return mixed
   */
  public function editContact($contact_id, $data, $createIfNotExists);

  /**
   * @param integer $contact_id
   *
   * @return mixed
   */
  public function deleteContact($contact_id);


}
