<?php

/**
 * Box API user class.
 *
 * Handles operations related to users.
 */
class User {

  const USER_API_BASE_URL = 'users';

  private $box_client;
  public $user_id;

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
   * Delete a file from the Box App.
   *
   * @param string $file
   *  The unique ID of the box file.
   */
  public function delete($file) {

    $return = $this->request('DELETE', $param, $file);
    if (empty($return)){
      return TRUE;
    }
    else {
      return $return;
    }
  }

  /**
   * Create a user in Box.
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
  public function createUser($login, $name, $values = array(), $fields = array()){

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


  /**
   * Get a list of users.
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
    if (!empty($result['entries'])) {
      $entries = $result['entries'];
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

    if (!$complete) {
      if (!empty($result->entries)) {
        $user = reset($result->entries);
        if (!empty($user) && $user->id) {
          $result = $user->id;
        }
      }
    }

    return $result;
  }

  /**
   * Invite a user to an enterprise.
   *
   * @TODO: This implementation does not make use of the correct API criterea.
   */
  // public function inviteUser($login, $name) {
  //   $params = array('login' =>$login, 'name' => $name) ;
  //   return $this->request('POST');
  // }


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

    return $box_client->curlRequest($url, $method, $param);
  }
}
