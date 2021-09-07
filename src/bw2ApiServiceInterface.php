<?php

namespace Drupal\bw2_api;

/**
 * Interface bw2ApiServiceInterface
 *
 * @package Drupal\bw2_api
 */
interface bw2ApiServiceInterface {

  /**
   * Gets username/password for bw2 api call.
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

  /**
   * @param $email
   *
   * @return mixed
   */
  public function updateBlacklist($email);

  /**
   * @param $email
   *
   * @return mixed
   */
  public function deleteFromBlacklist($email);

}
