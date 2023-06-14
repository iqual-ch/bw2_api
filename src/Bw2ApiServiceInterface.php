<?php

namespace Drupal\bw2_api;

/**
 * Interface Bw2ApiServiceInterface
 *
 * @package Drupal\bw2_api
 */
interface Bw2ApiServiceInterface {

  /**
   * Retrieve all contacts from bw2.
   *
   * @return array
   *   An array of contacts.
   */
  public function getContacts();

  /**
   * Get country codes from bw2.
   *
   * @return array
   *   An array of country codes.
   */
  public function getCountryInformation();

  /**
   * Get language codes from bw2.
   * 
   * @return array
   *   An array of language codes.
   */
  public function getLanguageInformation();

  /**
   * Create a new contact in bw2.
   * 
   * @param array $data
   *   An array of user data.
   *
   * @return mixed
   *   The bw2 id if creation was successfull or FALSE.
   */
  public function createContact($data);

  /**
   * Update an existing contact in bw2.
   * 
   * @param integer $contact_id
   *   The bw2 unique user id.
   * @param array $data
   *   An array of user data.
   * @param bool $createIfNotExists
   *   If TRUE a user will be created.
   *
   * @return mixed
   *   The bw2 id if update was successfull or FALSE.
   */
  public function editContact($contact_id, $data, $createIfNotExists);

}
