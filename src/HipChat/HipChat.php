<?php

namespace HipChat;

/**
 * Library for interacting with the HipChat REST API.
 *
 * @see http://api.hipchat.com/docs/api
 */
class HipChat {
  protected static $clients = array();

  protected static $client_map = array(
    'v1' => 'HipChat\HipChatV1',
    'v2' => 'HipChat\HipChatV2',
  );

  /**
   * Factory function to provide the appropriate implementation for the API 
   * Version requested.
   *
   * @param string $auth_token An API Authentication Token generated by HipChat
   * @param string $target The API Endpoint for the HipChat API
   * @param string $varsion The HipChat API version to be accessed
   * @return BasicHipChatClient
   */
  public static function get_client($auth_token,
                                    $target = null,
                                    $version = null) {

    if (is_null($target)) {
      $target = BasicHipChatClient::DEFAULT_TARGET;
    }
    if (is_null($version)) {
      $version = BasicHipChatClient::VERSION_1;
    }
    if ($client = empty(self::$clients[$version])) {
      if (empty(self::$client_map[$version])) {
          throw new HipChatVersion_Exception("Could not load HipChat Client for unknown version {$version}");
      }
      $client_class = self::$client_map[$version];
      $client = new $client_class($auth_token, $target, $version);
    }
    return $client;
  }
}

class HipChat_Exception extends \Exception {
  public $code;
  public function __construct($code, $info, $url) {
    $message = "HipChat API error: code=$code, info=$info, url=$url";
    parent::__construct($message, (int)$code);
  }
}

class HipChatVersion_Exception extends \Exception {
}
