<?php

namespace Drupal\bw2_api;

/**
 * Interface bw2ApiServiceInterface
 *
 * @package Drupal\bw2_api
 */
interface bw2ApiServiceInterface {

  /**
   *
   * @return array
   */
  public function getContacts();

  /**
   *
   * @return array
   */
  public function getCountryInformation();

  /**
   *
   * @return array
   */
  public function getLanguageInformation();

  /**
   * @param string $email
   * @param entity $data
   *
   * @return mixed
   */
  public function createContact($data);

  /**
   * @param integer $contact_id
   * @param entity $data
   * @param bool $createIfNotExists
   *
   * @return mixed
   */
  public function editContact($contact_id, $data, $createIfNotExists);

}
