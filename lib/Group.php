<?php

/**
 * Box API group class.
 *
 * Handles operations related to groups.
 */
class Group {

  const GROUP_API_BASE_URL = 'groups';

  private $box_client;
  public $group_id;

  /**
   * Create a new instance of a Group.
   *
   * @param BoxClient $box_client
   *  A complete $box_client entity that is prepared to perform requests against
   *  the API.
   */
  public function __construct($box_client) {
    $this->box_client = $box_client;
  }

  /**
   * Get all Box groups.
   */
  private function getGroups(){
    $url = $this->build_url("/groups");
    return json_decode($this->get($url));
  }

  /**
   * Retrieve a given group by its ID.
   *
   * @param string $name
   *  The name of the Box Group to be returned.
   */
  public function getGroupId($name){
    $group_id = 0;
    $groups = $this->get_groups();
    foreach($groups->entries as $group){
      if ($group->name == $name){
        $group_id = $group->id;
      }
    }
    return $group_id;
  }

  /**
   * Create a new group.
   *
   * @param string $name
   *  The name of the group that should be created.
   */
  public function create($name){
    $url = $this->build_url("/groups");
    $params = array('name' => $name) ;
    return json_decode($this->post($url, json_encode($params)), TRUE);
  }

  /**
   * Add a user to a group.
   *
   * @param string $user_id
   *  Box ID of the user that should be added to the group.
   *
   * @param string $group_id
   *  Box ID of the group to which the user should be added.
   */
  public function addUser($user_id, $group_id){
    $url = $this->build_url("/group_memberships");
    $params = array('user' => array('id' => $userId), 'group' => array('id' => $groupId));
    return json_decode($this->post($url, json_encode($params)), TRUE);
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
    $url = $box_client::BOX_API_URL . '/' .  $this::GROUP_API_BASE_URL;

    if (!empty($sub_path)) {
      $url .= '/'. $sub_path;
    }

    $param['headers'] = !empty($param['headers']) ? $param['headers'] : array();

    $default_headers = array('Content-Type' => 'application/json');
    $param['headers'] = array_merge($default_headers, $param['headers']);

    return $box_client->curlRequest($url, $method, $param);
  }
}
