<?php

/**
 * @file
 * Basic PHP client for Box.com API V2.
 *
 * Handles authorization and requests to/with Box.com.
 */

/**
 * Box client class.
 *
 * Handles authentication and all requests from other entities. This SDK
 * currently only supports JWT authentication with Box.com.
 */
class BoxClient {

  const BOX_AUTHORIZE_URL = 'https://www.box.com/api/oauth2/authorize';
  const BOX_TOKEN_URL = 'https://www.box.com/api/oauth2/token';
  const BOX_API_URL = 'https://api.box.com/2.0';
  const BOX_UPLOAD_URL = 'https://upload.box.com/api/2.0';

  private $access_token;
  private $token_expires;
  public $client_id;
  public $client_secret;
  public $public_key_id;
  public $refresh_token; // Remove?
  public $error_message; // Remove?
  public $reponse_status; // Remove?
  public $as_user; // Rename?

  /**
   * @param string $client_id
   *  The full client_id for the box account being accessed.
   *
   * @param string $client_secret
   *  The full client_secret for the box account being accessed.
   *
   * @param string $public_key_id
   *  The ID that box provides when RSA credentials are added to an account.
   */
  public function __construct($client_id = '', $client_secret = '', $public_key_id = '') {
    if (!empty($client_id) && !empty($client_secret)) {
      $this->client_id = $client_id;
      $this->client_secret = $client_secret;
      $this->public_key_id = $public_key_id;
    }
    else {
      throw ('Invalid CLIENT_ID and/or CLIENT_SECRET. Values cannot be empty.');
    }
  }

  /**
   * Sets a user as the current user against the client.
   *
   * This is used to spoof the user and make requests on their behalf.
   */
  public function setAsUser($user_id){
    $this->as_user = $user_id;
  }

  /**
   * Check if access token exists and if it has already expired.
   *
   * This function does NOT verify that an existing access token will actually
   * work. It only checks for the presence of an access token and for its
   * expiration status.
   *
   * @param bool $strict
   *  Determines whether an access token with no expiration time is considered
   *  expired or not. A value of TRUE requires that both an access token and
   *  an expiration value be present for the access token to be valid.
   *
   * @return bool
   *  Whether or not the current values for the access token and expiration time
   *  are valid.
   */
  public function checkAccess($strict = TRUE) {
    $token_valid = FALSE;

    if (!empty($this->access_token)) {
      $expires = $this->token_expires;

      if (empty($expires)) {
        // If no expiration value is present, then the returned is determined
        // by whether the strict parameter is specified. Note that that
        // the inverse of $strict is returned.
        $token_valid = !$strict;
      }
      elseif ($expires > time()) {
        $token_valid = TRUE;
      }
    }

    return $token_valid;
  }

  /**
   * Set the values of the access token and it's expiration time.
   *
   * @param string $access_token
   *  The access token string to be set on the BoxClient. A value of FALSE will
   *  leave the token unmodified.
   *
   * @param string $token_expires
   *  The timestamp for access_token expiration to be set on the BoxClient.
   *  A value of FALSE will leave the value unmodified.
   *
   * @return bool
   *  Whether the tokens were set sucessfully.
   */
  public function setAccess($access_token = FALSE, $token_expires = FALSE) {
    if ($access_token) {
      $this->access_token = $access_token;
    }
    if ($token_expires) {
      $this->token_expires = $token_expires;
    }
    return TRUE;
  }

  /**
   * Builds a url for a request
   *
   * @deprecated Entity classes will handle the majority of this logic.
   */
  private function buildUrl($api_func, array $opts = array(), $url) {
    if (!$url) $url = $this::BOX_API_URL;
    $opts = $this->addAccessHeader($opts);
    $base = $url . $api_func . '?';
    $query_string = http_build_query($opts);
    $base = $base . $query_string;
    return $base;
  }

  /**
   * Callback to set access header on outgoing requests.
   *
   * @param array $headers
   *  The array of headers to be added to the request.
   */
  private function addAccessHeader(&$headers) {
    if (!array_key_exists('Authorization', $headers)) {
      $headers['Authorization'] = 'Bearer ' . $this->access_token;
    }
  }

  /**
   * Callback to set the As-User header on outgoing requests.
   *
   * @param array $headers
   *  The array of headers to be added to the request.
   */
  private function performAsUser(&$headers) {
    if (!array_key_exists('As-User', $headers) && !empty($this->as_user)) {
      $headers['As-User'] = $this->as_user;
    }
  }


  /**
   * Authenticate against the Box API to retrieve an access token.
   *
   * This method does not set the retrieved token against it's parent. That must
   * be done separately.
   *
   * @param string $jwt
   *  The complete, JWT. The header, claim, and signature of the JWT must
   *  already be base64 encoded.
   *
   * @return object
   *  The JSON response object from the authentication request.
   */
  public function autenticate($jwt) {

    $url = $this::BOX_TOKEN_URL;
    $param = array();
    $param['headers'] = array('Content-Type' => 'application/x-www-form-urlencoded');
    $param['body'] = array(
      'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
      'assertion' => $jwt,
      'client_id' => $this->client_id,
      'client_secret' => $this->client_secret,
    );


    $response = $this->curlRequest($url, 'POST', $param);
    return $response;
  }

  /**
   * Decodes an xml response into an array.
   */
  private function parseResult($res) {
    $xml = simplexml_load_string($res);
    $json = json_encode($xml);
    $array = json_decode($json,TRUE);
    return $array;
  }

  /**
   * Perform a curl request based on the values provided.
   *
   * @param string $url
   *  The URL to which the request should be made. Any URL parameters which need
   *  to be included must be included in $param, not here.
   *
   * @param string $method
   *  The HTTP method for which to make the request.
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
   */
  public function curlRequest($url, $method = 'GET', $param = array()) {
    $ch = curl_init();

    // Initialize the headers/body/url_param variables before extracting them.
    // Only these three variables will be extracted and will then be used to
    // perform the request.
    $headers = $body = $url_param = $follow = array();
    extract($param, EXTR_IF_EXISTS);

    if (!empty($url_param)) {
      $url .= '?' . http_build_query($url_param, '', '&');
    }

    $this->addAccessHeader($headers);
    $this->performAsUser($headers);

    if (!empty($headers)) {
      $request_headers = array();
      foreach($headers as $parameter => $value) {
        $request_headers[] = $parameter . ': ' . $value;
      }
    }

    if (!empty($body)) {
      $body_string = http_build_query($body, '', '&');

      curl_setopt($ch, CURLOPT_POSTFIELDS, $body_string);
    }

    if ($follow) {
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
    curl_setopt($ch, CURLOPT_SSLVERSION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    dpm(curl_getinfo($ch));

    $response = curl_exec($ch);
    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);
    if ($response_code == 200) {
      return json_decode($response);
    }
    else {
      $error = "Request to $url failed with code $response_code.\n\nResponse:\n\n";
      $error .= "<pre>$response</pre>";

      throw new Exception($error);
    }
  }
}
