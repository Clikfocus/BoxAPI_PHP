<?php

/**
 * Box API folder class.
 *
 * Handles operations related to folders.
 */
class Folder {

  const FOLDERS_API_BASE_URL = 'folders';

  public $box_client;
  public $folder_id;

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
   * Retrieves details for a folder.
   *
   * @param string $folder
   *  The Box ID of the folder to look up.
   *
   * @param array $fields
   *  Allows callers to limit the fields included in the response.
   */
  public function getDetails($folder = 0, $fields = array()) {

    $param = array();

    if (!empty($fields)) {
      $param['url_param'] = array(
        'fields' => explode(',', $fields),
      );
    }

    return $this->request('GET', $param, $folder);
  }

  /**
   * Retrieves all items in a folder.
   *
   * @param string $folder
   *  The Box ID of the folder to look up.
   *
   * @param string $type
   *  Specify the type of items that should be returned. Valid values are:
   *  'folder', 'file', and 'web_link'. If no value is specified, all types
   *  are returned.
   *
   * @param array $url_param
   *  Allows callers to specify various criterea to limit or improve search.
   */
  public function getItems($folder = 0, $type = FALSE, $url_param = array()) {

    $sub_path = $folder . '/items';

    $param = array();

    if (!empty($url_param)) {
      $param['url_param'] = $url_param;
    }

    $response = $this->request('GET', $param, $sub_path);
    dpm($response);
    // Check to verify that results were actually returned.
    $results = FALSE;
    if (!empty($response->entries)) {

      // If no type was specified, then return all items.
      if (empty($type)) {
        $results = $response->entries;
      }
      else {
        // if a type was specified, then loop through all items to only return
        // items of that type.
        foreach($response->entries as $entry){
          if ($entry['type'] == $type){
            $results[] = $entry;
          }
        }
      }
    }

    return $results;
  }

  /**
   * Retrieves all collaborators for a folder.
   *
   * @param string $folder
   *  The Box ID of the folder to look up.
   *
   * @param array $fields
   *  Allows callers to limit the fields included in the response.
   */
  public function getCollaborators($folder = 0, $url_param = array()) {

    $sub_path = $folder . '/collaborations';

    $param = array();

    if (!empty($fields)) {
      $param['url_param'] = array(
        'fields' => explode(',', $fields),
      );
    }

    return $this->request('GET', $param, $sub_path);
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
    $url = $box_client::BOX_API_URL . '/' .  $this::FOLDERS_API_BASE_URL;

    if (!empty($sub_path)) {
      $url .= '/'. $sub_path;
    }

    return $box_client->curlRequest($url, $method, $param);
  }

  /**
   * Create a new folder.
   *
   * @param string $name
   *  The name of the folder to be created.
   *
   * @param string $parent_id
   *  The Box ID of the folder that the new folder should be created within.
   *
   * @param array $fields
   *  An array of fields that should be included in the response.
   */
  public function create($name, $parent_id, $fields = array()) {

    $param = array(
      'body' => array(
        'name' => $name,
        'parent' => array(
          'id' => $parent_id,
        ),
      ),
    );

    if (!empty($fields)) {
      $param['url_param'] = array(
        'fields' => explode(',', $fields),
      );
    }

    return $this->request('POST', $param);
  }

  /**
   * Updates the values of a folder
   *
   * @param string $folder
   *  The Box ID of the folder to look up.
   *
   * @param array $values
   *  An associative array of values to update the folder with.
   *
   * @param array $fields
   *  An array of fields that should be included in the response.
   */
  public function update($folder, $values, $fields = array()) {
    $param = array(
      'body' => array($values),
    );

    if (!empty($fields)) {
      $param['url_param'] = array(
        'fields' => explode(',', $fields),
      );
    }

    return $this->request('PUT', $param, $folder);
  }

  /**
   * Delete a folder from the Box App.
   *
   * @param string $folder
   *  The unique ID of the box folder.
   *
   * @param bool $recursive
   *  Whether the delete should be recursive. A value of FALSE will mean that
   *  the request will fail if the folder is not empty.
   */
  public function delete($folder, $recursive = FALSE) {

    $param['url_param'] = array('recursive' => $recursive);

    $return = $this->request('DELETE', $param, $folder);
    if (empty($return)){
      return TRUE;
    }
    else {
      return $return;
    }
  }
}
