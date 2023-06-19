<?php

namespace Drupal\bw2_api;

/**
 * Interface describing method available for the Bw2 API Client.
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
  public function createContact(array $data);

  /**
   * Update an existing contact in bw2.
   *
   * @param int $contact_id
   *   The bw2 unique user id.
   * @param array $data
   *   An array of user data.
   * @param bool $createIfNotExists
   *   If TRUE a user will be created.
   *
   * @return mixed
   *   The bw2 id if update was successfull or FALSE.
   */
  public function editContact(int $contact_id, array $data, bool $createIfNotExists = FALSE);

}
