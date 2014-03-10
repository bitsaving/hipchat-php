<?php

namespace HipChat;

class HipChatV1 extends BasicHipChatClient {

  /////////////////////////////////////////////////////////////////////////////
  // Room functions
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Get information about a room
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/show
   */
  public function get_room($room_id) {
    $response = $this->make_request("rooms/show", array(
      'room_id' => $room_id
    ));
    return $response->room;
  }

  /**
   * Get list of rooms
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/list
   */
  public function get_rooms() {
    $response = $this->make_request('rooms/list');
    return $response->rooms;
  }

  /**
   * Send a message to a room
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/message
   */
  public function message_room($room_id, $from, $message, $notify = false,
                               $color = self::COLOR_YELLOW,
                               $message_format = self::FORMAT_HTML) {
    $args = array(
      'room_id' => $room_id,
      'from' => $from,
      'message' => utf8_encode($message),
      'notify' => (int)$notify,
      'color' => $color,
      'message_format' => $message_format
    );
    $response = $this->make_request("rooms/message", $args, 'POST');
    return ($response->status == 'sent');
  }

  /**
   * Get chat history for a room
   *
   * @see https://www.hipchat.com/docs/api/method/rooms/history
   */
   public function get_rooms_history($room_id, $date = 'recent') {
     $response = $this->make_request('rooms/history', array(
      'room_id' => $room_id,
      'date' => $date
     ));
     return $response->messages;
   }

  /**
   * Set a room's topic
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/topic
   */
   public function set_room_topic($room_id, $topic, $from = null) {
     $args = array(
       'room_id' => $room_id,
       'topic' => utf8_encode($topic),
     );

     if ($from) {
       $args['from'] = utf8_encode($from);
     }

     $response = $this->make_request("rooms/topic", $args, 'POST');
     return ($response->status == 'ok');
   }

  /**
   * Create a room
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/create
   */
   public function create_room($name, $owner_user_id = null, $privacy = null, $topic = null, $guest_access = null) {
     $args = array(
       'name' => $name
     );

     if ($owner_user_id) {
       $args['owner_user_id'] = $owner_user_id;
     }

     if ($privacy) {
       $args['privacy'] = $privacy;
     }

     if ($topic) {
       $args['topic'] = utf8_encode($topic);
     }

     if ($guest_access) {
       $args['guest_access'] = (int) $guest_access;
     }

     // Return the std object
     return $this->make_request("rooms/create", $args, 'POST');
    }

    /**
     * Delete a room
     *
     * @see http://api.hipchat.com/docs/api/method/rooms/delete
     */
   public function delete_room($room_id){
     $args = array(
       'room_id' => $room_id
     );

     $response = $this->make_request("rooms/delete", $args, 'POST');

     return ($response->deleted == 'true');
   }

  /////////////////////////////////////////////////////////////////////////////
  // User functions
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Get information about a user
   *
   * @see http://api.hipchat.com/docs/api/method/users/show
   */
  public function get_user($user_id) {
    $response = $this->make_request("users/show", array(
      'user_id' => $user_id
    ));
    return $response->user;
  }

  /**
   * Get list of users
   *
   * @see http://api.hipchat.com/docs/api/method/users/list
   */
  public function get_users() {
    $response = $this->make_request('users/list');
    return $response->users;
  }

}
