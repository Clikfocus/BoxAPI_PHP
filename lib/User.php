<?php

/**
 * Box API user class.
 *
 * Handles operations related to users.
 */
class User {

  const USER_API_BASE_URL = 'users';

  private $box_client;
  public $id;

  public $login;
  public $name;

  /**
   * Create a new instance of a User.
   *
   * @param BoxClient $box_client
   *  A complete $box_client entity that is prepared to perform requests against
   *  the API.
   */
  public function __construct($box_client) {
    $this->box_client = $box_client;
  }


  /**
   * Get a list of users.
   *
   * @param string $filter
   *  String to be used to filter results by their name or login.
   *
   * @param int $limit
   *  Specifies the max number of results that will be returned.
   */
  public function getUsers($filter = FALSE, $limit = 100, $offset = 0, $param = array()){
    $url_param = array(
      'limit' => $limit,
      'offset' => $offset,
    );

    if (!empty($filter)) {
      $url_param['filter_term'] = $filter;
    }

    $url_param = array_merge($url_param, $param);
    $entries = FALSE;

    $result = $this->request('GET', array('url_param' => $url_param));
    if (!empty($result->entries)) {
      $entries = $result->entries;
    }

    return $entries;
  }

  /**
   * Load a user by their Box ID.
   *
   * @param string $user_id
   *  The Box ID of the user to be fetched.
   *
   * @return object
   *  The JSON object returned by the request.
   *  Returns FALSE if no user was found.
   */
  public function getUserById($user_id) {
    $result = $this->request('GET', array(), $user_id);

    $user = FALSE;
    if (!empty($result->entries)) {
      $user = reset($result->entries);
      if (!empty($user)) {
        $result = $user;
      }
      else {
        $user = FALSE;
      }
    }
    return $user;
  }

  /**
   * Retrieve a user by their login.
   *
   * @param string $login
   *  The login (email address) for the user to be retrieved.
   *
   * @param bool $complete
   *  Indicates whether the full user should be returned, or just the ID.
   */
  public function getUserByLogin($login, $complete = TRUE){
    $result = $this->getUsers($login);
    if (!empty($result) && !empty($result[0])) {
      $user = $result[0];

      if (!empty($user)) {
        if (!$complete && $user->id) {
          $result = $user->id;
        }
        else {
          $result = $user;
        }
      }
    }

    return $result;
  }

  /**
   * Retrieves the details of the current user.
   *
   * This is just a wrapper around getUserById, using the special user ID of
   * 'me' to return the current user.
   *
   * @return object
   *  The JSON object returned by the request.
   *  Returns FALSE if no user was found.
   */
  public function getCurrentUser() {
    return $this->getUserById('me');
  }

  /**
   * Clean and prepare this object for a request.
   */
  private function getValues() {
    // Clone and remove the client from the User object.
    $values = clone $this;
    unset($values->box_client);
    unset($values->id);

    // Return the cleaned values as an array.
    return (array) $values;
  }

  /**
   * Utility function to save this object.
   */
  public function save() {
    if (!isset($this->id) || empty($this->id)) {
      $method = 'POST';
      $sub_path = '';
    }
    else {
      $method = 'PUT';
      $sub_path = $this->id;
    }
    $values = $this->getValues();

    $param = array(
      'body' => $values,
    );

    $return = $this->request($method, $param, $sub_path);
    $this->id = $return->entries[0]->id;
    return $return->entries[0];
  }

  /**
   * Perform a request to Box.com using the current BoxClient.
   *
   * @param string $method
   *  The HTTP method to use for this request. Defaults to Get.
   *
   * @param array $param
   *  An array of options for the request. These options are used to build the
   *  complete request. The structure of params is:
   *  array(
   *    'headers' => array(),
   *    'body' => array(),
   *    'url_param' => array(),
   *    'follow' => bool,
   *  );
   *
   * @param string $sub_path
   *  An optional sub_path to be added to the request.
   */
  private function request($method = 'GET', $param, $sub_path = '') {
    $box_client = $this->box_client;
    $url = $box_client::BOX_API_URL . '/' .  $this::USER_API_BASE_URL;

    if (!empty($sub_path)) {
      $url .= '/'. $sub_path;
    }
    $param['headers'] = !empty($param['headers']) ? $param['headers'] : array();

    $default_headers = array('Content-Type' => 'application/json');
    $param['headers'] = array_merge($default_headers, $param['headers']);

    return $box_client->curlRequest($url, $method, $param);
  }

  /**
   * Create a user in Box.
   *
   * @deprecated This is replaced by the more generic save() method.
   *  The save method performs a create/update based off of the values set
   *  against the user object.
   *
   * @param string $login
   *  Box login of the user to be created.
   *
   * @param string $name
   *  Name of the user to be created.
   *
   * @param array $values
   *  An array of values that should be added to the body of the request.
   *
   * @param array $fields
   *  An array of fields that should be returned in the response.
   */
  public function create($login, $name, $values = array(), $fields = array()){

    $defaults = array('login' => $login, 'name' => $name);

    $body_values = array_merge($defaults, $values);

    $param = array(
      'body' => $body_values,
    );

    // If there a list of fields were specified, then include them here.
    if (!empty($fields)) {
      $param['url_param'] = array('fields' => $fields);
    }

    return $this->request('POST', $param);
  }
}
