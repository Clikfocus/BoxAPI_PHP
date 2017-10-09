<?php

/**
 * Box API file class.
 *
 * Handles operations related to files.
 */
class File {

  const FILE_API_BASE_URL = 'files';

  private $box_client;
  public $file_id;

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
   * Retrieves all comments for a file.
   *
   * @param string $file_id
   *  The unique ID of the box file.
   */
  public function getFile($file_id, $param = array()) {
    return $this->request('GET', $param, $path);
  }

  /**
   * Retrieves all comments for a file.
   *
   * @param string $file
   *  The unique ID of the box file.
   *
   * @param int $limit
   *
   * @param int $offset
   *
   * @param array $fields
   */
  public function getComments($file_id, $limit = 100, $offset = 0, $fields = array()) {
    $param = array(
      'url_param' => array(
        'limit' => $limit,
        'offset' => $offset,
      ),
    );

    if (!empty($fields)) {
      $param['url_param']['fields'] = explode(',', $fields);
    }

    $path = $file_id . '/comments';

    return $this->getFile($path, $param);
  }

  /**
   * Retrieves all tasks for a file.
   *
   * @param string $file
   *  The unique ID of the box file.
   *
   * @param array $fields
   */
  public function getTasks($file_id, $fields = array()) {
    $param = array();

    if (!empty($fields)) {
      $param['url_param']['fields'] = explode(',', $fields);
    }

    $path = $file_id . '/tasks';

    return $this->getFile($path, $param);
  }

  /**
   * Returns an embed link for a given file.
   *
   * Note that the embed link is only valid for 60 seconds.
   */
  public function getEmbedLink($file_id) {
    $param = array();

    $param['url_param']['fields'] = 'expiring_embed_link';

    return $this->getFile($file_id, $param);
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
    $url = $box_client::BOX_API_URL . '/' .  $this::FILE_API_BASE_URL;

    if (!empty($sub_path)) {
      $url .= '/'. $sub_path;
    }

    return $box_client->curlRequest($url, $method, $param);
  }

  /**
   * Uploades a given file to the active Box.com application.
   *
   * @TODO: Verify the approach for this. Is this type of functionality even
   *  necessary?
   *
   * @param string $file
   *  The unique ID of the box file.
   *
   * @param string $name
   *  The name of the file to be stored in Box.
   *
   * @param string $parent_id
   *  The Box ID of the parent element that this item should be stored in.
   *  Defaults to '0' which is the root directory for the account.
   */
  public function put($file, $name, $parent_id = '0') {
    $url = $this->build_url('/files/content', array(), $this->upload_url);
    $attributes = array('name' => $name, 'parent' => array('id' => $parent_id));
    $cfile = new CURLFile(realpath($file),'image/png','pic');
    $params = array('attributes' => json_encode($attributes), 'file' => $cfile);
    return json_decode($this->post($url, $params), TRUE);
  }

  /**
   * Updates the values of a file
   *
   * @param string $folder
   *  The Box ID of the folder to look up.
   *
   * @param array $values
   *  An associative array of values to update the file with.
   *
   * @param array $fields
   *  An array of fields that should be included in the response.
   */
  public function update($file, $values, $fields = array()) {
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
   * Delete a file from the Box App.
   *
   * @param string $file
   *  The unique ID of the box file.
   */
  public function delete($file) {
    $url = $this->build_url("/files/$file");
    $return = json_decode($this->delete($url), TRUE);
    if (empty($return)){
      return FALSE;
    }
    else {
      return $return;
    }
  }

}
