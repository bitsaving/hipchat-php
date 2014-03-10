<?php

namespace HipChat;

abstract class BasicHipChatClient {

  const DEFAULT_TARGET = 'https://api.hipchat.com';

  /**
   * HTTP response codes from API
   *
   * @see http://api.hipchat.com/docs/api/response_codes
   */
  const STATUS_BAD_RESPONSE = -1; // Not an HTTP response code
  const STATUS_OK = 200;
  const STATUS_BAD_REQUEST = 400;
  const STATUS_UNAUTHORIZED = 401;
  const STATUS_FORBIDDEN = 403;
  const STATUS_NOT_FOUND = 404;
  const STATUS_NOT_ACCEPTABLE = 406;
  const STATUS_INTERNAL_SERVER_ERROR = 500;
  const STATUS_SERVICE_UNAVAILABLE = 503;

  /**
   * Colors for rooms/message
   */
  const COLOR_YELLOW = 'yellow';
  const COLOR_RED = 'red';
  const COLOR_GRAY = 'gray';
  const COLOR_GREEN = 'green';
  const COLOR_PURPLE = 'purple';
  const COLOR_RANDOM = 'random';

  /**
   * Formats for rooms/message
   */
  const FORMAT_HTML = 'html';
  const FORMAT_TEXT = 'text';

  /**
   * API versions
   */
  const VERSION_1 = 'v1';

  private $api_target;
  private $auth_token;
  private $verify_ssl = true;
  private $proxy;

  /**
   * Creates a new API interaction object.
   *
   * @param $auth_token string Your API token.
   * @param $api_target string API protocol and host. Change if you're using an API
   *                           proxy such as apigee.com.
   * @param $api_version string Version of API to use.
   */
  public function __construct($auth_token, $api_target = self::DEFAULT_TARGET,
                       $api_version = self::VERSION_1) {
    $this->api_target = $api_target;
    $this->auth_token = $auth_token;
    $this->api_version = $api_version;
  }


  /////////////////////////////////////////////////////////////////////////////
  // Room functions
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Get information about a room
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/show
   */
  abstract public function get_room($room_id);

  /**
   * Determine if the given room name or room id already exists.
   *
   * @param mixed $room_id
   * @return boolean
   */
  public function room_exists($room_id) {
    try {
      $this->get_room($room_id);
    }
    catch (HipChat_Exception $e) {
      if ($e->code === self::STATUS_NOT_FOUND) {
        return false;
      }
      throw $e;
    }
    return true;
  }

  /**
   * Get list of rooms
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/list
   */
  abstract public function get_rooms();

  /**
   * Send a message to a room
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/message
   */
  abstract public function message_room($room_id, $from, $message, $notify = false,
                               $color = self::COLOR_YELLOW,
                               $message_format = self::FORMAT_HTML);

  /**
   * Get chat history for a room
   *
   * @see https://www.hipchat.com/docs/api/method/rooms/history
   */
  abstract public function get_rooms_history($room_id, $date = 'recent');

  /**
   * Set a room's topic
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/topic
   */
  abstract public function set_room_topic($room_id, $topic, $from = null);

  /**
   * Create a room
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/create
   */
  abstract public function create_room($name, $owner_user_id = null, $privacy = null, $topic = null, $guest_access = null);

  /**
   * Delete a room
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/delete
   */
  abstract public function delete_room($room_id);

  /////////////////////////////////////////////////////////////////////////////
  // User functions
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Get information about a user
   *
   * @see http://api.hipchat.com/docs/api/method/users/show
   */
  abstract public function get_user($user_id);

  /**
   * Get list of users
   *
   * @see http://api.hipchat.com/docs/api/method/users/list
   */
  abstract public function get_users();


  /////////////////////////////////////////////////////////////////////////////
  // Helper functions
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Performs a curl request
   *
   * @param $url        URL to hit.
   * @param $post_data  Data to send via POST. Leave null for GET request.
   *
   * @throws HipChat_Exception
   * @return string
   */
  protected function curl_request($url, $post_data = null) {

    if (is_array($post_data)) {
      $post_data = array_map(array($this, "sanitize_curl_parameter"), $post_data);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
    if (isset($this->proxy)) {
      curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
      curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
    }
    if (is_array($post_data)) {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    $response = curl_exec($ch);

    // make sure we got a real response
    if (strlen($response) == 0) {
      $errno = curl_errno($ch);
      $error = curl_error($ch);
      throw new HipChat_Exception(self::STATUS_BAD_RESPONSE,
        "CURL error: $errno - $error", $url);
    }

    // make sure we got a 200
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($code != self::STATUS_OK) {
      throw new HipChat_Exception($code,
        "HTTP status code: $code, response=$response", $url);
    }

    curl_close($ch);

    return $response;
  }

  /**
   * Sanitizes the given value as cURL parameter.
   *
   * The first value may not be a "@". PHP would treat this as a file upload
   *
   * @link http://www.php.net/manual/en/function.curl-setopt.php CURLOPT_POSTFIELDS
   *
   * @param string $value
   * @return string
   */
  protected function sanitize_curl_parameter ($value) {

    if ((strlen($value) > 0) && ($value[0] === "@")) {
      return substr_replace($value, '&#64;', 0, 1);
    }

    return $value;
  }

  /**
   * Make an API request using curl
   *
   * @param string $api_method  Which API method to hit, like 'rooms/show'.
   * @param array  $args        Data to send.
   * @param string $http_method HTTP method (GET or POST).
   *
   * @throws HipChat_Exception
   * @return mixed
   */
  public function make_request($api_method, $args = array(),
                               $http_method = 'GET') {
    $args['format'] = 'json';
    $args['auth_token'] = $this->auth_token;
    $url = "$this->api_target/$this->api_version/$api_method";
    $post_data = null;

    // add args to url for GET
    if ($http_method == 'GET') {
      $url .= '?'.http_build_query($args);
    } else {
      $post_data = $args;
    }

    $response = $this->curl_request($url, $post_data);

    // make sure response is valid json
    $response = json_decode($response);
    if (!$response) {
      throw new HipChat_Exception(self::STATUS_BAD_RESPONSE,
        "Invalid JSON received: $response", $url);
    }

    return $response;
  }

  /**
   * Enable/disable verify_ssl.
   * This is useful when curl spits back ssl verification errors, most likely
   * due to outdated SSL CA bundle file on server. If you are able to, update
   * that CA bundle. If not, call this method with false for $bool param before
   * interacting with the API.
   *
   * @param bool $bool
   * @return bool
   * @link http://davidwalsh.name/php-ssl-curl-error
   */
  public function set_verify_ssl($bool = true) {
    $this->verify_ssl = (bool)$bool;
    return $this->verify_ssl;
  }

  /**
   * Set an outbound proxy to use as a curl option
   * To disable proxy usage, set $proxy to null
   *
   * @param string $proxy
   */
  public function set_proxy($proxy) {
    $this->proxy = $proxy;
  }

}
